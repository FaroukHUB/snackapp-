-- =============================================
-- SNACKAPP v1 - MySQL Schema
-- Migration depuis JSON vers MySQL
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- Table: restaurants (multi-tenant ready)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL slug: fabrik-burger',
  `name` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `primary_color` VARCHAR(7) DEFAULT '#c58a3a',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: restaurant_settings (ex restaurant.json)
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
  UNIQUE KEY `uk_restaurant` (`restaurant_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: opening_hours (multi-créneaux par jour)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `opening_hours` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `day_of_week` TINYINT NOT NULL COMMENT '0=Lundi, 6=Dimanche',
  `slot_number` TINYINT DEFAULT 0 COMMENT '0=premier créneau, 1=deuxième, etc.',
  `opens` TIME DEFAULT '18:30:00',
  `closes` TIME DEFAULT '23:30:00',
  `is_closed` TINYINT(1) DEFAULT 0,
  UNIQUE KEY `uk_restaurant_day_slot` (`restaurant_id`, `day_of_week`, `slot_number`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: categories (ex menu.json > categories)
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
  UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_sort` (`restaurant_id`, `sort_order`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: products (ex menu.json > items)
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
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_restaurant_slug` (`restaurant_id`, `slug`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_status` (`restaurant_id`, `status`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: supplements (ex supplements.catalog)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `supplements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `price` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('available', 'unavailable') DEFAULT 'available',
  `sort_order` INT DEFAULT 0,
  INDEX `idx_restaurant` (`restaurant_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: product_supplements (liaison N:N)
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `product_supplements` (
  `product_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`, `supplement_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_restaurant_phone` (`restaurant_id`, `phone`),
  UNIQUE KEY `uk_loyalty_code` (`loyalty_code`),
  INDEX `idx_orders_count` (`restaurant_id`, `orders_count`),
  INDEX `idx_loyalty_points` (`restaurant_id`, `loyalty_points`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: orders
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `order_number` VARCHAR(20) NOT NULL COMMENT 'Ex: FB-20250718-001',
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
  UNIQUE KEY `uk_order_number` (`restaurant_id`, `order_number`),
  INDEX `idx_status` (`restaurant_id`, `status`, `is_archived`),
  INDEX `idx_created` (`restaurant_id`, `created_at`),
  INDEX `idx_customer` (`customer_id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  INDEX `idx_order` (`order_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: order_item_supplements
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `order_item_supplements` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_item_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED DEFAULT NULL,
  `supplement_name` VARCHAR(100) NOT NULL COMMENT 'Snapshot du nom',
  `price` DECIMAL(8,2) NOT NULL,
  FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: faq
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `faq` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `answer` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  INDEX `idx_restaurant` (`restaurant_id`, `sort_order`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  UNIQUE KEY `uk_restaurant_username` (`restaurant_id`, `username`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Vue: dashboard_stats (stats rapides)
-- ---------------------------------------------
CREATE OR REPLACE VIEW `v_dashboard_stats` AS
SELECT
  o.restaurant_id,
  DATE(o.created_at) as order_date,
  COUNT(*) as orders_count,
  SUM(o.total) as revenue,
  AVG(o.total) as avg_order
FROM orders o
WHERE o.is_archived = 0
GROUP BY o.restaurant_id, DATE(o.created_at);

-- ---------------------------------------------
-- Vue: top_products (produits populaires)
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
GROUP BY oi.product_id, p.restaurant_id, p.name
ORDER BY total_sold DESC;

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
  INDEX `idx_restaurant` (`restaurant_id`, `is_active`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------
-- Table: loyalty_transactions
-- ---------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;
