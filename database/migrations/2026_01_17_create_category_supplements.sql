-- =============================================
-- Migration de rattrapage: Création table category_supplements
-- Date: 2026-01-17
-- Instance: Atelier Pizza
-- Objectif: Débloquer création catégories (fix erreur "Table doesn't exist")
-- =============================================

-- Note: Cette migration crée UNIQUEMENT la table manquante.
-- Elle ne modifie aucune colonne existante (supplements.flavor reste inchangé).
-- Pour Atelier Pizza (pizzeria), l'auto-assignment par flavor n'est pas utilisé.

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- Table: category_supplements
-- Liaison N:N entre catégories et suppléments
-- ---------------------------------------------
CREATE TABLE IF NOT EXISTS `category_supplements` (
  `category_id` INT UNSIGNED NOT NULL,
  `supplement_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`, `supplement_id`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_supplement` (`supplement_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplement_id`) REFERENCES `supplements`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Liaison catégories-suppléments (auto-assignment désactivé pour Atelier Pizza)';

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- Post-migration:
-- =============================================
-- Cette table existe maintenant mais ne sera PAS utilisée activement
-- par Atelier Pizza (flag auto_category_supplements=false).
-- Elle évite simplement l'erreur SQL lors de la création de catégories.
-- =============================================
