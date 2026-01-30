-- =============================================
-- SNACKAPP v2 - MySQL Schema Complet
-- Migration JSON vers MySQL avec Soft Delete
-- Date: 2026-01-16
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. RESTAURANTS & CONFIGURATION
-- =============================================

-- ---------------------------------------------
-- Table: restaurants (multi-tenant ready)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL slug: le-marvelous',
  `name` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `primary_color` VARCHAR(7) DEFAULT '#c58a3a',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Restaurants (multi-tenant)';

-- ---------------------------------------------
-- Table: restaurant_settings
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `restaurant_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `extra_phones` JSON DEFAULT NULL,
  `whatsapp_number` VARCHAR(20) DEFAULT NULL,
  `whatsapp_token` TEXT DEFAULT NULL COMMENT 'WhatsApp Business API token',
  `whatsapp_phone_id` VARCHAR(50) DEFAULT NULL,
  `whatsapp_business_id` VARCHAR(50) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(10) DEFAULT NULL,
  `latitude` DECIMAL(10,8) DEFAULT NULL,
  `longitude` DECIMAL(11,8) DEFAULT NULL,
  `instagram` VARCHAR(100) DEFAULT NULL,
  `facebook` VARCHAR(100) DEFAULT NULL,
  `tiktok` VARCHAR(100) DEFAULT NULL,
  `accepting_orders` TINYINT(1) DEFAULT 1,
  `loyalty_enabled` TINYINT(1) DEFAULT 1,
  `loyalty_points_per_euro` INT UNSIGNED DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant` (`restaurant_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Paramètres restaurant (config/restaurant.json)';

-- ---------------------------------------------
-- Table: opening_hours (supporte plusieurs créneaux par jour)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `opening_hours` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Lundi, 6=Dimanche',
  `slot_number` TINYINT DEFAULT 0 COMMENT '0=premier créneau, 1=deuxième, etc.',
  `opens` TIME DEFAULT '18:30:00',
  `closes` TIME DEFAULT '23:30:00',
  `is_closed` TINYINT(1) DEFAULT 0,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_day_slot` (`restaurant_id`, `day_of_week`, `slot_number`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Horaires ouverture par jour (multi-créneaux)';

-- =============================================
-- 2. MENU & PRODUITS
-- =============================================

-- ---------------------------------------------
-- Table: categories
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_sort` (`restaurant_id`, `sort_order`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catégories produits (menu.json > categories)';

-- ---------------------------------------------
-- Table: products
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `price_solo` DECIMAL(8,2) NOT NULL,
  `price_menu` DECIMAL(8,2) DEFAULT NULL,
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  `options_config` JSON DEFAULT NULL COMMENT 'Options spéciales: variants, viennoiserie, boissons, etc.',
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_status` (`restaurant_id`, `status`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Produits (menu.json > items)';

-- ---------------------------------------------
-- Table: supplements
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `supplements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `flavor` ENUM('sale', 'sucre', 'both') NOT NULL DEFAULT 'sale' COMMENT 'Type de plat compatible',
  `group_name` ENUM('viande', 'legumes', 'fromages', 'sauces', 'autres') NOT NULL DEFAULT 'autres' COMMENT 'Groupe pour affichage',
  `price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  `sort_order` INT DEFAULT 0,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_restaurant` (`restaurant_id`),
  INDEX `idx_restaurant_flavor` (`restaurant_id`, `flavor`),
  INDEX `idx_restaurant_group` (`restaurant_id`, `group_name`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suppléments (supplements.catalog)';

-- ---------------------------------------------
-- Table: product_supplements (liaison N:N)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `product_supplements` (
  `product_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED NOT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`product_id`, `supplement_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Liaison produits-suppléments';

-- =============================================
-- 3. CLIENTS & FIDÉLITÉ
-- =============================================

-- ---------------------------------------------
-- Table: customers
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `loyalty_code` VARCHAR(10) NOT NULL COMMENT 'Code unique ex: SNACK-A3X7',
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `loyalty_points` INT UNSIGNED DEFAULT 0,
  `orders_count` INT UNSIGNED DEFAULT 0,
  `total_spent` DECIMAL(10,2) DEFAULT 0.00,
  `last_order_at` TIMESTAMP NULL,
  `addresses` JSON DEFAULT NULL COMMENT 'Adresses multiples: [{type, label, address, notes, is_default}]',
  `preferences` JSON DEFAULT NULL COMMENT 'Préférences: {allergies, favorites, delivery_instructions, preferred_time}',
  `admin_notes` TEXT DEFAULT NULL COMMENT 'Notes privées admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_phone` (`restaurant_id`, `phone`),
  UNIQUE KEY `uk_loyalty_code` (`loyalty_code`),
  INDEX `idx_orders_count` (`restaurant_id`, `orders_count`),
  INDEX `idx_loyalty_points` (`restaurant_id`, `loyalty_points`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Clients (customers.json)';

-- ---------------------------------------------
-- Table: customer_tags
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `customer_tags` (
  `customer_id` INT UNSIGNED NOT NULL,
  `tag` VARCHAR(50) NOT NULL COMMENT 'VIP, Régulier, Nouveau, Zone, Type',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`customer_id`, `tag`),
  INDEX `idx_tag` (`tag`),
  INDEX `idx_customer_created` (`customer_id`, `created_at`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tags segmentation clients';

-- ---------------------------------------------
-- Table: loyalty_rewards
-- ---------------------------------------------
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
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_restaurant` (`restaurant_id`, `is_active`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Récompenses fidélité';

-- ---------------------------------------------
-- Table: loyalty_transactions
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `loyalty_transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `customer_id` INT UNSIGNED NOT NULL,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `order_id` INT UNSIGNED DEFAULT NULL,
  `points` INT NOT NULL COMMENT 'Positif=gagné, Négatif=utilisé',
  `type` ENUM('earn', 'redeem', 'bonus', 'adjustment') NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_restaurant` (`restaurant_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique transactions fidélité';

-- =============================================
-- 4. COMMANDES
-- =============================================

-- ---------------------------------------------
-- Table: orders
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `order_number` VARCHAR(20) NOT NULL COMMENT 'Ex: ORD202512150001',
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `status` ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
  `notes` TEXT DEFAULT NULL,
  `pickup_time` TIME DEFAULT NULL,
  `loyalty_reward_id` INT UNSIGNED DEFAULT NULL COMMENT 'Récompense fidélité utilisée',
  `loyalty_points_used` INT UNSIGNED DEFAULT 0 COMMENT 'Points déduits',
  `loyalty_redeemed` TINYINT(1) DEFAULT 0 COMMENT 'Points déjà déduits?',
  `is_archived` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_order_number` (`restaurant_id`, `order_number`),
  INDEX `idx_status` (`restaurant_id`, `status`, `is_archived`),
  INDEX `idx_created` (`restaurant_id`, `created_at`),
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Commandes (orders.json)';

-- ---------------------------------------------
-- Table: order_items
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL COMMENT 'Snapshot du nom',
  `variant` VARCHAR(50) DEFAULT NULL COMMENT 'solo ou menu',
  `quantity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(8,2) NOT NULL,
  `total_price` DECIMAL(8,2) NOT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_order` (`order_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Articles de commande';

-- ---------------------------------------------
-- Table: order_item_supplements
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `order_item_supplements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_item_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED DEFAULT NULL,
  `supplement_name` VARCHAR(100) NOT NULL COMMENT 'Snapshot du nom',
  `price` DECIMAL(8,2) NOT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Suppléments des articles de commande';

-- =============================================
-- 5. PROMOTIONS
-- =============================================

-- ---------------------------------------------
-- Table: promo_codes
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `promo_codes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `discount_type` ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_order_amount` DECIMAL(10,2) DEFAULT NULL,
  `max_uses` INT UNSIGNED DEFAULT NULL,
  `current_uses` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_code` (`restaurant_id`, `code`),
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_expires` (`expires_at`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Codes promo';

-- =============================================
-- 6. LIVRAISON
-- =============================================

-- ---------------------------------------------
-- Table: delivery_persons
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `delivery_persons` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `vehicle_type` ENUM('moto', 'voiture', 'velo', 'pieton') DEFAULT 'moto',
  `is_active` TINYINT(1) DEFAULT 1,
  `current_orders_count` INT UNSIGNED DEFAULT 0 COMMENT 'Commandes en cours',
  `total_deliveries` INT UNSIGNED DEFAULT 0 COMMENT 'Total livraisons effectuées',
  `rating` DECIMAL(3,2) DEFAULT NULL COMMENT 'Note moyenne (ex: 4.75)',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_restaurant_active` (`restaurant_id`, `is_active`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Livreurs (livreurs.json)';

-- =============================================
-- 7. TGTG (Too Good To Go)
-- =============================================

-- ---------------------------------------------
-- Table: tgtg_baskets
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `tgtg_baskets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `external_id` VARCHAR(50) DEFAULT NULL COMMENT 'ID TGTG API si intégration',
  `product_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `original_price` DECIMAL(8,2) NOT NULL,
  `discount_price` DECIMAL(8,2) NOT NULL,
  `quantity_total` INT UNSIGNED NOT NULL,
  `quantity_available` INT UNSIGNED NOT NULL,
  `pickup_time` VARCHAR(50) DEFAULT NULL COMMENT 'Ex: 20:00 - 21:00',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_restaurant_active_expires` (`restaurant_id`, `is_active`, `expires_at`),
  INDEX `idx_external` (`external_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Paniers Too Good To Go (tgtg.json)';

-- =============================================
-- 8. ADMINISTRATION
-- =============================================

-- ---------------------------------------------
-- Table: admin_users
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'manager', 'staff') DEFAULT 'staff',
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant_username` (`restaurant_id`, `username`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Utilisateurs administrateurs';

-- ---------------------------------------------
-- Table: admin_pins
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_pins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `pin_hash` VARCHAR(255) NOT NULL COMMENT 'Hash du PIN (password_hash)',
  `last_changed_by` INT UNSIGNED DEFAULT NULL COMMENT 'Admin qui a changé le PIN',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uk_restaurant` (`restaurant_id`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`last_changed_by`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Codes PIN admin (admin-pin.json)';

-- ---------------------------------------------
-- Table: faq
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `faq` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  INDEX `idx_restaurant` (`restaurant_id`, `sort_order`),
  INDEX `idx_deleted` (`deleted_at`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='FAQ (restaurant.json > faq.items)';

-- =============================================
-- 9. VUES
-- =============================================

-- ---------------------------------------------
-- Vue: v_dashboard_stats
-- ---------------------------------------------
CREATE OR REPLACE VIEW `v_dashboard_stats` AS
SELECT
  o.restaurant_id,
  DATE(o.created_at) as order_date,
  COUNT(*) as orders_count,
  SUM(o.total) as revenue,
  AVG(o.total) as avg_order
FROM orders o
WHERE o.is_archived = 0 AND o.deleted_at IS NULL
GROUP BY o.restaurant_id, DATE(o.created_at);

-- ---------------------------------------------
-- Vue: v_top_products
-- ---------------------------------------------
CREATE OR REPLACE VIEW `v_top_products` AS
SELECT
  oi.product_id,
  p.restaurant_id,
  p.name as product_name,
  SUM(oi.quantity) as total_sold,
  SUM(oi.total_price) as total_revenue
FROM order_items oi
JOIN products p ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
  AND o.deleted_at IS NULL
  AND oi.deleted_at IS NULL
GROUP BY oi.product_id, p.restaurant_id, p.name
ORDER BY total_sold DESC;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- FIN DU SCHÉMA
-- =============================================
