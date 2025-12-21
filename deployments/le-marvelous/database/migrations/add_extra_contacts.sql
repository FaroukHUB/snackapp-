-- Migration: Ajout colonnes pour telephones et reseaux sociaux supplementaires
ALTER TABLE restaurant_settings
ADD COLUMN IF NOT EXISTS extra_phones JSON DEFAULT NULL AFTER phone,
ADD COLUMN IF NOT EXISTS extra_socials JSON DEFAULT NULL AFTER snapchat;
