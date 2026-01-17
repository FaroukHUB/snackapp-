-- =============================================
-- Migration: Ajout colonne flavor à supplements
-- Date: 2026-01-17
-- Objectif: Corriger bug "Unknown column 'type'"
-- =============================================

-- Désactiver les checks temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- Ajout colonne flavor
-- ---------------------------------------------
ALTER TABLE `supplements`
ADD COLUMN `flavor` ENUM('sale', 'sucre', 'both') NOT NULL DEFAULT 'both'
COMMENT 'Type de plat compatible: salé, sucré, ou les deux'
AFTER `name`;

-- ---------------------------------------------
-- Ajout index pour performance
-- ---------------------------------------------
CREATE INDEX `idx_restaurant_flavor` ON `supplements` (`restaurant_id`, `flavor`);

-- Réactiver les checks
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- Notes d'exécution:
-- =============================================
-- 1. Tous les suppléments existants seront 'both' par défaut
-- 2. L'index améliore les requêtes WHERE flavor = ?
-- 3. Compatible avec assignSupplementsByFlavor()
-- =============================================
