# AUDIT COMPLET: Structure DB vs Code SQL

## 1. COLONNES RÉELLES EN BASE DE DONNÉES

### categories
```
id, restaurant_id, name, slug, description, icon, flavor, product_type,
image, sort_order, is_active, created_at, deleted_at
```

### products
```
id, restaurant_id, category_id, name, slug, description, image,
price_solo, price_menu, base_ingredients, status, options_config,
sort_order, created_at, updated_at, deleted_at, bundle_enabled,
bundle_quantity, bundle_price_solo, bundle_price_duo
```

### formules
```
id, restaurant_id, slug, name, description, image, price,
original_price, savings, badge, includes, status, sort_order,
created_at, updated_at, deleted_at
```

### supplements
```
id, restaurant_id, name, flavor, price, status, sort_order, deleted_at
```

## 2. ANALYSE DES FICHIERS

### ✓ snackup/backend/repositories/MenuRepository.php (UTILISÉ par config/menu.php)

**getAllCategories (L22-25):**
- SELECT: id, name, description, icon, flavor, sort_order
- ✓ TOUTES LES COLONNES EXISTENT

**getProductsByCategory (L45-47):**
- SELECT: id, name, description, image, price_solo, price_menu, status, sort_order, base_ingredients
- ✓ TOUTES LES COLONNES EXISTENT

**getAllSupplements (L75):**
- SELECT: id, name, flavor, price, status
- ✓ TOUTES LES COLONNES EXISTENT

**getAllFormules (L391-393):**
- SELECT: id, name, description, image, price, original_price, badge, includes, status, sort_order
- ✓ TOUTES LES COLONNES EXISTENT

**assignSupplementsByFlavor (L220):**
- WHERE: flavor = ?
- ✓ COLONNE EXISTE

### ✗ database/repositories/MenuRepository.php (NON utilisé par config/menu.php)

**getAllCategories (L20):**
- SELECT: id, slug, name, description, icon, flavor, sort_order
- ✓ TOUTES LES COLONNES EXISTENT

**getProductsByCategory (L43-45):**
- SELECT: id, slug, name, description, image, price_solo, price_menu, status, sort_order, base_ingredients, snackup_context
- ✓ id, slug, name, description, image, price_solo, price_menu, status, sort_order, base_ingredients EXISTENT
- ✗ **snackup_context N'EXISTE PAS** dans products

**getAllSupplements (L83):**
- SELECT: id, slug, name, price, type, status
- ✓ id, name, price, status EXISTENT
- ✗ **slug N'EXISTE PAS** dans supplements
- ✗ **type N'EXISTE PAS** dans supplements (c'est **flavor**)

**getCategorySupplements (L111):**
- SELECT: c.slug as category_slug, s.slug as supplement_slug
- ✓ c.slug EXISTE dans categories
- ✗ **s.slug N'EXISTE PAS** dans supplements

**assignSupplementsByFlavor (L199-200):**
- WHERE: type = ? OR type = 'both'
- ✗ **type N'EXISTE PAS** dans supplements (c'est **flavor**)

## 3. CORRECTIONS À EFFECTUER

### database/repositories/MenuRepository.php

1. **getProductsByCategory (L43-45):**
   - SUPPRIMER: snackup_context de SELECT
   - SUPPRIMER: lignes 65-70 (décodage snackup_context)

2. **getAllSupplements (L83):**
   - REMPLACER: slug, type → name (utiliser name comme id)
   - REMPLACER: type → flavor
   - SUPPRIMER: slug de L92 (utiliser id numérique)

3. **getCategorySupplements (L111):**
   - REMPLACER: s.slug → s.id
   - MODIFIER: logique pour utiliser les IDs au lieu de slugs

4. **assignSupplementsByFlavor (L199-200):**
   - REMPLACER: type → flavor

5. **addProduct (L276):**
   - SUPPRIMER: snackup_context du INSERT
   - SUPPRIMER: paramètre $snackupContext

6. **editProduct (L311-315):**
   - SUPPRIMER: snackup_context de l'UPDATE
   - SUPPRIMER: paramètre $snackupContext

## 4. FICHIERS IMPACTÉS

- database/repositories/MenuRepository.php (6 corrections)

## 5. FICHIERS OK (AUCUNE CORRECTION)

- snackup/backend/repositories/MenuRepository.php ✓
- config/menu.php ✓
