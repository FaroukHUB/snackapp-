-- =============================================
-- Migration: Créer table formules
-- Date: 2026-01-26
-- Description: Stocker les formules en base MySQL
-- =============================================

CREATE TABLE IF NOT EXISTS `formules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) DEFAULT NULL COMMENT 'Prix avant réduction',
  `savings` DECIMAL(10,2) DEFAULT NULL COMMENT 'Économie réalisée',
  `badge` VARCHAR(50) DEFAULT NULL COMMENT 'Badge promo (ex: -20%)',
  `includes` JSON DEFAULT NULL COMMENT 'Ce qui est inclus dans la formule',
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_restaurant_status` (`restaurant_id`, `status`),
  INDEX `idx_sort` (`restaurant_id`, `sort_order`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Formules / menus combinés';
