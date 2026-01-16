# PLAN DE MIGRATION MYSQL - SNACKAPP

## 📋 DOCUMENT DE CADRAGE
**Phase** : 0 - Cadrage
**Date** : 2026-01-16
**Objectif** : Migration complète de JSON vers MySQL avec préservation des données existantes

---

## 🎯 OBJECTIF DE LA MIGRATION

Migrer l'application SnackApp d'un système de stockage JSON vers une base de données MySQL relationnelle pour :
- Garantir l'intégrité référentielle des données
- Améliorer les performances sur de gros volumes
- Faciliter les requêtes complexes (stats, rapports)
- Préparer le multi-tenant (plusieurs restaurants)
- Éliminer les risques de corruption de fichiers JSON

---

## 📊 INVENTAIRE DES ENTITÉS MÉTIER

### 1. ENTITÉS EXISTANTES (déjà en MySQL)

#### 1.1 Restaurants (Multi-tenant)
- Table : `restaurants`
- Source : N/A (nouvelle structure)
- Champs : id, slug, name, logo, primary_color, is_active, created_at, updated_at

#### 1.2 Paramètres Restaurant
- Table : `restaurant_settings`
- Source : `config/restaurant.json` + `admin-panel-v2/data/settings.json`
- Champs : id, restaurant_id, phone, extra_phones (JSON), whatsapp_number, whatsapp_token, address, city, postal_code, latitude, longitude, instagram, facebook, tiktok, accepting_orders, loyalty_enabled, loyalty_points_per_euro, updated_at

#### 1.3 Horaires d'Ouverture
- Table : `opening_hours`
- Source : `config/restaurant.json` > openingHours
- Champs : id, restaurant_id, day_of_week (0-6), opens, closes, is_closed

#### 1.4 Catégories de Produits
- Table : `categories`
- Source : `config/menu.json` > menu.categories
- Champs : id, restaurant_id, name, slug, description, image, sort_order, is_active, created_at

#### 1.5 Produits
- Table : `products`
- Source : `config/menu.json` > menu.categories[].items
- Champs : id, restaurant_id, category_id, name, slug, description, image, price_solo, price_menu, status, sort_order, created_at, updated_at
- **Note** : Options spéciales stockées en JSON dans extensions futures

#### 1.6 Suppléments
- Table : `supplements`
- Source : `config/menu.json` > supplements.catalog
- Champs : id, restaurant_id, name, price, status, sort_order
- Liaison N:N : `product_supplements` (product_id, supplement_id)

#### 1.7 Clients
- Table : `customers`
- Source : `admin-panel-v2/data/customers.json`
- Champs : id, restaurant_id, loyalty_code, name, phone, email, loyalty_points, orders_count, total_spent, last_order_at, created_at, addresses (JSON), preferences (JSON), admin_notes

#### 1.8 Tags Clients
- Table : `customer_tags`
- Source : N/A (nouvelle fonctionnalité)
- Champs : customer_id, tag, created_at
- Tags disponibles : VIP, Régulier, Nouveau, Zone Centre/Est/Ouest, Livraison, Sur place, Entreprise

#### 1.9 Commandes
- Table : `orders`
- Source : `admin-panel-v2/data/orders.json`
- Champs : id, restaurant_id, customer_id, order_number, customer_name, customer_phone, subtotal, total, status, notes, pickup_time, loyalty_reward_id, loyalty_points_used, loyalty_redeemed, is_archived, created_at, completed_at

#### 1.10 Articles de Commande
- Table : `order_items`
- Source : `admin-panel-v2/data/orders.json` > items[]
- Champs : id, order_id, product_id, product_name, variant (solo/menu), quantity, unit_price, total_price

#### 1.11 Suppléments d'Articles
- Table : `order_item_supplements`
- Source : `admin-panel-v2/data/orders.json` > items[].supplements[]
- Champs : id, order_item_id, supplement_id, supplement_name, price

#### 1.12 Système de Fidélité - Transactions
- Table : `loyalty_transactions`
- Source : N/A (historique à créer)
- Champs : id, customer_id, restaurant_id, order_id, points, type (earn/redeem/bonus/adjustment), description, created_at

#### 1.13 Système de Fidélité - Récompenses
- Table : `loyalty_rewards`
- Source : N/A (configuration admin)
- Champs : id, restaurant_id, name, description, points_required, reward_type (discount_percent/discount_amount/free_item), reward_value, is_active, created_at

#### 1.14 Codes Promo
- Table : `promo_codes`
- Source : N/A (configuration admin)
- Champs : id, restaurant_id, code, description, discount_type (percent/fixed), discount_value, min_order_amount, max_uses, current_uses, starts_at, expires_at, is_active, created_at, updated_at

#### 1.15 FAQ
- Table : `faq`
- Source : `config/restaurant.json` > faq.items
- Champs : id, restaurant_id, question, answer, sort_order

#### 1.16 Utilisateurs Admin
- Table : `admin_users`
- Source : N/A (nouvelle fonctionnalité)
- Champs : id, restaurant_id, username, password_hash, role (owner/manager/staff), last_login, created_at

---

### 2. ENTITÉS À MIGRER (actuellement JSON)

#### 2.1 Livreurs
- Source : `admin-panel-v2/data/livreurs.json`
- Structure actuelle : `[]` (vide)
- Table cible : `delivery_persons`
- Champs proposés :
  - id (INT UNSIGNED, PK, AUTO_INCREMENT)
  - restaurant_id (INT UNSIGNED, FK)
  - name (VARCHAR(100))
  - phone (VARCHAR(20))
  - email (VARCHAR(150), nullable)
  - vehicle_type (ENUM: 'moto', 'voiture', 'velo', 'pieton')
  - is_active (TINYINT(1), default 1)
  - current_orders_count (INT, default 0)
  - total_deliveries (INT, default 0)
  - rating (DECIMAL(3,2), nullable, ex: 4.75)
  - notes (TEXT, nullable)
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)

#### 2.2 TGTG (Too Good To Go)
- Source : `admin-panel-v2/data/tgtg.json`
- Structure actuelle :
  ```json
  {
    "id": "TGTG202512121234567",
    "product_name": "Panier Surprise Burger",
    "description": "2 burgers + 1 portion de frites - Invendus du jour",
    "original_price": 18.0,
    "discount_price": 5.99,
    "quantity_total": 3,
    "quantity_available": 2,
    "pickup_time": "20:00 - 21:00",
    "created_at": "2025-12-12 18:30:00",
    "expires_at": "2025-12-12 23:59:59",
    "active": true
  }
  ```
- Table cible : `tgtg_baskets`
- Champs proposés :
  - id (INT UNSIGNED, PK, AUTO_INCREMENT)
  - restaurant_id (INT UNSIGNED, FK)
  - external_id (VARCHAR(50), nullable, index) -- ID TGTG API si intégration
  - product_name (VARCHAR(150))
  - description (TEXT)
  - original_price (DECIMAL(8,2))
  - discount_price (DECIMAL(8,2))
  - quantity_total (INT)
  - quantity_available (INT)
  - pickup_time (VARCHAR(50)) -- "20:00 - 21:00"
  - is_active (TINYINT(1))
  - created_at (TIMESTAMP)
  - expires_at (TIMESTAMP)
  - INDEX idx_active_expires (restaurant_id, is_active, expires_at)

#### 2.3 PIN Admin
- Source : `admin-panel-v2/data/admin-pin.json`
- Structure actuelle :
  ```json
  {
    "pin": "1234",
    "created_at": "2024-01-01T00:00:00Z",
    "note": "PIN par défaut..."
  }
  ```
- Table cible : `admin_pins`
- Champs proposés :
  - id (INT UNSIGNED, PK, AUTO_INCREMENT)
  - restaurant_id (INT UNSIGNED, FK, UNIQUE)
  - pin_hash (VARCHAR(255)) -- Hacher avec password_hash()
  - created_at (TIMESTAMP)
  - updated_at (TIMESTAMP)
  - last_changed_by (INT UNSIGNED, nullable, FK vers admin_users)
  - notes (TEXT, nullable)

#### 2.4 Archives de Commandes
- Source : `admin-panel-v2/data/orders-archive.json`
- Structure actuelle : `[]` (vide)
- **Stratégie** : Utiliser le flag `is_archived` dans la table `orders` existante
- **Pas de table séparée nécessaire**

#### 2.5 Extensions Produits (Options Spéciales)
- Source : `config/menu.json` > items[] avec options spéciales
- Exemples :
  - `hasViennoiserieOptions` + `viennoiserieOptions[]`
  - `hasPâtisserieOptions` + `pâtisserieOptions[]`
  - `hasBeverageOptions` + `beverageOptions[]`
  - `hasSpecialSauce` + `sauceOptions[]`
  - `hasSpecialAccompagnement` + `accompagnementOptions[]`
  - `capsuleColors[]`, `capsuleNumbers[]`
  - `variants[]` (pour cafés avec prix différents)

**Stratégie 1 (Recommandée)** : Colonne JSON dans `products`
- Ajouter colonne `options_config` (JSON) dans table `products`
- Stocker toutes les options spéciales dans ce champ
- Exemple :
  ```json
  {
    "type": "viennoiserie",
    "options": [
      {"id": "croissant", "name": "Croissant"},
      {"id": "pain-chocolat", "name": "Pain au Chocolat"}
    ]
  }
  ```

**Stratégie 2 (Alternative)** : Table `product_options`
- Champs : id, product_id, option_type, option_data (JSON), sort_order
- Plus flexible mais plus complexe

---

## 🗂️ SCHÉMA MYSQL CIBLE COMPLET

### Tables Principales (16 tables)

```sql
-- =============================================
-- TABLES EXISTANTES (déjà créées)
-- =============================================

1. restaurants
2. restaurant_settings
3. opening_hours
4. categories
5. products
6. supplements
7. product_supplements (liaison N:N)
8. customers
9. customer_tags
10. orders
11. order_items
12. order_item_supplements
13. loyalty_transactions
14. loyalty_rewards
15. promo_codes
16. faq
17. admin_users

-- =============================================
-- NOUVELLES TABLES À CRÉER
-- =============================================

18. delivery_persons (livreurs)
19. tgtg_baskets (Too Good To Go)
20. admin_pins (PIN admin)
21. product_options (optionnel, selon stratégie choisie)
```

### Vues Matérialisées (2)

```sql
1. v_dashboard_stats (statistiques quotidiennes)
2. v_top_products (produits populaires 30 jours)
```

---

## 🔗 RELATIONS ET CONTRAINTES

### Règles d'Intégrité Référentielle

1. **CASCADE DELETE** :
   - restaurant_id → supprime tous les enfants (settings, products, orders, etc.)
   - category_id → supprime tous les produits de la catégorie
   - order_id → supprime tous les items et suppléments

2. **SET NULL** :
   - customer_id dans orders → permet suppression client sans perdre historique
   - product_id dans order_items → snapshot préservé dans product_name

3. **UNIQUE CONSTRAINTS** :
   - (restaurant_id, slug) pour products, categories
   - (restaurant_id, phone) pour customers
   - loyalty_code global unique
   - (restaurant_id, code) pour promo_codes

---

## 📐 RÈGLES MÉTIER CRITIQUES

### 1. Gestion des Commandes

**Statuts** : pending → preparing → ready → completed | cancelled

**Règles** :
- `order_number` format : `ORD202512150001` (unique par restaurant)
- `subtotal` = somme des `order_items.total_price`
- `total` = subtotal - réductions (promo, points fidélité)
- `loyalty_points_used` : points déduits de la commande
- `loyalty_redeemed` : flag pour éviter double déduction

### 2. Système de Fidélité

**Règles** :
- Points gagnés : `total_spent * loyalty_points_per_euro`
- Accumulation : transaction `earn` dans `loyalty_transactions`
- Utilisation : transaction `redeem` avec points négatifs
- Solde : `SUM(points)` de toutes les transactions du client
- Cohérence : `customers.loyalty_points` = solde calculé

### 3. Codes de Fidélité

**Format** : `SNACK-A3X7` (10 caractères)
- Génération : `SNACK-` + 4 caractères alphanumériques uppercase
- Unicité : globale (pas par restaurant)
- Attribution : automatique à la création client

### 4. Codes Promo

**Validation** :
- Code actif : `is_active = 1`
- Date valide : `NOW() BETWEEN starts_at AND expires_at`
- Utilisations : `current_uses < max_uses` (si max_uses défini)
- Montant min : `order.subtotal >= min_order_amount` (si défini)

**Calcul réduction** :
- `percent` : `subtotal * (discount_value / 100)`
- `fixed` : `discount_value` (plafond = subtotal)

### 5. Produits et Variantes

**Prix** :
- `price_solo` : produit seul (obligatoire)
- `price_menu` : avec boisson/accompagnement (nullable)
- Options spéciales : prix dans `options_config` JSON

**Statuts** :
- `available` : en vente
- `unavailable` : temporairement indisponible

### 6. Suppléments

**Application** :
- Liaison produit-supplément via `product_supplements`
- Prix supplément ajouté au `unit_price` de l'article
- Snapshot dans `order_item_supplements` (nom + prix)

### 7. Clients

**Identification** :
- Phone : unique par restaurant
- Email : optionnel
- Loyalty code : unique global

**Adresses** (JSON) :
```json
[
  {
    "type": "home",
    "label": "Maison",
    "address": "123 Rue Example",
    "notes": "Interphone B",
    "is_default": true
  }
]
```

**Préférences** (JSON) :
```json
{
  "allergies": ["arachides", "gluten"],
  "favorites": [12, 45, 78],
  "delivery_instructions": "Sonner 2 fois",
  "preferred_time": "19:00-20:00"
}
```

### 8. Tags Clients

**Attribution automatique** :
- `VIP` : orders_count >= 10
- `Régulier` : 3 <= orders_count < 10
- `Nouveau` : orders_count < 3
- Autres tags : manuels (Zone, Type, Entreprise)

### 9. TGTG Baskets

**Logique** :
- `quantity_available` décrémenté à chaque vente
- `is_active = 0` si `quantity_available = 0` OU `expires_at` passé
- Cleanup automatique : suppression après `expires_at + 24h`

### 10. Livreurs

**Disponibilité** :
- `is_active = 1` : en service
- `current_orders_count` : nombre de commandes en cours
- Attribution : livreur avec `current_orders_count` minimal

---

## 🔄 PHASES DE MIGRATION

### PHASE 1 : Préparation BDD ✅ FAIT
- [x] Création schéma MySQL (`database/schema.sql`)
- [x] Migrations loyalty (`add_loyalty_system.sql`)
- [x] Migrations codes promo (`create_promo_codes_table.sql`)
- [x] Migrations clients améliorés (`2026-01-09-customer-improvements.sql`)
- [x] Repositories : CustomerRepository, OrderRepository, MenuRepository, LoyaltyRepository, PromoCodeRepository

### PHASE 2 : Migration Nouvelles Entités ⏳ EN ATTENTE
**Objectif** : Créer les 3 nouvelles tables

**Actions** :
1. Créer migration `database/migrations/create_delivery_tgtg_pins.sql` :
   - Table `delivery_persons`
   - Table `tgtg_baskets`
   - Table `admin_pins`

2. Créer repositories :
   - `DeliveryPersonRepository.php`
   - `TgtgRepository.php`
   - `AdminPinRepository.php`

3. Migration données :
   - Livreurs : `[]` → aucune migration nécessaire
   - TGTG : 1 panier test → migrer vers `tgtg_baskets`
   - PIN : hacher avec `password_hash()` → migrer vers `admin_pins`

### PHASE 3 : Extensions Produits (Options) ⏳ EN ATTENTE
**Objectif** : Gérer les options spéciales des produits

**Actions** :
1. **Stratégie A (Recommandée)** :
   - Ajouter colonne `options_config` JSON dans `products`
   - Script migration : parser `menu.json`, extraire options spéciales
   - Mettre à jour `MenuRepository` pour gérer options

2. **Stratégie B (Alternative)** :
   - Créer table `product_options`
   - Migration + repository dédié

### PHASE 4 : Refactoring Frontend ⏳ EN ATTENTE
**Objectif** : Connecter frontend aux nouvelles tables

**Actions** :
1. Modifier APIs admin pour :
   - Gestion livreurs (`/api/livreurs.php` → MySQL)
   - Gestion TGTG (`/api/tgtg.php` → MySQL)
   - Gestion PIN (`/api/admin-pin.php` → MySQL)

2. Tester chaque endpoint

### PHASE 5 : Suppression JSON ⏳ EN ATTENTE
**Objectif** : Retirer les fichiers JSON obsolètes

**Actions** :
1. Backup fichiers JSON dans `admin-panel-v2/data/backup-json/`
2. Supprimer lectures/écritures JSON du code
3. Tests régression complets

### PHASE 6 : Optimisations ⏳ EN ATTENTE
**Objectif** : Performance et monitoring

**Actions** :
1. Ajouter indexes composites si nécessaire
2. Analyser slow query log
3. Créer vues supplémentaires pour stats

---

## 📦 DONNÉES DE TEST

### Restaurant Principal
```sql
INSERT INTO restaurants (slug, name, is_active)
VALUES ('le-marvelous', 'Le Marvelous', 1);
```

### Récompenses Fidélité
```sql
INSERT INTO loyalty_rewards (restaurant_id, name, points_required, reward_type, reward_value) VALUES
(1, 'Boisson offerte', 50, 'free_item', 0),
(1, '-10% sur commande', 100, 'discount_percent', 10),
(1, 'Dessert offert', 200, 'free_item', 0);
```

### Codes Promo Test
```sql
INSERT INTO promo_codes (restaurant_id, code, discount_type, discount_value, is_active) VALUES
(1, 'BIENVENUE', 'percent', 10, 1),
(1, 'FIDELE20', 'fixed', 20, 1);
```

---

## ⚠️ RISQUES ET POINTS D'ATTENTION

### Risques Identifiés

1. **Perte de données** :
   - Backup JSON avant toute migration
   - Tests migration sur copie BDD

2. **Downtime** :
   - Migration en maintenance (fenêtre nocturne)
   - Temps estimé : 2-5 minutes selon volume

3. **Cohérence données** :
   - Vérifier foreign keys avant suppression JSON
   - Script validation post-migration

4. **Performance** :
   - Indexes optimaux critiques
   - Monitoring requêtes lentes

### Points d'Attention

1. **Options Produits** :
   - JSON flexible vs tables normalisées
   - Choix stratégie impacte complexité

2. **Multi-tenant** :
   - Toutes les requêtes DOIVENT filtrer par `restaurant_id`
   - Isolation données critique

3. **Historique Commandes** :
   - Snapshots produits/suppléments essentiels
   - Ne jamais supprimer si référencé dans commandes

4. **Codes Fidélité** :
   - Génération unique garantie
   - Collision impossible

---

## 📈 MÉTRIQUES DE SUCCÈS

### Indicateurs Clés

1. **Intégrité** :
   - ✅ 0 foreign key violation
   - ✅ 100% des commandes avec customer valide
   - ✅ 100% des order_items avec product snapshot

2. **Performance** :
   - ✅ Requêtes dashboard < 200ms
   - ✅ Insertion commande < 50ms
   - ✅ Recherche client < 30ms

3. **Complétude** :
   - ✅ 100% des JSON migrés
   - ✅ 0 lecture de fichiers JSON en production
   - ✅ Tous les repositories fonctionnels

---

## 🔧 OUTILS ET SCRIPTS

### Scripts de Migration

```
database/
├── schema.sql                     ✅ Schéma complet
├── migrations/
│   ├── add_loyalty_system.sql     ✅ Fidélité
│   ├── create_promo_codes_table.sql ✅ Codes promo
│   ├── 2026-01-09-customer-improvements.sql ✅ Clients
│   └── create_delivery_tgtg_pins.sql ⏳ À créer
├── migrate.php                    ✅ Runner migrations
└── verify-migration.php           ✅ Validation
```

### Repositories

```
database/repositories/
├── CustomerRepository.php         ✅ Clients
├── OrderRepository.php            ✅ Commandes
├── MenuRepository.php             ✅ Produits
├── LoyaltyRepository.php          ✅ Fidélité
├── PromoCodeRepository.php        ✅ Codes promo
├── RestaurantRepository.php       ✅ Restaurant
├── DeliveryPersonRepository.php   ⏳ À créer
├── TgtgRepository.php             ⏳ À créer
└── AdminPinRepository.php         ⏳ À créer
```

---

## 📝 CHANGELOG

| Date       | Phase | Action                                    | Statut |
|------------|-------|-------------------------------------------|--------|
| 2026-01-16 | 0     | Création MIGRATION.md                     | ✅ FAIT |
| 2025-12-20 | 1     | Schéma initial + loyalty                  | ✅ FAIT |
| 2026-01-09 | 1     | Améliorations clients (addresses, tags)   | ✅ FAIT |

---

## 📚 RÉFÉRENCES

### Fichiers Clés

- `database/schema.sql` : Schéma complet BDD
- `database/config.php` : Configuration connexion MySQL
- `database/Database.php` : Classe helper requêtes
- `admin-panel-v2/data/*.json` : Données actuelles (source)

### Documentation

- MySQL 8.0 : https://dev.mysql.com/doc/refman/8.0/
- JSON MySQL : https://dev.mysql.com/doc/refman/8.0/en/json.html
- PHP PDO : https://www.php.net/manual/en/book.pdo.php

---

**FIN DU DOCUMENT DE CADRAGE**
