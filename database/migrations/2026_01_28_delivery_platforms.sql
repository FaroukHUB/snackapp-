-- =============================================
-- Migration: Table delivery_platforms
-- Date: 2026-01-28
-- Gestion des plateformes de livraison (Uber Eats, Deliveroo, etc.)
-- =============================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `delivery_platforms` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL COMMENT 'Nom icône (ubereats, deliveroo, etc.)',
  `is_enabled` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_enabled` (`restaurant_id`, `is_enabled`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter colonnes thème à restaurant_settings si manquantes
ALTER TABLE `restaurant_settings`
  ADD COLUMN IF NOT EXISTS `theme_primary` VARCHAR(7) DEFAULT '#e63946' COMMENT 'Couleur primaire',
  ADD COLUMN IF NOT EXISTS `theme_primary_dark` VARCHAR(7) DEFAULT '#d62839',
  ADD COLUMN IF NOT EXISTS `theme_secondary` VARCHAR(7) DEFAULT '#1a1a2e',
  ADD COLUMN IF NOT EXISTS `theme_accent` VARCHAR(7) DEFAULT '#ff6fae',
  ADD COLUMN IF NOT EXISTS `snapchat` VARCHAR(100) DEFAULT NULL COMMENT 'Lien Snapchat';
