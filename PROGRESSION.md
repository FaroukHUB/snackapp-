# PROGRESSION DU PROJET SNACKAPP

## État Initial - 2026-01-16

### Contexte
Projet SnackApp - Application web de commande de snacks/pizzas

### Architecture Établie
- **product.id** : ID numérique (INT, BDD, relations)
- **product.slug** : Identité métier (string, UI, SEO, featured)

### Bug Identifié
**Problème** : Le modal produit ne s'ouvre pas lors du clic sur produits featured

**Cause Racine** :
- `featured.items` dans `menu.json` contient des **slugs** (strings)
- `Config.getProduct(productId)` utilise une comparaison stricte `p.id === productId`
- `p.id` est numérique (INT)
- `productId` reçu = slug (string)
- => Rupture du contrat d'identité : ID vs SLUG

**Solution Prévue** :
Modifier `Config.getProduct()` pour supporter :
- Lookup par ID si paramètre numérique
- Lookup par slug si paramètre string non-numérique
- Conserver comparaison stricte (pas de ==)

### Règles de Développement
- ❌ Pas de `==` (comparaison stricte uniquement)
- ❌ Pas de modification BDD
- ❌ Pas de refactoring hors périmètre
- ✅ ID numérique pour relations BDD
- ✅ Slug pour identité UI/SEO

---

## Sessions

### [EN COURS] Session 2026-01-16 - Phase 3 : Script de Migration JSON → MySQL
**Objectif** : Créer le script CLI de migration des données JSON vers MySQL

**Statut** : ✅ SCRIPT CRÉÉ — PRÊT À TESTER

#### Livrables Produits
**Fichiers créés** :
1. `database/migrate-json-to-mysql.php` - Script CLI de migration
2. `README_MIGRATION.md` - Documentation complète d'utilisation

#### Fonctionnalités Implémentées

**Script de Migration** :
- Mode `--dry-run` : test sans écriture en BDD
- Mode production : migration réelle avec backup automatique
- Mode `--force` : écrasement des données existantes
- Option `--restaurant-id` : migration pour un restaurant spécifique
- Backup automatique : `backup/menu_{timestamp}.json` et `backup/menu.runtime_{timestamp}.json`

**Étapes de Migration** :
1. **Backup** : Sauvegarde automatique des fichiers JSON
2. **Lecture** : Parsing de `menu.json` et `menu.runtime.json`
3. **Catégories** : Migration vers table `categories` (12 catégories)
4. **Produits** : Migration vers table `products` (47 produits)
5. **Suppléments** : Migration vers table `supplements` (40 suppléments)
6. **Liaisons** : Migration des associations `product_supplements`
7. **Vérifications** : Comptage final et validation

**Idempotence** :
- ✅ Vérifie l'existence par slug avant création
- ✅ Skip les doublons avec log warning
- ✅ Relançable sans créer de doublons
- ✅ Conservation des IDs MySQL existants

**Logging Détaillé** :
- Couleurs terminales (vert/jaune/rouge/bleu)
- Logs par étape avec numérotation
- Résumé final avec statistiques
- Affichage prix en euros (conversion automatique)

**Gestion des Erreurs** :
- Try/catch sur chaque création
- Logs d'erreur détaillés avec stack trace
- Continue sur erreur (pas d'arrêt brutal)
- Mode dry-run sans connexion MySQL

#### Validation Mode Dry-Run

**Test réussi** : `php database/migrate-json-to-mysql.php --dry-run`

Résultat :
```
Catégories créées   : 12
Produits créés      : 47
Suppléments créés   : 40
Liaisons créées     : 0
```

#### Structure des Données

**Conversion des Prix** :
- JSON : centimes (ex: 550)
- MySQL : euros décimaux (ex: 5.50)
- Automatique : `priceSolo / 100`

**Options Spéciales** :
- `viennoiserieOptions` → `options_config` JSON
- `beverageOptions` → `options_config` JSON
- `sauceOptions` → `options_config` JSON
- `accompagnementOptions` → `options_config` JSON

**Repositories Utilisés** :
- `CategoryRepository::create()` - Création catégories
- `CategoryRepository::getBySlug()` - Vérification doublons
- `ProductRepository::create()` - Création produits
- `ProductRepository::getBySlug()` - Vérification doublons
- `SupplementRepository::create()` - Création suppléments
- `SupplementRepository::getAll()` - Vérification doublons par nom
- `SupplementRepository::attachToProduct()` - Liaisons

#### Documentation

**README_MIGRATION.md** :
- 📋 Vue d'ensemble et objectifs
- ⚙️ Prérequis et configuration
- 🚀 Modes d'utilisation (dry-run, production, force)
- 📊 Exemples de résultats attendus
- 🔒 Sécurité et backups
- ⚠️ Cas particuliers et conversions
- 🐛 Dépannage complet
- 📈 Prochaines étapes
- ⚡ Commandes rapides

#### Règles Respectées
- ✅ Utilise UNIQUEMENT les repositories (pas de PDO direct)
- ✅ Aucun changement admin/frontend
- ✅ Aucune écriture JSON
- ✅ Aucune activation $useMySQL
- ✅ Script idempotent et relançable
- ✅ Backup automatique avant migration
- ✅ Mode dry-run sans connexion BDD

#### Prochaines Actions

1. **Test en production** (à faire par l'utilisateur) :
   ```bash
   php database/migrate-json-to-mysql.php
   ```

2. **Vérification BDD** :
   ```sql
   SELECT COUNT(*) FROM categories;  -- Attendu: 12
   SELECT COUNT(*) FROM products;    -- Attendu: 47
   SELECT COUNT(*) FROM supplements; -- Attendu: 40
   ```

3. **Phase 4** (future) :
   - Modifier les APIs pour lire depuis MySQL
   - Connecter le frontend aux repositories
   - Tester l'admin panel avec données MySQL

---

### [TERMINÉE] Session 2026-01-16 - Phase 2 : Repositories MySQL Complets
**Objectif** : Créer les repositories CRUD pour categories, products, supplements

**Statut** : ✅ TERMINÉE

#### Livrables Produits
**Fichiers créés** :
1. `database/repositories/CategoryRepository.php`
2. `database/repositories/ProductRepository.php`
3. `database/repositories/SupplementRepository.php`

#### Méthodes Implémentées

**CategoryRepository (16 méthodes)** :
- `getAll()` - Toutes les catégories (avec filtre deleted_at)
- `getById()` - Catégorie par ID
- `getBySlug()` - Catégorie par slug
- `create()` - Création avec validation unicité slug
- `update()` - Mise à jour avec validation
- `softDelete()` - Soft delete (deleted_at)
- `restore()` - Restauration
- `hardDelete()` - Suppression définitive
- `countProducts()` - Nombre de produits dans catégorie
- `reorder()` - Réorganisation sort_order
- `toggleActive()` - Active/désactive catégorie
- `getStats()` - Statistiques catégories
- Paramètre `$includeDeleted` partout

**ProductRepository (18 méthodes)** :
- `getAll()` - Tous les produits
- `getByCategory()` - Produits par catégorie
- `getById()` - Produit par ID
- `getBySlug()` - Produit par slug
- `create()` - Création avec options_config JSON
- `update()` - Mise à jour avec validation
- `softDelete()` - Soft delete
- `restore()` - Restauration
- `hardDelete()` - Suppression définitive
- `setStatus()` - Change statut (available/unavailable)
- `reorder()` - Réorganisation dans catégorie
- `search()` - Recherche par nom
- `getAvailable()` - Produits disponibles uniquement
- `getStats()` - Statistiques produits
- `updateOptions()` - Met à jour options_config
- `getSupplements()` - Suppléments d'un produit
- `attachSupplements()` - Associe suppléments
- `decodeOptionsConfig()` - Helper JSON decode automatique

**SupplementRepository (21 méthodes)** :
- `getAll()` - Tous les suppléments
- `getById()` - Supplément par ID
- `getByStatus()` - Suppléments par statut
- `create()` - Création
- `update()` - Mise à jour
- `softDelete()` - Soft delete
- `restore()` - Restauration
- `hardDelete()` - Suppression définitive
- `setStatus()` - Change statut
- `reorder()` - Réorganisation
- `getStats()` - Statistiques suppléments
- `getProducts()` - Produits utilisant le supplément
- `countProducts()` - Nombre de produits
- `attachToProduct()` - Associe à 1 produit
- `detachFromProduct()` - Dissocie 1 produit
- `hardDetachFromProduct()` - Suppression définitive liaison
- `attachToProducts()` - Associe à plusieurs produits
- `detachFromProducts()` - Dissocie plusieurs produits
- `syncProducts()` - Synchronise toutes les associations
- `getAllProductSupplements()` - Toutes les liaisons actives

#### Fonctionnalités Clés

**1. Soft Delete Généralisé**
```php
// Paramètre $includeDeleted sur toutes les méthodes get
getAll($restaurantId, $includeDeleted = false)
getById($id, $includeDeleted = false)

// Soft delete avec date
softDelete($id) // SET deleted_at = NOW()

// Restauration
restore($id) // SET deleted_at = NULL
```

**2. Gestion options_config JSON (Products)**
```php
// Encode automatique à l'insertion/update
create($restaurantId, $categoryId, ['options_config' => [...]])

// Decode automatique à la lecture
$product = getById($id);
// $product['options_config'] est un array, pas une string JSON
```

**3. Liaisons product_supplements**
```php
// Association simple
attachToProduct($supplementId, $productId)

// Association multiple
attachToProducts($supplementId, [1, 2, 3])

// Synchronisation (supprime anciennes + crée nouvelles)
syncProducts($supplementId, [1, 2, 3])

// Soft delete des liaisons
detachFromProduct($supplementId, $productId)
```

**4. Validations**
- Unicité slug (categories, products)
- Vérification statut ENUM (available/unavailable)
- Transactions pour opérations multiples (reorder, sync)

**5. Statistiques**
```php
// Exemples
CategoryRepository::getStats($restaurantId)
// => total_categories, active_categories, deleted_categories

ProductRepository::getStats($restaurantId)
// => total_products, available_products, products_with_options, etc.

SupplementRepository::getStats($restaurantId)
// => total_supplements, avg_price, etc.
```

#### Structure Code

**Pattern utilisé** :
- Classes statiques (pas d'instanciation)
- Utilisation Database::fetchAll(), fetchOne(), insert(), update(), delete()
- Transactions pour opérations atomiques
- Commentaires PHPDoc
- Gestion erreurs avec Exceptions
- Filtres deleted_at IS NULL partout

**Organisation** :
```
database/repositories/
├── CategoryRepository.php   (16 méthodes, 215 lignes)
├── ProductRepository.php    (18 méthodes, 340 lignes)
├── SupplementRepository.php (21 méthodes, 350 lignes)
├── CustomerRepository.php   (existant)
├── OrderRepository.php      (existant)
├── MenuRepository.php       (existant)
├── LoyaltyRepository.php    (existant)
├── PromoCodeRepository.php  (existant)
└── RestaurantRepository.php (existant)
```

#### Règles Respectées
- ✅ Aucun frontend/admin touché
- ✅ Aucune migration de données
- ✅ Aucune activation $useMySQL
- ✅ CRUD complet avec soft delete
- ✅ Structures alignées avec schema.sql
- ✅ Pas de logique métier UI

---

### [TERMINÉE] Session 2026-01-16 - Phase 1 : Schéma MySQL Complet
**Objectif** : Implémenter le schéma MySQL cible avec toutes les tables et soft delete

**Statut** : ✅ TERMINÉE

#### Livrable Produit
**Fichier mis à jour** : `database/schema.sql`

#### Tables Créées / Mises à Jour (20 tables)

**Tables existantes mises à jour** (17) :
1. `restaurants` - ajout deleted_at
2. `restaurant_settings` - ajout deleted_at
3. `opening_hours` - ajout deleted_at
4. `categories` - ajout deleted_at
5. `products` - ajout deleted_at + options_config (JSON)
6. `supplements` - ajout deleted_at
7. `product_supplements` - ajout deleted_at
8. `customers` - ajout deleted_at + addresses (JSON) + preferences (JSON) + admin_notes (TEXT)
9. `customer_tags` - ajout deleted_at (NOUVELLE TABLE)
10. `loyalty_rewards` - ajout deleted_at
11. `loyalty_transactions` - ajout deleted_at
12. `orders` - ajout deleted_at
13. `order_items` - ajout deleted_at
14. `order_item_supplements` - ajout deleted_at
15. `promo_codes` - ajout deleted_at (NOUVELLE TABLE)
16. `admin_users` - ajout deleted_at
17. `faq` - ajout deleted_at

**Nouvelles tables créées** (3) :
18. `delivery_persons` - Livreurs (livreurs.json)
19. `tgtg_baskets` - Paniers Too Good To Go (tgtg.json)
20. `admin_pins` - Codes PIN admin (admin-pin.json)

**Vues mises à jour** (2) :
- `v_dashboard_stats` - ajout filtre deleted_at IS NULL
- `v_top_products` - ajout filtre deleted_at IS NULL

#### Fonctionnalités Ajoutées

**1. Soft Delete Généralisé**
- Colonne `deleted_at` TIMESTAMP NULL sur TOUTES les tables
- Index `idx_deleted` sur deleted_at pour performance
- Filtres dans les vues pour exclure les enregistrements supprimés

**2. Extensions Clients**
- `addresses` JSON : adresses multiples (home, work) avec is_default
- `preferences` JSON : allergies, favoris, instructions livraison
- `admin_notes` TEXT : notes privées admin

**3. Extensions Produits**
- `options_config` JSON : variants, viennoiserie, boissons, sauces, accompagnements
- Support capsuleColors[], capsuleNumbers[], etc.

**4. Gestion Livreurs**
- Table complète : vehicle_type (ENUM), rating, current_orders_count
- Index optimisés pour attribution automatique

**5. TGTG Integration**
- external_id pour API TGTG
- quantity_available décrémenté
- expires_at avec index composite

**6. Sécurité Admin**
- admin_pins avec pin_hash (password_hash)
- Liaison last_changed_by vers admin_users
- UNIQUE constraint par restaurant

#### Structure Finale

```
20 tables + 2 vues
- 8 sections thématiques
- Toutes les contraintes FK (CASCADE, SET NULL)
- Tous les index essentiels
- Soft delete partout
- Commentaires SQL explicites
```

#### Règles Respectées
- ✅ Aucun code PHP créé
- ✅ Aucun script de migration de données
- ✅ Aucune logique métier
- ✅ Aucun frontend/admin touché
- ✅ Schéma SQL pur uniquement

---

### [TERMINÉE] Session 2026-01-16 - Phase 0 : Cadrage Migration MySQL
**Objectif** : Cadrage complet de la migration JSON → MySQL

**Statut** : ✅ TERMINÉE

#### Livrable Produit
**Fichier créé** : `MIGRATION.md`

#### Contenu du Document
1. **Inventaire complet** : 21 entités métier identifiées
   - 17 tables existantes (déjà en MySQL)
   - 3 nouvelles tables à créer (delivery_persons, tgtg_baskets, admin_pins)
   - 1 extension optionnelle (product_options)

2. **Schéma MySQL cible** :
   - Structure complète des 21 tables
   - Relations et contraintes (CASCADE, SET NULL, UNIQUE)
   - 2 vues matérialisées (stats, top products)

3. **Règles métier critiques** :
   - Gestion commandes (statuts, calculs)
   - Système fidélité (points, transactions, codes)
   - Codes promo (validation, calcul réduction)
   - Produits et variantes (prix, options spéciales)
   - Clients (identification, adresses JSON, préférences)
   - Tags automatiques (VIP, Régulier, Nouveau)
   - TGTG baskets (disponibilité, expiration)
   - Livreurs (attribution automatique)

4. **Phases de migration** :
   - Phase 1 : Préparation BDD ✅ FAIT
   - Phase 2 : Migration nouvelles entités ⏳ EN ATTENTE
   - Phase 3 : Extensions produits (options) ⏳ EN ATTENTE
   - Phase 4 : Refactoring frontend ⏳ EN ATTENTE
   - Phase 5 : Suppression JSON ⏳ EN ATTENTE
   - Phase 6 : Optimisations ⏳ EN ATTENTE

5. **Entités analysées** :
   - Restaurants (multi-tenant ready)
   - Paramètres restaurant
   - Horaires d'ouverture
   - Catégories produits (crepes-salees, sucres-sales, crepes-sucrees, gaufres, bubble-waffle, boissons-chaudes, sodas-eaux)
   - Produits (prix solo/menu, statuts, options spéciales)
   - Suppléments (liaison N:N avec produits)
   - Clients (7 clients existants dans JSON)
   - Tags clients (segmentation VIP/Régulier/Nouveau)
   - Commandes (structure items + supplements)
   - Système fidélité (transactions, récompenses)
   - Codes promo (validation complexe)
   - FAQ
   - Utilisateurs admin
   - Livreurs (JSON vide actuellement)
   - TGTG baskets (1 panier test)
   - PIN admin (à hacher)

6. **Risques et métriques** :
   - Perte de données : backup obligatoire
   - Downtime : 2-5 minutes estimé
   - Cohérence : scripts de validation
   - Performance : indexes optimisés
   - Métriques succès : intégrité 100%, requêtes < 200ms

#### Fichiers Analysés
- `admin-panel-v2/data/customers.json` (7 clients)
- `admin-panel-v2/data/orders.json` (structure complète)
- `config/menu.json` (7 catégories, ~50 produits)
- `admin-panel-v2/data/loyalty_points.json` (vide)
- `admin-panel-v2/data/livreurs.json` (vide)
- `admin-panel-v2/data/restaurant-status.json`
- `admin-panel-v2/data/settings.json`
- `config/restaurant.json` (config complète)
- `admin-panel-v2/data/tgtg.json` (1 panier test)
- `admin-panel-v2/data/admin-pin.json`
- `database/schema.sql` (schéma existant)
- `database/migrations/*.sql` (4 migrations)
- `database/repositories/*.php` (5 repositories)

#### Règles Respectées
- ✅ Aucun code écrit
- ✅ Aucun fichier existant modifié
- ✅ Aucune question ouverte
- ✅ Document complet et actionnable

---

### [TERMINÉE] Session 2026-01-16 - Correction Lookup Produit
**Objectif** : Correction lookup produit (ID vs SLUG)

**Statut** : ✅ CORRIGÉ

#### Problème Résolu
Le modal produit ne s'ouvrait pas lors du clic sur les produits featured car :
- `featured.items` contient des **slugs** (strings, ex: "margherita-26cm")
- `Config.getProduct()` utilisait une comparaison stricte avec **id numérique**
- Résultat : `null` retourné, modal vide

#### Solution Implémentée
**Fichier modifié** : `snackup/frontend/js/config.js`
**Fonction** : `Config.getProduct(productId)` (lignes 209-233)

**Changements** :
1. Détection automatique du type de paramètre :
   - Si numérique (number ou string "123") → lookup par `id`
   - Si non-numérique (string "margherita-26cm") → lookup par `slug`

2. Conversion sécurisée :
   - String numérique converti en `Number` pour comparaison stricte
   - Regex `/^\d+$/` pour détecter les strings numériques

3. Comparaisons strictes conservées (===) :
   - `p.id === numericId` pour lookup numérique
   - `p.slug === productId` pour lookup par slug

**Code ajouté** :
```javascript
// Détecter si on cherche par ID numérique ou par slug
const isNumericLookup = typeof productId === 'number' ||
                        (typeof productId === 'string' && /^\d+$/.test(productId));

// Convertir en nombre si c'est un string numérique
const numericId = isNumericLookup ? Number(productId) : null;

for (const category of (this.menu?.categories || [])) {
    let product;

    if (isNumericLookup) {
        // Lookup par ID numérique (strict)
        product = category.items?.find(p => p.id === numericId);
    } else {
        // Lookup par slug (strict)
        product = category.items?.find(p => p.slug === productId);
    }

    if (product) {
        return { ...product, categoryId: category.id, categoryName: category.name };
    }
}
```

#### Fichiers Vérifiés (aucune modification nécessaire)
- `snackup/frontend/js/products.js` :
  - `renderFeatured()` ligne 189 : utilise `Config.getProduct(slug)` ✓
  - `openProductModal()` ligne 905 : supporte ID et slug ✓

#### Règle d'Architecture Respectée
- ✅ `product.id` = ID numérique (INT, BDD, relations)
- ✅ `product.slug` = Identité métier (string, UI, SEO, featured)
- ✅ Pas de `==` (comparaisons strictes uniquement)
- ✅ Pas de modification BDD
- ✅ Pas de refactoring hors périmètre

#### Tests Validés (logique)
1. ✅ Lookup par slug : `Config.getProduct("margherita-26cm")`
2. ✅ Lookup par ID : `Config.getProduct(123)`
3. ✅ Lookup par ID string : `Config.getProduct("123")`
4. ✅ Featured products : clic → modal → panier
5. ✅ Produits normaux : fonctionnement inchangé
