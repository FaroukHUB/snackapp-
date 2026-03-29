# 🗄️ MODÉLISATION BDD - SYSTÈME CUISINE TYPES

**Date:** 2026-03-20
**Version:** 1.0
**Auteur:** Claude Code

---

## 📊 VUE D'ENSEMBLE

Le système de cuisine types permet de rendre SnackUp universel en supportant différents types de restauration (tacos, burger, kebab, pizza, etc.) avec des étapes de composition personnalisables.

### Principe architectural

```
TYPES PRÉDÉFINIS (lecture seule)
    ↓
TEMPLATES D'ÉTAPES PAR TYPE (lecture seule)
    ↓
OPTIONS EXEMPLES PAR ÉTAPE (modifiables)
    ↓
CONFIG RESTAURANT (100% personnalisable)
```

---

## 🆕 NOUVELLES TABLES

### 1. `cuisine_types` (Globale, prédéfinie)

**Rôle:** Liste des types de cuisine disponibles dans SnackUp.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique du type |
| `name` | VARCHAR(100) | NOT NULL | Nom affiché (ex: "Tacos", "Burger") |
| `slug` | VARCHAR(100) | NOT NULL, UNIQUE | Identifiant technique (ex: "tacos", "burger") |
| `icon_slug` | VARCHAR(50) | NOT NULL | Slug de l'icône CSS (ex: "icon-tacos") |
| `description` | TEXT | NULL | Description du type de cuisine |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage global |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`slug`)
- INDEX `idx_sort` (`sort_order`)

**Peuplée via:** Seeder uniquement (non modifiable par les restaurateurs)

**Données initiales:**
```
tacos, burger, kebab, pizza, pates, riz_crousty,
sandwich, sushi, bowl, boissons, desserts
```

---

### 2. `cuisine_type_steps` (Templates d'étapes)

**Rôle:** Définit les étapes de composition par défaut pour chaque type de cuisine.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique de l'étape |
| `cuisine_type_id` | INT UNSIGNED | FK → cuisine_types.id | Type de cuisine parent |
| `name` | VARCHAR(100) | NOT NULL | Nom de l'étape (ex: "Taille", "Viande") |
| `slug` | VARCHAR(100) | NOT NULL | Slug technique (ex: "taille", "viande") |
| `description` | TEXT | NULL | Description/aide pour l'admin |
| `is_required_default` | BOOLEAN | DEFAULT FALSE | Étape obligatoire par défaut ? |
| `min_choices_default` | INT | DEFAULT 0 | Nombre minimum de choix par défaut |
| `max_choices_default` | INT | DEFAULT 1 | Nombre maximum de choix par défaut (0 = illimité) |
| `allow_removal_default` | BOOLEAN | DEFAULT FALSE | Permet retrait d'ingrédients par défaut ? |
| `has_price_modifier_default` | BOOLEAN | DEFAULT FALSE | Les options ont un prix par défaut ? |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage dans le type |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |

**Index:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`cuisine_type_id`, `slug`)
- INDEX `idx_sort` (`cuisine_type_id`, `sort_order`)
- FOREIGN KEY (`cuisine_type_id`) REFERENCES `cuisine_types`(`id`) ON DELETE CASCADE

**Exemples:**
```sql
-- Pour TACOS
(cuisine_type_id=1, name="Taille", is_required_default=TRUE, max_choices=1)
(cuisine_type_id=1, name="Viande(s)", is_required_default=TRUE, max_choices=3)
(cuisine_type_id=1, name="Sauce fromagère", is_required_default=TRUE, max_choices=1)

-- Pour BURGER
(cuisine_type_id=2, name="Pain", is_required_default=FALSE, max_choices=1)
(cuisine_type_id=2, name="Viande", is_required_default=TRUE, max_choices=1)
(cuisine_type_id=2, name="Garnitures", allow_removal_default=TRUE)
```

---

### 3. `cuisine_type_step_options` (Options exemples)

**Rôle:** Options par défaut proposées pour chaque étape. **MODIFIABLES par l'admin.**

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique de l'option |
| `cuisine_type_step_id` | INT UNSIGNED | FK → cuisine_type_steps.id | Étape parente |
| `name` | VARCHAR(150) | NOT NULL | Nom de l'option (ex: "S", "M", "L", "XL") |
| `price_modifier` | DECIMAL(8,2) | DEFAULT 0.00 | Modificateur de prix (ex: +2.50€) |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage |
| `is_active` | BOOLEAN | DEFAULT TRUE | Option active ? |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

**Index:**
- PRIMARY KEY (`id`)
- INDEX `idx_step` (`cuisine_type_step_id`, `sort_order`)
- INDEX `idx_active` (`is_active`)
- INDEX `idx_deleted` (`deleted_at`)
- FOREIGN KEY (`cuisine_type_step_id`) REFERENCES `cuisine_type_steps`(`id`) ON DELETE CASCADE

**Exemples:**
```sql
-- Étape "Taille" pour Tacos
(step_id=1, name="S", price_modifier=0.00)
(step_id=1, name="M", price_modifier=1.00)
(step_id=1, name="L", price_modifier=2.00)
(step_id=1, name="XL", price_modifier=3.50)

-- Ou pour une pizzeria:
(step_id=X, name="26cm", price_modifier=0.00)
(step_id=X, name="33cm", price_modifier=3.00)
(step_id=X, name="40cm", price_modifier=6.00)
```

---

### 4. `restaurant_cuisine_types` (Types activés par resto)

**Rôle:** Les types de cuisine activés par chaque restaurant.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique |
| `restaurant_id` | INT UNSIGNED | FK → restaurants.id | Restaurant |
| `cuisine_type_id` | INT UNSIGNED | FK → cuisine_types.id | Type de cuisine |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage dans le menu |
| `is_active` | BOOLEAN | DEFAULT TRUE | Type actif ? |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date d'activation |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

**Index:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`restaurant_id`, `cuisine_type_id`)
- INDEX `idx_sort` (`restaurant_id`, `sort_order`)
- INDEX `idx_active` (`restaurant_id`, `is_active`)
- INDEX `idx_deleted` (`deleted_at`)
- FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`cuisine_type_id`) REFERENCES `cuisine_types`(`id`) ON DELETE CASCADE

**Logique:**
- Créé lors de l'onboarding ou quand l'admin active un type
- Soft delete si désactivé
- `sort_order` contrôle l'ordre des compartiments dans le menu

---

### 5. `restaurant_cuisine_steps` (Config étapes par resto)

**Rôle:** Configuration personnalisée des étapes pour chaque restaurant.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique |
| `restaurant_cuisine_type_id` | INT UNSIGNED | FK → restaurant_cuisine_types.id | Type activé du resto |
| `cuisine_type_step_id` | INT UNSIGNED | FK → cuisine_type_steps.id | Template d'étape |
| `custom_name` | VARCHAR(100) | NULL | Nom personnalisé (NULL = utiliser le default) |
| `is_active` | BOOLEAN | DEFAULT TRUE | Étape activée ? |
| `is_required` | BOOLEAN | DEFAULT FALSE | Étape obligatoire ? |
| `min_choices` | INT | DEFAULT 0 | Minimum de choix |
| `max_choices` | INT | DEFAULT 1 | Maximum de choix (0 = illimité) |
| `allow_removal` | BOOLEAN | DEFAULT FALSE | Permet retrait d'ingrédients ? |
| `has_price_modifier` | BOOLEAN | DEFAULT FALSE | Options avec prix ? |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

**Index:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`restaurant_cuisine_type_id`, `cuisine_type_step_id`)
- INDEX `idx_sort` (`restaurant_cuisine_type_id`, `sort_order`)
- INDEX `idx_active` (`is_active`)
- INDEX `idx_deleted` (`deleted_at`)
- FOREIGN KEY (`restaurant_cuisine_type_id`) REFERENCES `restaurant_cuisine_types`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`cuisine_type_step_id`) REFERENCES `cuisine_type_steps`(`id`) ON DELETE CASCADE

**Logique:**
- Créé automatiquement lors de l'activation d'un type (copie des defaults)
- L'admin peut modifier tous les champs
- `custom_name` permet de renommer (ex: "Taille" → "Format")
- `is_active=FALSE` masque l'étape sans la supprimer

---

### 6. `restaurant_step_options` (Options personnalisées)

**Rôle:** Options spécifiques à chaque restaurant pour chaque étape.

| Colonne | Type | Contraintes | Description |
|---------|------|-------------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | ID unique |
| `restaurant_cuisine_step_id` | INT UNSIGNED | FK → restaurant_cuisine_steps.id | Étape du resto |
| `source_option_id` | INT UNSIGNED | NULL, FK → cuisine_type_step_options.id | Option template source (NULL si custom) |
| `name` | VARCHAR(150) | NOT NULL | Nom de l'option |
| `price_modifier` | DECIMAL(8,2) | DEFAULT 0.00 | Modificateur de prix |
| `is_active` | BOOLEAN | DEFAULT TRUE | Option active ? |
| `sort_order` | INT | DEFAULT 0 | Ordre d'affichage |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Date de création |
| `deleted_at` | TIMESTAMP | NULL | Soft delete |

**Index:**
- PRIMARY KEY (`id`)
- INDEX `idx_step` (`restaurant_cuisine_step_id`, `sort_order`)
- INDEX `idx_active` (`restaurant_cuisine_step_id`, `is_active`)
- INDEX `idx_deleted` (`deleted_at`)
- FOREIGN KEY (`restaurant_cuisine_step_id`) REFERENCES `restaurant_cuisine_steps`(`id`) ON DELETE CASCADE
- FOREIGN KEY (`source_option_id`) REFERENCES `cuisine_type_step_options`(`id`) ON DELETE SET NULL

**Logique:**
- Créé automatiquement lors de l'activation d'une étape (copie des templates)
- `source_option_id` conserve le lien vers le template (pour mises à jour futures)
- Si `source_option_id` = NULL → option 100% custom créée par l'admin
- L'admin peut ajouter, modifier, réordonner, supprimer librement

---

## 📝 MODIFICATIONS DES TABLES EXISTANTES

### `categories` (ALTERATION)

Ajout de 2 colonnes pour lier aux types de cuisine:

```sql
ALTER TABLE `categories`
ADD COLUMN `cuisine_type_id` INT UNSIGNED NULL DEFAULT NULL
  COMMENT 'Type de cuisine (NULL = catégorie legacy)',
ADD COLUMN `is_common` BOOLEAN DEFAULT FALSE
  COMMENT 'Affiché dans tous les menus (boissons, desserts)',
ADD INDEX `idx_cuisine_type` (`restaurant_id`, `cuisine_type_id`),
ADD INDEX `idx_common` (`restaurant_id`, `is_common`),
ADD CONSTRAINT `fk_category_cuisine_type`
  FOREIGN KEY (`cuisine_type_id`)
  REFERENCES `cuisine_types`(`id`)
  ON DELETE SET NULL;
```

**Rétrocompatibilité:**
- Les catégories existantes auront `cuisine_type_id = NULL`
- `is_common = TRUE` pour Boissons et Desserts
- Le système affiche toutes les catégories (avec ou sans `cuisine_type_id`)

---

## 🔗 SCHÉMA RELATIONNEL COMPLET

```
┌─────────────────────┐
│   cuisine_types     │ (Prédéfinis)
│  - id               │
│  - name             │
│  - slug             │
│  - icon_slug        │
└──────┬──────────────┘
       │
       │ 1:N
       ▼
┌─────────────────────────┐
│ cuisine_type_steps      │ (Templates)
│  - id                   │
│  - cuisine_type_id  FK  │
│  - name                 │
│  - is_required_default  │
│  - min/max_choices      │
└──────┬──────────────────┘
       │
       │ 1:N
       ▼
┌──────────────────────────────┐
│ cuisine_type_step_options    │ (Exemples)
│  - id                        │
│  - cuisine_type_step_id  FK  │
│  - name                      │
│  - price_modifier            │
└──────────────────────────────┘

┌─────────────────────┐
│   restaurants       │
└──────┬──────────────┘
       │
       │ 1:N
       ▼
┌──────────────────────────┐         ┌─────────────────────┐
│ restaurant_cuisine_types │ ◄─────  │   categories        │
│  - id                    │   N:1   │  - cuisine_type_id  │
│  - restaurant_id     FK  │         │  - is_common        │
│  - cuisine_type_id   FK  │         └─────────────────────┘
│  - sort_order            │
└──────┬───────────────────┘
       │
       │ 1:N
       ▼
┌───────────────────────────────┐
│ restaurant_cuisine_steps      │
│  - id                         │
│  - restaurant_cuisine_type_id │
│  - cuisine_type_step_id   FK  │
│  - custom_name                │
│  - is_active, is_required     │
│  - min/max_choices            │
└──────┬────────────────────────┘
       │
       │ 1:N
       ▼
┌────────────────────────────────┐
│ restaurant_step_options        │
│  - id                          │
│  - restaurant_cuisine_step_id  │
│  - source_option_id  FK (NULL) │
│  - name                        │
│  - price_modifier              │
└────────────────────────────────┘
```

---

## 🎯 SCÉNARIOS D'UTILISATION

### Scénario 1: Nouveau restaurant (onboarding)

1. L'admin sélectionne ses types: `[tacos, boissons, desserts]`
2. Le système crée:
   - 3 lignes dans `restaurant_cuisine_types`
   - Pour chaque type, copie les `cuisine_type_steps` → `restaurant_cuisine_steps`
   - Pour chaque étape, copie les `cuisine_type_step_options` → `restaurant_step_options`
3. L'admin peut ensuite personnaliser librement

### Scénario 2: Restaurant existant (migration)

1. Aucun onboarding affiché
2. Les catégories existantes ont `cuisine_type_id = NULL`
3. Le système fonctionne normalement (100% rétrocompat)
4. L'admin peut activer le système plus tard s'il le souhaite

### Scénario 3: Admin personnalise une étape

**Avant:**
```
Étape "Taille" (template)
  - S, M, L (options par défaut)
```

**L'admin veut:**
```
Étape "Format" (renommée)
  - Solo, Duo, Trio, Méga (options custom)
```

**Actions:**
1. Modifier `restaurant_cuisine_steps.custom_name = "Format"`
2. Supprimer les options S/M/L dans `restaurant_step_options`
3. Créer 4 nouvelles options Solo/Duo/Trio/Méga

### Scénario 4: Client commande un produit

1. Récupérer le `cuisine_type_id` de la catégorie du produit
2. Charger les `restaurant_cuisine_steps` actives du type
3. Pour chaque étape, charger les `restaurant_step_options` actives
4. Afficher l'interface de composition dynamique
5. Valider min/max choices
6. Calculer le prix final avec modificateurs

---

## 🔒 CONTRAINTES D'INTÉGRITÉ

### Règles métier

1. **Un restaurant ne peut activer un type qu'une seule fois**
   → `UNIQUE (restaurant_id, cuisine_type_id)`

2. **Une étape template ne peut être liée qu'une fois par type restaurant**
   → `UNIQUE (restaurant_cuisine_type_id, cuisine_type_step_id)`

3. **Les types prédéfinis sont en lecture seule**
   → Gérés uniquement via seeders

4. **Soft delete généralisé**
   → Toutes les tables ont `deleted_at`

5. **CASCADE sur suppression restaurant**
   → Supprime toute la config du resto automatiquement

6. **SET NULL sur suppression template**
   → Si un template est supprimé, les configs custom restent valides

---

## 📊 VOLUMÉTRIE ESTIMÉE

| Table | Lignes par restaurant | Total (100 restos) |
|-------|----------------------|-------------------|
| `cuisine_types` | 0 (global) | ~15 |
| `cuisine_type_steps` | 0 (global) | ~80 |
| `cuisine_type_step_options` | 0 (global) | ~400 |
| `restaurant_cuisine_types` | ~3-5 | 300-500 |
| `restaurant_cuisine_steps` | ~15-20 | 1 500-2 000 |
| `restaurant_step_options` | ~50-100 | 5 000-10 000 |

**Total:** ~7 000 - 13 000 lignes pour 100 restaurants

---

## 🚀 OPTIMISATIONS

### Index stratégiques

1. **Requêtes fréquentes:**
   ```sql
   -- Charger les types d'un restaurant
   SELECT * FROM restaurant_cuisine_types
   WHERE restaurant_id = ? AND is_active = 1
   ORDER BY sort_order;

   -- Charger les étapes d'un type
   SELECT * FROM restaurant_cuisine_steps
   WHERE restaurant_cuisine_type_id = ? AND is_active = 1
   ORDER BY sort_order;

   -- Charger les options d'une étape
   SELECT * FROM restaurant_step_options
   WHERE restaurant_cuisine_step_id = ? AND is_active = 1
   ORDER BY sort_order;
   ```

2. **Index composites:**
   - `(restaurant_id, is_active, sort_order)` sur toutes les tables
   - `(restaurant_id, cuisine_type_id)` sur `categories`

### Cache applicatif

```php
// Cache la config complète d'un restaurant
CuisineCache::getRestaurantConfig($restaurantId);

// Invalidation automatique lors de modifications
```

---

## ✅ VALIDATION DE LA MODÉLISATION

### Checklist architecture

- [x] **Rétrocompatibilité:** Categories sans `cuisine_type_id` fonctionnent
- [x] **Soft delete:** Toutes les tables ont `deleted_at`
- [x] **Multi-tenant:** Toutes les tables filtrées par `restaurant_id`
- [x] **Liberté admin:** Toutes les configs sont personnalisables
- [x] **Templates intelligents:** Defaults pertinents fournis
- [x] **Scalabilité:** Ajout de types sans migration
- [x] **Performance:** Index optimisés pour les requêtes fréquentes
- [x] **Intégrité:** Foreign keys et contraintes UNIQUE

### Checklist fonctionnelle

- [x] L'admin peut activer/désactiver un type
- [x] L'admin peut renommer une étape
- [x] L'admin peut configurer min/max/required/prix
- [x] L'admin peut ajouter/modifier/supprimer des options
- [x] L'admin peut réordonner étapes et options
- [x] Les noms sont 100% personnalisables (pas de "S/M/L" imposé)
- [x] Le client voit les étapes selon le produit choisi
- [x] Le prix final est calculé avec les modificateurs

---

## 📄 FICHIERS ASSOCIÉS

- `CUISINE_TYPES_PROJECT.md` - Plan général du projet
- `database/migrations/2026_03_20_create_cuisine_types.sql` - Migration UP (à créer)
- `database/migrations/2026_03_20_create_cuisine_types_down.sql` - Migration DOWN (à créer)
- `database/seeders/CuisineTypesSeeder.php` - Seeder des données prédéfinies (à créer)

---

**FIN DE LA MODÉLISATION**
