-- =====================================================
-- Migration: Ajout colonne delivery_fee à orders
-- Date: 2026-01-11
-- Description: Ajouter une colonne pour stocker les frais de livraison
-- =====================================================

-- Ajouter colonne delivery_fee
ALTER TABLE orders
ADD COLUMN delivery_fee DECIMAL(10,2) DEFAULT 0 AFTER total;

-- =====================================================
-- ROLLBACK (si nécessaire)
-- =====================================================
-- ALTER TABLE orders DROP COLUMN delivery_fee;
