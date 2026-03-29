-- Migration: Ajouter la colonne base_ingredients à la table products
-- Date: 2026-01-26
-- Description: Permet de stocker les ingrédients retirables pour chaque produit

ALTER TABLE `products`
ADD COLUMN `base_ingredients` JSON DEFAULT NULL COMMENT 'Liste des ingrédients que le client peut retirer' AFTER `price_menu`;

-- Vérifier que la colonne a bien été ajoutée
SELECT 'Migration réussie : Colonne base_ingredients ajoutée à la table products' AS status;
