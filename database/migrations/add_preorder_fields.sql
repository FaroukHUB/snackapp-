-- Migration: Ajouter les champs de précommande et mode
-- Date: 2026-01-13

ALTER TABLE `orders`
ADD COLUMN `preorder_date` DATE DEFAULT NULL COMMENT 'Date de retrait pour précommande' AFTER `pickup_time`,
ADD COLUMN `preorder_time` TIME DEFAULT NULL COMMENT 'Heure de retrait pour précommande' AFTER `preorder_date`,
ADD COLUMN `mode_notes` VARCHAR(100) DEFAULT NULL COMMENT 'Mode de commande (À emporter, Sur place, Livraison)' AFTER `preorder_time`,
ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0 COMMENT 'Frais de livraison' AFTER `total`,
ADD COLUMN `delivery_address` TEXT DEFAULT NULL COMMENT 'Adresse de livraison complète' AFTER `mode_notes`,
ADD COLUMN `delivery_instructions` TEXT DEFAULT NULL COMMENT 'Instructions de livraison' AFTER `delivery_address`;
