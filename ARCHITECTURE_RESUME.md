# 📐 RÉSUMÉ ARCHITECTURE - SNACKUP CUISINE TYPES

**Date:** 2026-03-20
**Status:** ✅ Exploration terminée, en attente validation

---

## 🏗️ ARCHITECTURE EXISTANTE (Analysée)

### Multi-tenant par domaine
```
Requête HTTP → $_SERVER['HTTP_HOST']
    ↓
InstanceManager::detectInstance()
    ↓
Charge config/instances.json
    ↓
Route vers config/<instance>/config.php
    ↓
Extraction restaurant_id
    ↓
Database::init() avec credentials instance
```

### Repository Pattern
```php
MenuRepository::$restaurantId = RESTAURANT_ID;
MenuRepository::getAllCategories();
  → SELECT * FROM categories WHERE restaurant_id = ?
```

**Tous les repositories filtrent automatiquement par `restaurant_id`.**

### API REST
```
/snackup/admin/api/products.php
  ├── GET    → Liste produits
  ├── POST   → Créer produit
  ├── PUT    → Modifier produit
  └── DELETE → Supprimer produit (soft delete)
```

---

## 🗄️ NOUVELLES TABLES (6 tables)

### 📋 Tables globales (prédéfinies)

| Table | Rôle | Lignes estimées |
|-------|------|----------------|
| `cuisine_types` | Types disponibles (tacos, burger, etc.) | ~15 |
| `cuisine_type_steps` | Étapes par type (Taille, Viande, etc.) | ~80 |
| `cuisine_type_step_options` | Options exemples (S/M/L, etc.) | ~400 |

### 🏪 Tables par restaurant (personnalisables)

| Table | Rôle | Lignes/resto |
|-------|------|--------------|
| `restaurant_cuisine_types` | Types activés | 3-5 |
| `restaurant_cuisine_steps` | Config des étapes | 15-20 |
| `restaurant_step_options` | Options custom | 50-100 |

### 📝 Modification table existante

```sql
ALTER TABLE `categories`
ADD COLUMN `cuisine_type_id` INT UNSIGNED NULL,
ADD COLUMN `is_common` BOOLEAN DEFAULT FALSE;
```

**Rétrocompatibilité:** `cuisine_type_id` NULL = catégorie legacy (fonctionne normalement)

---

## 🎯 LOGIQUE MÉTIER CLÉS

### 1️⃣ Templates → Personnalisation

```
SYSTÈME fournit:
  Tacos
    ├── Étape "Taille" (obligatoire, 1 choix)
    │   ├── Simple
    │   ├── Double
    │   └── Triple
    └── Étape "Viande" (obligatoire, 1-3 choix)
        ├── Poulet
        └── Bœuf

ADMIN personnalise:
  Tacos
    ├── Étape "Format" (renommée)
    │   ├── S       ← Modifié
    │   ├── M       ← Modifié
    │   ├── L       ← Modifié
    │   └── XL      ← Ajouté
    └── Étape "Viande" (2-4 choix)  ← Config modifiée
        ├── Poulet mariné      ← Renommé
        ├── Viande hachée      ← Renommé
        ├── Escalope panée     ← Ajouté
        └── Mixte au choix     ← Ajouté
```

### 2️⃣ Cascade de création

```sql
-- Admin active "Tacos"
INSERT INTO restaurant_cuisine_types
  (restaurant_id=1, cuisine_type_id=1);  -- id=100

-- Système copie automatiquement les étapes
INSERT INTO restaurant_cuisine_steps
  (restaurant_cuisine_type_id=100, cuisine_type_step_id=1, ...);
INSERT INTO restaurant_cuisine_steps
  (restaurant_cuisine_type_id=100, cuisine_type_step_id=2, ...);

-- Système copie automatiquement les options
INSERT INTO restaurant_step_options (...);
INSERT INTO restaurant_step_options (...);
```

### 3️⃣ Affichage client

```javascript
// Client clique sur un produit Tacos
fetch('/api/product-composition/' + productId)
  .then(steps => {
    steps.forEach(step => {
      renderStep(step.name, step.options, step.min_choices, step.max_choices);
    });
  });
```

**Le client ne voit JAMAIS les types de cuisine, juste les produits et étapes.**

---

## 🔒 RÉTROCOMPATIBILITÉ

### Restaurants existants (en production)

| Situation | Comportement |
|-----------|--------------|
| Categories actuelles | `cuisine_type_id = NULL` → tout fonctionne |
| Produits actuels | Aucun changement, prix fixes |
| Commandes actuelles | Pas d'étapes de composition |
| Admin actuel | Pas d'onboarding, pas de changement |

**Impact: ZÉRO** si feature flag désactivé.

### Nouveaux restaurants

| Situation | Comportement |
|-----------|--------------|
| Premier login admin | Onboarding: sélection des types |
| Création catégorie | Choix du `cuisine_type_id` |
| Création produit | Étapes de composition automatiques |
| Client commande | Interface de composition dynamique |

---

## 🚀 FEATURE FLAG

```php
// bootstrap.php
define('CUISINE_TYPES_ENABLED', true);

// Partout dans le code
if (CUISINE_TYPES_ENABLED) {
    // Nouvelle logique
} else {
    // Legacy code
}
```

**Rollback instantané:** Mettre `false` → retour au système actuel.

---

## 📊 REQUÊTES CLÉS

### Charger la config complète d'un restaurant

```sql
-- Types activés
SELECT ct.name, ct.slug, rct.sort_order
FROM restaurant_cuisine_types rct
JOIN cuisine_types ct ON rct.cuisine_type_id = ct.id
WHERE rct.restaurant_id = ? AND rct.is_active = 1
ORDER BY rct.sort_order;

-- Étapes d'un type
SELECT rcs.*, cts.name as default_name
FROM restaurant_cuisine_steps rcs
JOIN cuisine_type_steps cts ON rcs.cuisine_type_step_id = cts.id
WHERE rcs.restaurant_cuisine_type_id = ? AND rcs.is_active = 1
ORDER BY rcs.sort_order;

-- Options d'une étape
SELECT name, price_modifier
FROM restaurant_step_options
WHERE restaurant_cuisine_step_id = ? AND is_active = 1
ORDER BY sort_order;
```

### Charger les étapes pour un produit

```sql
-- Via la catégorie du produit
SELECT c.cuisine_type_id
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.id = ?;

-- Puis charger les étapes de ce type
```

---

## 🎨 EXEMPLES CONCRETS

### Pizzeria classique

```yaml
Types activés:
  - Pizza
  - Boissons (is_common)
  - Desserts (is_common)

Étapes Pizza:
  1. Taille (désactivée car une seule taille: 33cm)
  2. Pâte (désactivée car toujours fine)
  3. Base (désactivée car toujours tomate)
  4. Retrait ingrédients (active)
     - Sans olives
     - Sans anchois
     - Sans champignons
  5. Suppléments (active)
     - Mozzarella +2€
     - Chorizo +1.50€
```

### Tacos moderne

```yaml
Types activés:
  - Tacos
  - Boissons
  - Desserts

Étapes Tacos:
  1. Format (obligatoire)
     - S (0€)
     - M (+1€)
     - L (+2€)
     - XL (+3.50€)
  2. Viandes (obligatoire, 1-3 choix selon format)
     - Poulet
     - Bœuf
     - Cordon bleu
     - Tenders
  3. Sauce fromagère (obligatoire)
     - Classique
     - Épicée
     - Légère
  4. Sauce extra (optionnel, 0-2)
     - Samouraï
     - Blanche
     - Barbecue
  5. Suppléments (optionnel)
     - Fromage +1€
     - Gratinage +1.50€
```

### Burger compositeur

```yaml
Types activés:
  - Burger
  - Boissons
  - Desserts

Étapes Burger:
  1. Pain (optionnel)
     - Brioché
     - Classique
     - Sésame
  2. Viande (obligatoire)
     - Bœuf 100g
     - Bœuf 150g
     - Poulet pané
  3. Garnitures (retrait autorisé)
     - Salade oui/non
     - Tomate oui/non
     - Oignon oui/non
     - Cheddar oui/non
  4. Sauce (1-2 choix)
     - Burger maison
     - Samouraï
     - Ketchup
  5. Extras (optionnel)
     - Bacon +1€
     - Œuf +0.80€
```

---

## ✅ VALIDATION POINTS

Avant de passer à la phase 2 (migrations), vérifier:

- [ ] **Modélisation BDD** comprise et validée
- [ ] **Relations** entre tables claires
- [ ] **Rétrocompatibilité** garantie
- [ ] **Liberté admin** sur les noms/options confirmée
- [ ] **Exemples concrets** correspondent aux attentes
- [ ] **Feature flag** approche validée

---

## 📁 FICHIERS CRÉÉS

1. ✅ `CUISINE_TYPES_PROJECT.md` - Plan général et tracking
2. ✅ `DATABASE_MODELING.md` - Modélisation BDD complète
3. ✅ `ARCHITECTURE_RESUME.md` - Ce fichier (résumé visuel)

---

## 🔜 PROCHAINES ÉTAPES (après validation)

1. Créer les migrations SQL (UP + DOWN)
2. Créer les seeders (types, étapes, options)
3. Créer les repositories (CuisineTypeRepository, RestaurantCuisineRepository)
4. Créer les API endpoints
5. Créer l'interface admin

**⚠️ RIEN NE SERA CODÉ SANS TON GO ! ⚠️**
