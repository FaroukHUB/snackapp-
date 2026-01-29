-- Migration: Add promo code fields to orders table
-- Date: 2026-01-29
-- Purpose: Store promo code information when an order is placed with a discount code

-- Add promo code columns to orders table
ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_code VARCHAR(50) DEFAULT NULL COMMENT 'Code promo utilisé';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_discount_type ENUM('percent', 'fixed') DEFAULT NULL COMMENT 'Type de réduction';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_discount_value DECIMAL(10,2) DEFAULT NULL COMMENT 'Valeur de la réduction';
ALTER TABLE orders ADD COLUMN IF NOT EXISTS promo_discount_amount DECIMAL(10,2) DEFAULT NULL COMMENT 'Montant de réduction appliqué';

-- Add index for promo code lookups
CREATE INDEX IF NOT EXISTS idx_orders_promo_code ON orders(promo_code);
