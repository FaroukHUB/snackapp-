-- Migration: Create pizza_bases table
-- Date: 2026-01-30
-- Description: Table for managing pizza base options (tomate, crème fraîche, etc.)

CREATE TABLE IF NOT EXISTS pizza_bases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Add default_base_id to products table
ALTER TABLE products ADD COLUMN default_base_id INT DEFAULT NULL;
ALTER TABLE products ADD CONSTRAINT fk_product_base FOREIGN KEY (default_base_id) REFERENCES pizza_bases(id) ON DELETE SET NULL;
