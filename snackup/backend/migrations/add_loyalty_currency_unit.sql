-- =============================================
-- Migration: Ajouter loyalty_currency_unit et icon
-- Date: 2026-03-19
-- Description: Ajoute les colonnes pour personnaliser l'unité monétaire du système de fidélité
-- =============================================

-- Note: En MySQL, IF NOT EXISTS n'est pas supporté pour ALTER TABLE dans toutes les versions
-- Donc on utilise une approche qui ignore l'erreur si la colonne existe déjà
-- Exécuter ces commandes une par une, ignorer les erreurs de type "Duplicate column name"

-- 1. Ajouter colonne loyalty_currency_unit dans restaurant_settings
-- Cela permet de définir combien d'unités monétaires = 1 point (par défaut 100)
-- Exemple: 100 DA = 1 point, 1 EUR = 10 points, etc.
ALTER TABLE `restaurant_settings`
    ADD COLUMN `loyalty_currency_unit` INT UNSIGNED DEFAULT 100 COMMENT 'Unité monétaire pour 1 point (ex: 100 DA = 1 point)';

-- 2. Ajouter colonne icon dans loyalty_rewards
-- Permet de personnaliser l'icône FontAwesome de chaque récompense
ALTER TABLE `loyalty_rewards`
    ADD COLUMN `icon` VARCHAR(50) DEFAULT 'fa-gift' COMMENT 'Icône FontAwesome (ex: fa-cookie, fa-burger)';

-- 3. Mettre à jour les valeurs par défaut si besoin
UPDATE `restaurant_settings` SET `loyalty_currency_unit` = 100 WHERE `loyalty_currency_unit` IS NULL;
UPDATE `loyalty_rewards` SET `icon` = 'fa-gift' WHERE `icon` IS NULL OR `icon` = '';

SELECT 'Migration terminée avec succès!' AS status;
