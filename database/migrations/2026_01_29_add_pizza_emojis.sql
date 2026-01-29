-- =============================================
-- Migration: Ajouter des emojis aux catégories
-- Date: 2026-01-29
-- Description: Remplace les icônes FontAwesome par des emojis pour les pizzas
-- =============================================

-- Mettre à jour les catégories pizza avec l'emoji pizza
UPDATE categories
SET icon = '🍕'
WHERE slug LIKE '%pizza%'
   OR name LIKE '%pizza%'
   OR name LIKE '%Pizza%';

-- Exemples d'autres emojis possibles pour d'autres catégories:
-- UPDATE categories SET icon = '🍔' WHERE slug LIKE '%burger%';
-- UPDATE categories SET icon = '🌮' WHERE slug LIKE '%taco%';
-- UPDATE categories SET icon = '🥗' WHERE slug LIKE '%salad%';
-- UPDATE categories SET icon = '🍟' WHERE slug LIKE '%frite%';
-- UPDATE categories SET icon = '🍰' WHERE slug LIKE '%dessert%';
-- UPDATE categories SET icon = '🥤' WHERE slug LIKE '%boisson%';
-- UPDATE categories SET icon = '☕' WHERE slug LIKE '%cafe%' OR slug LIKE '%coffee%';
