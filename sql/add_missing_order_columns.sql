-- ================================================================
-- SQL Migration: Ajouter les colonnes manquantes à la table orders
-- Pour: L'Atelier Pizza (zajr1824_atelierpizza)
-- Date: 2026-01-29
-- ================================================================

-- Vérifier d'abord la structure actuelle
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders';

-- Ajouter toutes les colonnes potentiellement manquantes
-- Chaque ALTER est dans un bloc séparé pour éviter les erreurs si la colonne existe déjà

-- 1. delivery_fee - Frais de livraison
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_fee DECIMAL(10,2) DEFAULT 0.00;

-- 2. preorder_date - Date de précommande (YYYY-MM-DD)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS preorder_date DATE DEFAULT NULL;

-- 3. preorder_time - Heure de précommande (HH:MM)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS preorder_time TIME DEFAULT NULL;

-- 4. mode_notes - Notes sur le mode (À emporter, Sur place, Livraison)
ALTER TABLE orders ADD COLUMN IF NOT EXISTS mode_notes VARCHAR(255) DEFAULT NULL;

-- 5. delivery_address - Adresse de livraison complète
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_address TEXT DEFAULT NULL;

-- 6. delivery_instructions - Instructions pour la livraison
ALTER TABLE orders ADD COLUMN IF NOT EXISTS delivery_instructions TEXT DEFAULT NULL;

-- Vérification finale
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'
ORDER BY ORDINAL_POSITION;
