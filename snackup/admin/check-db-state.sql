-- Requête pour voir tous les chemins d'images actuels dans la BDD
SELECT id, name, image
FROM products
WHERE restaurant_id = 2
ORDER BY id;
