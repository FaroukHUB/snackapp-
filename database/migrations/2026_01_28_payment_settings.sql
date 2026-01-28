-- =============================================
-- Migration: Table payment_settings
-- Date: 2026-01-28
-- =============================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `cash_enabled` TINYINT(1) DEFAULT 1,
  `card_on_delivery_enabled` TINYINT(1) DEFAULT 0,
  `online_payment_enabled` TINYINT(1) DEFAULT 0,
  `stripe_mode` ENUM('test', 'live') DEFAULT 'test',
  `stripe_public_key_test` VARCHAR(255) DEFAULT NULL,
  `stripe_secret_key_test` VARCHAR(255) DEFAULT NULL,
  `stripe_public_key_live` VARCHAR(255) DEFAULT NULL,
  `stripe_secret_key_live` VARCHAR(255) DEFAULT NULL,
  `stripe_webhook_secret` VARCHAR(255) DEFAULT NULL,
  `require_payment_upfront` TINYINT(1) DEFAULT 0,
  `allow_partial_payment` TINYINT(1) DEFAULT 0,
  `partial_payment_percent` INT DEFAULT 30,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_restaurant` (`restaurant_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les paramètres par défaut pour le restaurant 3
INSERT IGNORE INTO `payment_settings` (restaurant_id, cash_enabled, card_on_delivery_enabled, online_payment_enabled)
VALUES (3, 1, 1, 0);
