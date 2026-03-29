-- Lier tous les suppléments aux catégories pizza (restaurant_id = 3)
-- Catégories : 9 (Sauce Tomate), 10 (Crème Fraîche), 11 (Originales)

-- Pour chaque catégorie pizza, on lie TOUS les suppléments
INSERT INTO category_supplements (category_id, supplement_id)
SELECT c.id, s.id
FROM categories c
CROSS JOIN supplements s
WHERE c.restaurant_id = 3
  AND c.id IN (9, 10, 11)
  AND s.restaurant_id = 3
  AND s.flavor = 'sale'
ON DUPLICATE KEY UPDATE category_id = category_id;
