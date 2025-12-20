-- Migration: Ajouter fidélité aux commandes
-- Permet aux clients d'utiliser leurs points lors de la commande

ALTER TABLE orders
ADD COLUMN loyalty_reward_id INT UNSIGNED DEFAULT NULL COMMENT 'Récompense fidélité utilisée',
ADD COLUMN loyalty_points_used INT UNSIGNED DEFAULT 0 COMMENT 'Points déduits',
ADD COLUMN loyalty_redeemed TINYINT(1) DEFAULT 0 COMMENT 'Points déjà déduits?';

-- Index pour rechercher les commandes avec récompense
ALTER TABLE orders ADD INDEX idx_loyalty (loyalty_reward_id);
