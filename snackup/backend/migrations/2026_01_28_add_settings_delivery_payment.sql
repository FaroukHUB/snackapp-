-- =============================================
-- Migration: Settings, Delivery Zones, Payment
-- Date: 2026-01-28
-- Description: Supprime tout le hardcodé du panier
-- =============================================

SET NAMES utf8mb4;

-- ---------------------------------------------
-- 1. Étendre restaurant_settings
-- ---------------------------------------------
ALTER TABLE `restaurant_settings`
  ADD COLUMN IF NOT EXISTS `currency_code` VARCHAR(5) DEFAULT 'EUR' COMMENT 'Code devise: EUR, DA, USD',
  ADD COLUMN IF NOT EXISTS `currency_symbol` VARCHAR(5) DEFAULT '€' COMMENT 'Symbole: €, DA, $',
  ADD COLUMN IF NOT EXISTS `currency_position` ENUM('before', 'after') DEFAULT 'after' COMMENT 'Position symbole: 10€ ou €10',
  ADD COLUMN IF NOT EXISTS `country_code` VARCHAR(5) DEFAULT '+33' COMMENT 'Indicatif pays: +33, +213',
  ADD COLUMN IF NOT EXISTS `country_name` VARCHAR(50) DEFAULT 'France' COMMENT 'Nom du pays',
  ADD COLUMN IF NOT EXISTS `loyalty_euro_per_point` DECIMAL(8,2) DEFAULT 10.00 COMMENT 'Combien € pour 1 point (ex: 10€ = 1 point)',
  ADD COLUMN IF NOT EXISTS `loyalty_point_value` DECIMAL(8,2) DEFAULT 1.00 COMMENT 'Valeur de 1 point en € pour réduction',
  ADD COLUMN IF NOT EXISTS `delivery_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Livraison activée?',
  ADD COLUMN IF NOT EXISTS `pickup_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Click & Collect activé?',
  ADD COLUMN IF NOT EXISTS `min_order_amount` DECIMAL(8,2) DEFAULT 0.00 COMMENT 'Montant minimum commande',
  ADD COLUMN IF NOT EXISTS `free_delivery_threshold` DECIMAL(8,2) DEFAULT NULL COMMENT 'Livraison gratuite à partir de X€';

-- ---------------------------------------------
-- 2. Table: delivery_zones (frais par distance)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `delivery_zones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL COMMENT 'Nom de la zone: Centre-ville, Périphérie',
  `min_distance_km` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Distance min en km',
  `max_distance_km` DECIMAL(5,2) NOT NULL COMMENT 'Distance max en km (999 = illimité)',
  `delivery_fee` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais de livraison',
  `min_order_amount` DECIMAL(8,2) DEFAULT NULL COMMENT 'Montant min pour cette zone (override global)',
  `estimated_time_min` INT UNSIGNED DEFAULT 30 COMMENT 'Temps estimé en minutes',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_restaurant` (`restaurant_id`, `is_active`, `sort_order`),
  INDEX `idx_distance` (`restaurant_id`, `min_distance_km`, `max_distance_km`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 3. Table: payment_settings (config paiement)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,

  -- Méthodes activées
  `cash_enabled` TINYINT(1) DEFAULT 1 COMMENT 'Paiement espèces',
  `card_on_delivery_enabled` TINYINT(1) DEFAULT 0 COMMENT 'CB à la livraison (TPE)',
  `online_payment_enabled` TINYINT(1) DEFAULT 0 COMMENT 'Paiement en ligne (Stripe)',

  -- Stripe
  `stripe_mode` ENUM('test', 'live') DEFAULT 'test',
  `stripe_public_key_test` VARCHAR(255) DEFAULT NULL,
  `stripe_secret_key_test` VARCHAR(255) DEFAULT NULL,
  `stripe_public_key_live` VARCHAR(255) DEFAULT NULL,
  `stripe_secret_key_live` VARCHAR(255) DEFAULT NULL,
  `stripe_webhook_secret` VARCHAR(255) DEFAULT NULL,

  -- Options paiement
  `require_payment_upfront` TINYINT(1) DEFAULT 0 COMMENT 'Paiement obligatoire avant commande?',
  `allow_partial_payment` TINYINT(1) DEFAULT 0 COMMENT 'Acompte possible?',
  `partial_payment_percent` TINYINT UNSIGNED DEFAULT 30 COMMENT 'Pourcentage acompte',

  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_restaurant` (`restaurant_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- 4. Insert default payment_settings pour restaurants existants
-- ---------------------------------------------
INSERT IGNORE INTO `payment_settings` (`restaurant_id`, `cash_enabled`)
SELECT `id`, 1 FROM `restaurants`;

-- ---------------------------------------------
-- 5. Insert default delivery_zones exemple
-- (L'admin les configurera via l'interface)
-- ---------------------------------------------
-- Zone 1: 0-3km gratuit
-- Zone 2: 3-5km = 2€
-- Zone 3: 5-10km = 5€
-- Zone 4: >10km = pas de livraison (non créée)
