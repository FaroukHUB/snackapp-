-- Insertion des suppléments SALÉS pour Atelier Pizza (restaurant_id = 3)
-- Extraits des produits existants

-- FROMAGES
INSERT INTO supplements (restaurant_id, name, flavor, category, price, status, sort_order) VALUES
(3, 'Mozzarella', 'sale', 'fromage', 1.50, 'available', 1),
(3, 'Chèvre', 'sale', 'fromage', 1.50, 'available', 2),
(3, 'Bleu', 'sale', 'fromage', 1.50, 'available', 3),
(3, 'Raclette', 'sale', 'fromage', 1.50, 'available', 4),
(3, 'Reblochon', 'sale', 'fromage', 1.50, 'available', 5),
(3, 'Boursin', 'sale', 'fromage', 1.50, 'available', 6),
(3, 'Cheddar', 'sale', 'fromage', 1.50, 'available', 7);

-- VIANDES
INSERT INTO supplements (restaurant_id, name, flavor, category, price, status, sort_order) VALUES
(3, 'Jambon de dinde', 'sale', 'viande', 1.50, 'available', 10),
(3, 'Merguez', 'sale', 'viande', 1.50, 'available', 11),
(3, 'Poulet', 'sale', 'viande', 1.50, 'available', 12),
(3, 'Viande hachée', 'sale', 'viande', 1.50, 'available', 13),
(3, 'Chorizo', 'sale', 'viande', 1.50, 'available', 14),
(3, 'Kebab', 'sale', 'viande', 1.50, 'available', 15),
(3, 'Lardons', 'sale', 'viande', 1.50, 'available', 16),
(3, 'Bolognaise', 'sale', 'viande', 1.50, 'available', 17),
(3, 'Steak', 'sale', 'viande', 2.00, 'available', 18),
(3, 'Saumon fumé', 'sale', 'viande', 2.50, 'available', 19),
(3, 'Thon', 'sale', 'viande', 1.50, 'available', 20);

-- LÉGUMES
INSERT INTO supplements (restaurant_id, name, flavor, category, price, status, sort_order) VALUES
(3, 'Champignons', 'sale', 'legume', 1.00, 'available', 30),
(3, 'Poivrons', 'sale', 'legume', 1.00, 'available', 31),
(3, 'Olives', 'sale', 'legume', 1.00, 'available', 32),
(3, 'Oignons', 'sale', 'legume', 1.00, 'available', 33),
(3, 'Tomates', 'sale', 'legume', 1.00, 'available', 34),
(3, 'Pommes de terre', 'sale', 'legume', 1.00, 'available', 35),
(3, 'Maïs', 'sale', 'legume', 1.00, 'available', 36),
(3, 'Frites', 'sale', 'legume', 1.50, 'available', 37);

-- AUTRES (Sauces et divers)
INSERT INTO supplements (restaurant_id, name, flavor, category, price, status, sort_order) VALUES
(3, 'Crème fraîche', 'sale', 'autre', 0.50, 'available', 40),
(3, 'Sauce thaï', 'sale', 'autre', 0.50, 'available', 41),
(3, 'Sauce samouraï', 'sale', 'autre', 0.50, 'available', 42),
(3, 'Sauce barbecue', 'sale', 'autre', 0.50, 'available', 43),
(3, 'Sauce curry', 'sale', 'autre', 0.50, 'available', 44),
(3, 'Sauce algérienne', 'sale', 'autre', 0.50, 'available', 45),
(3, 'Sauce piquante', 'sale', 'autre', 0.50, 'available', 46),
(3, 'Pesto', 'sale', 'autre', 0.50, 'available', 47),
(3, 'Miel', 'sale', 'autre', 0.50, 'available', 48),
(3, 'Œuf', 'sale', 'autre', 0.80, 'available', 49),
(3, 'Persillade', 'sale', 'autre', 0.50, 'available', 50);

