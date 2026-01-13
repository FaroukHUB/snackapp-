-- =============================================
-- Migration: Ajout features menu (icons, flavor, soft delete)
-- Date: 2026-01-06
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- Modification table categories
-- ---------------------------------------------
ALTER TABLE `categories`
  ADD COLUMN `icon` VARCHAR(50) DEFAULT 'fa-utensils' COMMENT 'FontAwesome icon' AFTER `description`,
  ADD COLUMN `flavor` ENUM('sale', 'sucre') DEFAULT NULL COMMENT 'Type pour assignment auto suppléments' AFTER `icon`,
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete' AFTER `is_active`,
  ADD INDEX `idx_deleted` (`deleted_at`);

-- ---------------------------------------------
-- Modification table products
-- ---------------------------------------------
ALTER TABLE `products`
  ADD COLUMN `deleted_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Soft delete' AFTER `status`,
  ADD INDEX `idx_deleted` (`deleted_at`);

-- ---------------------------------------------
-- Modification table supplements
-- ---------------------------------------------
ALTER TABLE `supplements`
  ADD COLUMN `slug` VARCHAR(100) DEFAULT NULL COMMENT 'ID unique ex: sup-nutella' AFTER `name`,
  ADD COLUMN `type` ENUM('sale', 'sucre', 'both') DEFAULT 'both' COMMENT 'Type de supplément' AFTER `slug`,
  ADD UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`);

-- ---------------------------------------------
-- Table: category_supplements (N:N)
-- Assignment automatique des suppléments par catégorie
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `category_supplements` (
  `category_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`, `supplement_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Assignment auto suppléments selon flavor catégorie';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- Fin migration
-- =============================================
