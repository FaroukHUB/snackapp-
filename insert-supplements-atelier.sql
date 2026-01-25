-- Insertion des suppléments pour Atelier Pizza (restaurant_id = 3)
-- Extraits des produits existants

-- FROMAGES
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Mozzarella', 'fromage', 1.50, 'available', 1),
(3, 'Chèvre', 'fromage', 1.50, 'available', 2),
(3, 'Bleu', 'fromage', 1.50, 'available', 3),
(3, 'Raclette', 'fromage', 1.50, 'available', 4),
(3, 'Reblochon', 'fromage', 1.50, 'available', 5),
(3, 'Boursin', 'fromage', 1.50, 'available', 6),
(3, 'Cheddar', 'fromage', 1.50, 'available', 7);

-- VIANDES
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Jambon de dinde', 'viande', 1.50, 'available', 10),
(3, 'Merguez', 'viande', 1.50, 'available', 11),
(3, 'Poulet', 'viande', 1.50, 'available', 12),
(3, 'Viande hachée', 'viande', 1.50, 'available', 13),
(3, 'Chorizo', 'viande', 1.50, 'available', 14),
(3, 'Kebab', 'viande', 1.50, 'available', 15),
(3, 'Lardons', 'viande', 1.50, 'available', 16),
(3, 'Bolognaise', 'viande', 1.50, 'available', 17),
(3, 'Steak', 'viande', 2.00, 'available', 18);

-- POISSONS
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Saumon fumé', 'poisson', 2.50, 'available', 20),
(3, 'Thon', 'poisson', 1.50, 'available', 21);

-- LÉGUMES
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Champignons', 'legume', 1.00, 'available', 30),
(3, 'Poivrons', 'legume', 1.00, 'available', 31),
(3, 'Olives', 'legume', 1.00, 'available', 32),
(3, 'Oignons', 'legume', 1.00, 'available', 33),
(3, 'Tomates', 'legume', 1.00, 'available', 34),
(3, 'Pommes de terre', 'legume', 1.00, 'available', 35),
(3, 'Maïs', 'legume', 1.00, 'available', 36),
(3, 'Frites', 'legume', 1.50, 'available', 37);

-- SAUCES
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Crème fraîche', 'sauce', 0.50, 'available', 40),
(3, 'Sauce thaï', 'sauce', 0.50, 'available', 41),
(3, 'Sauce samouraï', 'sauce', 0.50, 'available', 42),
(3, 'Sauce barbecue', 'sauce', 0.50, 'available', 43),
(3, 'Sauce curry', 'sauce', 0.50, 'available', 44),
(3, 'Sauce algérienne', 'sauce', 0.50, 'available', 45),
(3, 'Sauce piquante', 'sauce', 0.50, 'available', 46),
(3, 'Pesto', 'sauce', 0.50, 'available', 47),
(3, 'Miel', 'sauce', 0.50, 'available', 48);

-- AUTRES
INSERT INTO supplements (restaurant_id, name, category, price, status, sort_order) VALUES
(3, 'Œuf', 'autre', 0.80, 'available', 50),
(3, 'Persillade', 'autre', 0.50, 'available', 51);
