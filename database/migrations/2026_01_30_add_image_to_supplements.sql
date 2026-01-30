-- Migration: Ajouter colonne image aux suppléments
-- Date: 2026-01-30
-- Description: Permet d'associer une image à chaque supplément

ALTER TABLE `supplements`
ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `group_name`;
