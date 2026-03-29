-- ============================================================================
-- MIGRATION: Système de cuisine types pour SnackUp universel
-- Date: 2026-03-20
-- Description: Création de 6 tables pour gérer les types de cuisine
--              (tacos, burger, kebab, pizza, etc.) avec étapes personnalisables
-- ============================================================================

-- ============================================================================
-- SECTION UP: CRÉATION DES TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- TABLE 1: cuisine_types (Globale, prédéfinie)
-- Rôle: Liste des types de cuisine disponibles dans SnackUp
-- ----------------------------------------------------------------------------
CREATE TABLE `cuisine_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Nom affiché (ex: "Tacos", "Burger")',
  `slug` VARCHAR(100) NOT NULL COMMENT 'Identifiant technique (ex: "tacos", "burger")',
  `icon_slug` VARCHAR(50) NOT NULL COMMENT 'Slug de l\'icône CSS (ex: "icon-tacos")',
  `description` TEXT NULL COMMENT 'Description du type de cuisine',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage global',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Types de cuisine prédéfinis (tacos, burger, kebab, etc.)';

-- ----------------------------------------------------------------------------
-- TABLE 2: cuisine_type_steps (Templates d'étapes)
-- Rôle: Définit les étapes de composition par défaut pour chaque type
-- ----------------------------------------------------------------------------
CREATE TABLE `cuisine_type_steps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cuisine_type_id` INT UNSIGNED NOT NULL COMMENT 'Type de cuisine parent',
  `name` VARCHAR(100) NOT NULL COMMENT 'Nom de l\'étape (ex: "Taille", "Viande")',
  `slug` VARCHAR(100) NOT NULL COMMENT 'Slug technique (ex: "taille", "viande")',
  `description` TEXT NULL COMMENT 'Description/aide pour l\'admin',
  `is_required_default` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Étape obligatoire par défaut ?',
  `min_choices_default` INT NOT NULL DEFAULT 0 COMMENT 'Nombre minimum de choix par défaut',
  `max_choices_default` INT NOT NULL DEFAULT 1 COMMENT 'Nombre maximum de choix par défaut (0 = illimité)',
  `allow_removal_default` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Permet retrait d\'ingrédients par défaut ?',
  `has_price_modifier_default` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Les options ont un prix par défaut ?',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage dans le type',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_slug` (`cuisine_type_id`, `slug`),
  INDEX `idx_sort` (`cuisine_type_id`, `sort_order`),

  CONSTRAINT `fk_step_cuisine_type`
    FOREIGN KEY (`cuisine_type_id`)
    REFERENCES `cuisine_types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Templates d\'étapes de composition par type de cuisine';

-- ----------------------------------------------------------------------------
-- TABLE 3: cuisine_type_step_options (Options exemples)
-- Rôle: Options par défaut proposées pour chaque étape (MODIFIABLES)
-- ----------------------------------------------------------------------------
CREATE TABLE `cuisine_type_step_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cuisine_type_step_id` INT UNSIGNED NOT NULL COMMENT 'Étape parente',
  `name` VARCHAR(150) NOT NULL COMMENT 'Nom de l\'option (ex: "S", "M", "L", "XL")',
  `price_modifier` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Modificateur de prix (ex: +2.50€)',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Option active ?',
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',

  PRIMARY KEY (`id`),
  INDEX `idx_step` (`cuisine_type_step_id`, `sort_order`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_deleted` (`deleted_at`),

  CONSTRAINT `fk_option_step`
    FOREIGN KEY (`cuisine_type_step_id`)
    REFERENCES `cuisine_type_steps` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Options exemples par défaut pour chaque étape template';

-- ----------------------------------------------------------------------------
-- TABLE 4: restaurant_cuisine_types (Types activés par resto)
-- Rôle: Les types de cuisine activés par chaque restaurant
-- ----------------------------------------------------------------------------
CREATE TABLE `restaurant_cuisine_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_id` INT UNSIGNED NOT NULL COMMENT 'Restaurant',
  `cuisine_type_id` INT UNSIGNED NOT NULL COMMENT 'Type de cuisine',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage dans le menu',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Type actif ?',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_restaurant_type` (`restaurant_id`, `cuisine_type_id`),
  INDEX `idx_sort` (`restaurant_id`, `sort_order`),
  INDEX `idx_active` (`restaurant_id`, `is_active`),
  INDEX `idx_deleted` (`deleted_at`),

  CONSTRAINT `fk_resto_cuisine_restaurant`
    FOREIGN KEY (`restaurant_id`)
    REFERENCES `restaurants` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_resto_cuisine_type`
    FOREIGN KEY (`cuisine_type_id`)
    REFERENCES `cuisine_types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Types de cuisine activés par chaque restaurant';

-- ----------------------------------------------------------------------------
-- TABLE 5: restaurant_cuisine_steps (Config étapes par resto)
-- Rôle: Configuration personnalisée des étapes pour chaque restaurant
-- ----------------------------------------------------------------------------
CREATE TABLE `restaurant_cuisine_steps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_cuisine_type_id` INT UNSIGNED NOT NULL COMMENT 'Type activé du resto',
  `cuisine_type_step_id` INT UNSIGNED NOT NULL COMMENT 'Template d\'étape',
  `custom_name` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nom personnalisé (NULL = utiliser le default)',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Étape activée ?',
  `is_required` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Étape obligatoire ?',
  `min_choices` INT NOT NULL DEFAULT 0 COMMENT 'Minimum de choix',
  `max_choices` INT NOT NULL DEFAULT 1 COMMENT 'Maximum de choix (0 = illimité)',
  `allow_removal` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Permet retrait d\'ingrédients ?',
  `has_price_modifier` BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Options avec prix ?',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_resto_type_step` (`restaurant_cuisine_type_id`, `cuisine_type_step_id`),
  INDEX `idx_sort` (`restaurant_cuisine_type_id`, `sort_order`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_deleted` (`deleted_at`),

  CONSTRAINT `fk_resto_step_resto_type`
    FOREIGN KEY (`restaurant_cuisine_type_id`)
    REFERENCES `restaurant_cuisine_types` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_resto_step_template`
    FOREIGN KEY (`cuisine_type_step_id`)
    REFERENCES `cuisine_type_steps` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuration personnalisée des étapes par restaurant';

-- ----------------------------------------------------------------------------
-- TABLE 6: restaurant_step_options (Options personnalisées)
-- Rôle: Options spécifiques à chaque restaurant pour chaque étape
-- ----------------------------------------------------------------------------
CREATE TABLE `restaurant_step_options` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurant_cuisine_step_id` INT UNSIGNED NOT NULL COMMENT 'Étape du resto',
  `source_option_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Option template source (NULL si custom)',
  `name` VARCHAR(150) NOT NULL COMMENT 'Nom de l\'option',
  `price_modifier` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Modificateur de prix',
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Option active ?',
  `sort_order` INT NOT NULL DEFAULT 0 COMMENT 'Ordre d\'affichage',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete',

  PRIMARY KEY (`id`),
  INDEX `idx_step` (`restaurant_cuisine_step_id`, `sort_order`),
  INDEX `idx_active` (`restaurant_cuisine_step_id`, `is_active`),
  INDEX `idx_deleted` (`deleted_at`),

  CONSTRAINT `fk_resto_option_step`
    FOREIGN KEY (`restaurant_cuisine_step_id`)
    REFERENCES `restaurant_cuisine_steps` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,

  CONSTRAINT `fk_resto_option_source`
    FOREIGN KEY (`source_option_id`)
    REFERENCES `cuisine_type_step_options` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Options personnalisées par restaurant pour chaque étape';

-- ----------------------------------------------------------------------------
-- MODIFICATION TABLE EXISTANTE: categories
-- Ajout de colonnes pour lier aux types de cuisine
-- ----------------------------------------------------------------------------
ALTER TABLE `categories`
  ADD COLUMN `cuisine_type_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Type de cuisine (NULL = catégorie legacy)',
  ADD COLUMN `is_common` BOOLEAN NOT NULL DEFAULT FALSE
    COMMENT 'Affiché dans tous les menus (boissons, desserts)',

  ADD INDEX `idx_cuisine_type` (`restaurant_id`, `cuisine_type_id`),
  ADD INDEX `idx_common` (`restaurant_id`, `is_common`),

  ADD CONSTRAINT `fk_category_cuisine_type`
    FOREIGN KEY (`cuisine_type_id`)
    REFERENCES `cuisine_types` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- ============================================================================
-- FIN SECTION UP
-- ============================================================================


-- ============================================================================
-- SECTION DOWN: ROLLBACK (Suppression des tables)
-- ============================================================================

-- Pour exécuter le rollback, décommenter les lignes ci-dessous et exécuter:

/*
-- ----------------------------------------------------------------------------
-- ROLLBACK: Suppression de la contrainte et colonnes ajoutées à categories
-- ----------------------------------------------------------------------------
ALTER TABLE `categories`
  DROP FOREIGN KEY `fk_category_cuisine_type`,
  DROP INDEX `idx_cuisine_type`,
  DROP INDEX `idx_common`,
  DROP COLUMN `cuisine_type_id`,
  DROP COLUMN `is_common`;

-- ----------------------------------------------------------------------------
-- ROLLBACK: Suppression des 6 tables dans l'ordre inverse des dépendances
-- ----------------------------------------------------------------------------

-- TABLE 6: restaurant_step_options
DROP TABLE IF EXISTS `restaurant_step_options`;

-- TABLE 5: restaurant_cuisine_steps
DROP TABLE IF EXISTS `restaurant_cuisine_steps`;

-- TABLE 4: restaurant_cuisine_types
DROP TABLE IF EXISTS `restaurant_cuisine_types`;

-- TABLE 3: cuisine_type_step_options
DROP TABLE IF EXISTS `cuisine_type_step_options`;

-- TABLE 2: cuisine_type_steps
DROP TABLE IF EXISTS `cuisine_type_steps`;

-- TABLE 1: cuisine_types
DROP TABLE IF EXISTS `cuisine_types`;
*/

-- ============================================================================
-- FIN SECTION DOWN
-- ============================================================================
