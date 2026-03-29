-- =============================================
-- Migration: Corriger le type de la colonne id dans formules
-- Date: 2026-01-27
-- Description: Change id de VARCHAR(191) vers INT UNSIGNED AUTO_INCREMENT
-- ATTENTION: Cette migration supprime toutes les formules existantes
-- car il est impossible de convertir des IDs string en INT auto_increment
-- =============================================

-- Étape 1: Sauvegarder les données existantes (optionnel)
-- CREATE TABLE formules_backup AS SELECT * FROM formules;

-- Étape 2: Vider la table
TRUNCATE TABLE formules;

-- Étape 3: Supprimer l'ancienne colonne id et ses contraintes
ALTER TABLE formules
  DROP PRIMARY KEY,
  DROP COLUMN id;

-- Étape 4: Ajouter la nouvelle colonne id en INT AUTO_INCREMENT
ALTER TABLE formules
  ADD COLUMN id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST;

-- Étape 5: Supprimer les colonnes slug et badge si elles existent (obsolètes)
-- ALTER TABLE formules DROP COLUMN IF EXISTS slug;
-- ALTER TABLE formules DROP COLUMN IF EXISTS badge;
-- ALTER TABLE formules DROP COLUMN IF EXISTS savings;

-- Vérification: Afficher la nouvelle structure
DESCRIBE formules;
