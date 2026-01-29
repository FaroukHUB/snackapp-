-- =============================================
-- Migration: Ajout icon_image pour badges catégories
-- Date: 2026-01-29
-- Description: Permet d'uploader une image/badge pour chaque catégorie
--              Utilisé dans la sidebar desktop (style hashtagbangers.fr)
-- =============================================

SET NAMES utf8mb4;

-- Ajouter la colonne icon_image après icon
ALTER TABLE `categories`
  ADD COLUMN IF NOT EXISTS `icon_image` VARCHAR(255) DEFAULT NULL
  COMMENT 'Image badge pour sidebar (ex: combos.png)'
  AFTER `icon`;

-- =============================================
-- Fin migration
-- =============================================
