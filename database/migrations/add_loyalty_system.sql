-- =============================================
-- Migration: Système de Fidélité Complet
-- Date: 2025-12-20
-- =============================================

-- 1. Ajouter colonnes loyalty_code et loyalty_points si elles n'existent pas
ALTER TABLE `customers`
    ADD COLUMN IF NOT EXISTS `loyalty_code` VARCHAR(10) DEFAULT NULL COMMENT 'Code unique ex: SNACK-A3X7',
    ADD COLUMN IF NOT EXISTS `loyalty_points` INT UNSIGNED DEFAULT 0;

-- Ajouter index unique sur loyalty_code (ignore si existe)
-- Note: Exécuter séparément si erreur
-- ALTER TABLE `customers` ADD UNIQUE KEY `uk_loyalty_code` (`loyalty_code`);

-- 2. Ajouter colonnes fidélité dans restaurant_settings
ALTER TABLE `restaurant_settings`
    ADD COLUMN IF NOT EXISTS `loyalty_enabled` TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `loyalty_points_per_euro` INT UNSIGNED DEFAULT 1;

-- 3. Créer table loyalty_rewards
CREATE TABLE IF NOT EXISTS `loyalty_rewards` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `points_required` INT UNSIGNED NOT NULL,
  `reward_type` ENUM('discount_percent', 'discount_amount', 'free_item') DEFAULT 'discount_percent',
  `reward_value` DECIMAL(8,2) DEFAULT 0.00,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_restaurant` (`restaurant_id`, `is_active`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Créer table loyalty_transactions
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT UNSIGNED NOT NULL,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `points` INT NOT NULL COMMENT 'Positif = gagné, Négatif = utilisé',
  `type` ENUM('earn', 'redeem', 'bonus', 'adjustment') NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_restaurant` (`restaurant_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Générer des codes fidélité pour les clients existants qui n'en ont pas
-- Note: Ceci est un exemple simple, en production utiliser PHP pour garantir l'unicité
UPDATE `customers`
SET `loyalty_code` = CONCAT('SNACK-', UPPER(LEFT(MD5(RAND()), 4)))
WHERE `loyalty_code` IS NULL OR `loyalty_code` = '';

-- =============================================
-- DONNÉES DE TEST (optionnel)
-- =============================================

-- Insérer des récompenses test (remplacer 1 par votre restaurant_id)
-- INSERT INTO `loyalty_rewards` (`restaurant_id`, `name`, `description`, `points_required`, `reward_type`, `reward_value`) VALUES
-- (1, 'Boisson offerte', 'Une boisson au choix offerte', 50, 'free_item', 0),
-- (1, '-10% sur commande', 'Réduction de 10% sur le total', 100, 'discount_percent', 10),
-- (1, '-5€ sur commande', 'Réduction de 5€ sur le total', 150, 'discount_amount', 5),
-- (1, 'Dessert offert', 'Un dessert au choix offert', 200, 'free_item', 0);

SELECT 'Migration terminée avec succès!' AS status;
