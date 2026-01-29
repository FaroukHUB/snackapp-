-- =============================================
-- Migration: Ajouter is_featured aux produits
-- Date: 2026-01-29
-- Description: Permet de marquer des produits comme "Sélection pour vous"
-- =============================================

-- Ajouter colonne is_featured à products
ALTER TABLE `products`
ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`,
ADD INDEX `idx_featured` (`restaurant_id`, `is_featured`);

-- Table pour les paramètres de la section featured
CREATE TABLE IF NOT EXISTS `featured_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `title` VARCHAR(100) DEFAULT 'Sélection pour vous',
  `subtitle` VARCHAR(255) DEFAULT 'Nos produits les plus appréciés',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_restaurant` (`restaurant_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Paramètres section Featured/Sélection pour vous';

-- Insérer paramètres par défaut pour les restaurants existants
INSERT INTO `featured_settings` (`restaurant_id`, `enabled`, `title`, `subtitle`)
SELECT `id`, 1, 'Sélection pour vous', 'Nos produits les plus appréciés'
FROM `restaurants`
WHERE `deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `enabled` = `enabled`;
