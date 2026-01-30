-- =============================================
-- Migration: Ajouter product_id aux récompenses fidélité
-- Date: 2026-01-30
-- Description: Permet de lier une récompense à un produit du menu
--              pour récupérer automatiquement le nom et l'image
-- =============================================

-- 1. Ajouter colonne product_id à loyalty_rewards
ALTER TABLE `loyalty_rewards`
    ADD COLUMN IF NOT EXISTS `product_id` INT UNSIGNED DEFAULT NULL
    COMMENT 'ID du produit lié (pour image et nom automatique)';

-- 2. Ajouter index sur product_id
ALTER TABLE `loyalty_rewards`
    ADD INDEX IF NOT EXISTS `idx_product` (`product_id`);

-- 3. Ajouter contrainte de clé étrangère (optionnel - SET NULL si produit supprimé)
-- Note: Si erreur, vérifier que la table products existe et a une colonne id INT UNSIGNED
ALTER TABLE `loyalty_rewards`
    ADD CONSTRAINT `fk_loyalty_reward_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL;

-- Vérification
SELECT 'Migration add_product_to_rewards terminée!' AS status;
