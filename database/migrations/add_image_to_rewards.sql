-- =============================================
-- Migration: Ajouter image personnalisée aux récompenses
-- Date: 2026-01-30
-- Description: Permet d'ajouter une image custom pour une récompense
--              sans avoir à la lier à un produit
-- =============================================

ALTER TABLE `loyalty_rewards`
    ADD COLUMN IF NOT EXISTS `image` VARCHAR(255) DEFAULT NULL
    COMMENT 'Image personnalisée de la récompense';

SELECT 'Migration add_image_to_rewards terminée!' AS status;
