-- =============================================
-- Migration: Livraison par VILLE (pas par distance)
-- Date: 2026-01-28
-- =============================================

SET NAMES utf8mb4;

-- Supprimer l'ancienne table si elle existe
DROP TABLE IF EXISTS `delivery_zones`;

-- Nouvelle table: livraison par ville
CREATE TABLE IF NOT EXISTS `delivery_cities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `city_name` VARCHAR(100) NOT NULL COMMENT 'Nom de la ville',
  `postal_code` VARCHAR(10) DEFAULT NULL COMMENT 'Code postal (optionnel)',
  `delivery_fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais de livraison',
  `min_order_amount` DECIMAL(8,2) DEFAULT NULL COMMENT 'Montant min commande pour cette ville',
  `estimated_time_min` INT UNSIGNED DEFAULT 30 COMMENT 'Temps estimé en minutes',
  `is_active` TINYINT(1) DEFAULT 1 COMMENT '1=livre cette ville, 0=non',
  `is_home_city` TINYINT(1) DEFAULT 0 COMMENT '1=ville du restaurant',
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_restaurant` (`restaurant_id`, `is_active`),
  INDEX `idx_city` (`restaurant_id`, `city_name`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
