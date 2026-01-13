-- =====================================================
-- Migration: Ajout colonne delivery_address à orders
-- Date: 2026-01-10
-- Description: Ajouter une colonne pour stocker l'adresse de livraison
-- =====================================================

-- Ajouter colonne delivery_address
ALTER TABLE orders
ADD COLUMN delivery_address VARCHAR(500) DEFAULT NULL AFTER customer_phone,
ADD COLUMN delivery_instructions TEXT DEFAULT NULL AFTER delivery_address;

-- Index pour recherche par adresse
CREATE INDEX idx_delivery_address ON orders(delivery_address(255));

-- =====================================================
-- ROLLBACK (si nécessaire)
-- =====================================================
-- ALTER TABLE orders DROP COLUMN delivery_address;
-- ALTER TABLE orders DROP COLUMN delivery_instructions;
-- DROP INDEX idx_delivery_address ON orders;
