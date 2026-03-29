-- =====================================================
-- Migration: Ajout colonne snackup_context à products
-- Date: 2026-01-26
-- Description: Ajouter une colonne JSON pour stocker le contexte Snackup
--              (ingrédients custom, prix custom, etc.)
-- =====================================================

-- Ajouter colonne snackup_context
ALTER TABLE products
ADD COLUMN snackup_context JSON DEFAULT NULL COMMENT 'Contexte Snackup: ingrédients custom, prix custom, etc.';

-- Créer un index pour les requêtes JSON (optionnel mais utile)
-- ALTER TABLE products ADD INDEX idx_snackup_context ((CAST(snackup_context AS CHAR(255))));

-- =====================================================
-- ROLLBACK (si nécessaire)
-- =====================================================
-- ALTER TABLE products DROP COLUMN snackup_context;
