-- Script de remplissage menu : L'Atelier Pizza Roubaix
-- Date : 2025-01-13
-- Restaurant ID : 3

-- Nettoyer les données existantes (si besoin de recommencer)
-- DELETE FROM products WHERE restaurant_id = 3;
-- DELETE FROM categories WHERE restaurant_id = 3;

-- ==================== CATÉGORIES ====================

INSERT INTO categories (restaurant_id, slug, name, description, icon, flavor, sort_order, is_active) VALUES
(3, 'pizzas-sauce-tomate', 'Pizzas Sauce Tomate', 'Nos pizzas traditionnelles sur base tomate', '🍅', 'sale', 1, 1),
(3, 'pizzas-creme', 'Pizzas Crème Fraîche', 'Nos pizzas gourmandes sur base crème', '🧀', 'sale', 2, 1),
(3, 'pizzas-originales', 'Pizzas Originales', 'Nos créations uniques et audacieuses', '✨', 'sale', 3, 1),
(3, 'pates', 'Pâtes', 'Penne ou Tagliatelle au choix, option gratinée disponible', '🍝', 'sale', 4, 1),
(3, 'gratins', 'Gratins', 'Nos gratins généreux et réconfortants', '🥘', 'sale', 5, 1),
(3, 'texmex', 'Tex-Mex', 'Nuggets, tenders, wings et accompagnements', '🍗', 'sale', 6, 1),
(3, 'desserts', 'Desserts', 'Pour terminer en douceur', '🍰', 'sucre', 7, 1),
(3, 'boissons', 'Boissons', 'Sodas et boissons fraîches', '🥤', NULL, 8, 1);

-- ==================== PIZZAS SAUCE TOMATE ====================
-- Prix : 7,50€ Solo (26cm) / 9€ Duo (31cm)
-- Bundle : 2 Solo = 13€ | 2 Duo = 15€

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order, bundle_enabled, bundle_quantity, bundle_price_solo, bundle_price_duo) VALUES
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-reine', 'Pizza REINE', 'Mozzarella, jambon de dinde, champignons', NULL, 7.50, 9.00, 'available', 1, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-orientale', 'Pizza ORIENTALE', 'Mozzarella, merguez, poivrons, olives, œuf', NULL, 7.50, 9.00, 'available', 2, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-vegetarienne', 'Pizza VÉGÉTARIENNE', 'Mozzarella, poivrons, olives, champignons, oignon', NULL, 7.50, 9.00, 'available', 3, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-provencale', 'Pizza PROVENÇALE', 'Mozzarella, poivrons, olives, thon, oignon', NULL, 7.50, 9.00, 'available', 4, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-3-fromages', 'Pizza 3 FROMAGES', 'Mozzarella, chèvre, bleu', NULL, 7.50, 9.00, 'available', 5, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-royal', 'Pizza ROYAL', 'Mozzarella, poulet, viande hachée, œuf', NULL, 7.50, 9.00, 'available', 6, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-mexicaine', 'Pizza MEXICAINE', 'Mozzarella, chorizo, viande hachée, poivrons', NULL, 7.50, 9.00, 'available', 7, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-roubaisienne', 'Pizza ROUBAISIENNE', 'Mozzarella, jambon de dinde, chorizo, champignons, olives', NULL, 7.50, 9.00, 'available', 8, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-texane', 'Pizza TEXANE', 'Mozzarella, poulet, chorizo, poivrons, œuf', NULL, 7.50, 9.00, 'available', 9, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-campione', 'Pizza CAMPIONE', 'Mozzarella, viande hachée, champignons, œuf', NULL, 7.50, 9.00, 'available', 10, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-bolo', 'Pizza BOLO', 'Mozzarella, viande hachée, oignons', NULL, 7.50, 9.00, 'available', 11, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-tunisienne', 'Pizza TUNISIENNE', 'Mozzarella, viande hachée, merguez, olives, poivrons', NULL, 7.50, 9.00, 'available', 12, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-carnivore', 'Pizza CARNIVORE', 'Mozzarella, poulet, merguez, kebab', NULL, 7.50, 9.00, 'available', 13, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-sauce-tomate' AND restaurant_id=3), 'pizza-cannibale', 'Pizza CANNIBALE', 'Mozzarella, viande hachée, poulet, chorizo', NULL, 7.50, 9.00, 'available', 14, 1, 2, 13.00, 15.00);

-- ==================== PIZZAS CRÈME ====================

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order, bundle_enabled, bundle_quantity, bundle_price_solo, bundle_price_duo) VALUES
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-savoyarde', 'Pizza SAVOYARDE', 'Mozzarella, jambon de dinde, pomme de terre, oignon', NULL, 7.50, 9.00, 'available', 1, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-spicy', 'Pizza SPICY', 'Mozzarella, sauce thaï, poivrons, poulet', NULL, 7.50, 9.00, 'available', 2, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-kebab', 'Pizza KEBAB', 'Mozzarella, kebab, poivrons, sauce samouraï', NULL, 7.50, 9.00, 'available', 3, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-chevre-miel', 'Pizza CHÈVRE MIEL', 'Mozzarella, chèvre, miel', NULL, 7.50, 9.00, 'available', 4, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-3-jambons', 'Pizza 3 JAMBONS', 'Mozzarella, jambon de dinde, chorizo, lardons', NULL, 7.50, 9.00, 'available', 5, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-raclette', 'Pizza RACLETTE', 'Mozzarella, jambon de dinde, pomme de terre, raclette', NULL, 7.50, 9.00, 'available', 6, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-chicken', 'Pizza CHICKEN', 'Mozzarella, poulet, pomme de terre, champignons', NULL, 7.50, 9.00, 'available', 7, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-tartiflette', 'Pizza TARTIFLETTE', 'Mozzarella, lardons, pomme de terre, oignon, reblochon', NULL, 7.50, 9.00, 'available', 8, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-norvegienne', 'Pizza NORVÉGIENNE', 'Mozzarella, saumon fumé, pomme de terre, persillade', NULL, 7.50, 9.00, 'available', 9, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-country', 'Pizza COUNTRY', 'Mozzarella, viande hachée, pomme de terre, chèvre', NULL, 7.50, 9.00, 'available', 10, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-supreme', 'Pizza SUPRÊME', 'Mozzarella, poulet, lardons, œuf', NULL, 7.50, 9.00, 'available', 11, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-boursin', 'Pizza BOURSIN', 'Mozzarella, jambon de dinde, viande hachée, boursin, œuf', NULL, 7.50, 9.00, 'available', 12, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-creme' AND restaurant_id=3), 'pizza-fermiere', 'Pizza FERMIÈRE', 'Mozzarella, jambon de dinde, pomme de terre, champignons', NULL, 7.50, 9.00, 'available', 13, 1, 2, 13.00, 15.00);

-- ==================== PIZZAS ORIGINALES ====================

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order, bundle_enabled, bundle_quantity, bundle_price_solo, bundle_price_duo) VALUES
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-algerienne', 'Pizza ALGÉRIENNE', 'Crème fraîche, mozzarella, poulet, merguez, poivrons, sauce algérienne', NULL, 7.50, 9.00, 'available', 1, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-americaine', 'Pizza AMÉRICAINE', 'Sauce tomate, mozzarella, sauce barbecue, chorizo, merguez, poivrons', NULL, 7.50, 9.00, 'available', 2, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-indiana', 'Pizza INDIANA', 'Crème fraîche, mozzarella, poulet, lardons, champignons, sauce barbecue', NULL, 7.50, 9.00, 'available', 3, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-curry', 'Pizza CURRY', 'Crème fraîche, mozzarella, poulet, champignons, sauce curry', NULL, 7.50, 9.00, 'available', 4, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-chicken-boursin', 'Pizza CHICKEN BOURSIN', 'Crème fraîche, mozzarella, poulet, oignons, boursin', NULL, 7.50, 9.00, 'available', 5, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-cheddar', 'Pizza CHEDDAR', 'Crème fraîche, mozzarella, viande hachée, jambon, cheddar, œuf', NULL, 7.50, 9.00, 'available', 6, 1, 2, 13.00, 15.00),
(3, (SELECT id FROM categories WHERE slug='pizzas-originales' AND restaurant_id=3), 'pizza-buffalo', 'Pizza BUFFALO', 'Sauce tomate, sauce barbecue, mozzarella, viande hachée, poivrons, oignons', NULL, 7.50, 9.00, 'available', 7, 1, 2, 13.00, 15.00);

-- ==================== PÂTES ====================
-- Prix : 9€ (Penne ou Tagliatelle)

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order) VALUES
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-mexicaine', 'Pâtes MEXICAINE', 'Crème fraîche, poulet, maïs, poivrons, oignons', NULL, 9.00, NULL, 'available', 1),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-veggie', 'Pâtes VEGGIE', 'Crème fraîche, oignons, poivrons, maïs, champignons', NULL, 9.00, NULL, 'available', 2),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-supreme', 'Pâtes SUPRÊME', 'Crème fraîche, poulet, jambon de dinde, lardons, champignons', NULL, 9.00, NULL, 'available', 3),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-carbonara', 'Pâtes CARBONARA', 'Crème fraîche, lardons, reblochon, champignons', NULL, 9.00, NULL, 'available', 4),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-saumon', 'Pâtes SAUMON', 'Crème fraîche, saumon', NULL, 9.00, NULL, 'available', 5),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-fromaggi', 'Pâtes FROMAGGI', 'Crème fraîche, chèvre, bleu, reblochon', NULL, 9.00, NULL, 'available', 6),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-pesto', 'Pâtes PESTO', 'Crème fraîche, poulet, champignons, sauce pesto', NULL, 9.00, NULL, 'available', 7),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-bolognaise', 'Pâtes BOLOGNAISE', 'Crème fraîche, bolognaise, oignons', NULL, 9.00, NULL, 'available', 8),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-nordique', 'Pâtes NORDIQUE', 'Crème fraîche, jambon de dinde, pesto, champignons', NULL, 9.00, NULL, 'available', 9),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-indy', 'Pâtes INDY', 'Crème fraîche, curry, poulet, champignons', NULL, 9.00, NULL, 'available', 10),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-diavola', 'Pâtes DIAVOLA', 'Crème fraîche, poulet, jambon de dinde, sauce piquante', NULL, 9.00, NULL, 'available', 11),
(3, (SELECT id FROM categories WHERE slug='pates' AND restaurant_id=3), 'pates-chef', 'Pâtes CHEF', 'Crème fraîche, bolognaise, poulet, champignons', NULL, 9.00, NULL, 'available', 12);

-- ==================== GRATINS ====================
-- Prix : 9€

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order) VALUES
(3, (SELECT id FROM categories WHERE slug='gratins' AND restaurant_id=3), 'gratin-campagnard', 'Gratin CAMPAGNARD', 'Mozzarella, crème fraîche, lardons, pommes de terre, reblochon', NULL, 9.00, NULL, 'available', 1),
(3, (SELECT id FROM categories WHERE slug='gratins' AND restaurant_id=3), 'gratin-special', 'Gratin SPÉCIAL', 'Mozzarella, crème fraîche, chicken curry, pommes de terre, sauce algérienne', NULL, 9.00, NULL, 'available', 2),
(3, (SELECT id FROM categories WHERE slug='gratins' AND restaurant_id=3), 'gratin-montagnard', 'Gratin MONTAGNARD', 'Mozzarella, crème fraîche, jambon, pommes de terre, fromage à raclette', NULL, 9.00, NULL, 'available', 3),
(3, (SELECT id FROM categories WHERE slug='gratins' AND restaurant_id=3), 'gratin-saumon', 'Gratin SAUMON', 'Mozzarella, crème fraîche, saumon fumé, pommes de terre', NULL, 9.00, NULL, 'available', 4);

-- ==================== TEXMEX ====================
-- Prix : 5€ (sauf Potatoes 3€)

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order) VALUES
(3, (SELECT id FROM categories WHERE slug='texmex' AND restaurant_id=3), 'nuggets', 'Nuggets x8', 'Nuggets de poulet croustillants', NULL, 5.00, NULL, 'available', 1),
(3, (SELECT id FROM categories WHERE slug='texmex' AND restaurant_id=3), 'tenders', 'Tenders x4', 'Tenders de poulet panés', NULL, 5.00, NULL, 'available', 2),
(3, (SELECT id FROM categories WHERE slug='texmex' AND restaurant_id=3), 'wings', 'Wings x6', 'Ailes de poulet épicées', NULL, 5.00, NULL, 'available', 3),
(3, (SELECT id FROM categories WHERE slug='texmex' AND restaurant_id=3), 'mozza-stick', 'Mozza-Stick x6', 'Bâtonnets de mozzarella panés', NULL, 5.00, NULL, 'available', 4),
(3, (SELECT id FROM categories WHERE slug='texmex' AND restaurant_id=3), 'potatoes', 'Potatoes', 'Pommes de terre épicées', NULL, 3.00, NULL, 'available', 5);

-- ==================== DESSERTS ====================
-- Prix : 3€

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order) VALUES
(3, (SELECT id FROM categories WHERE slug='desserts' AND restaurant_id=3), 'tarte-daim', 'Tarte Daim', 'Tarte au chocolat et caramel Daim', NULL, 3.00, NULL, 'available', 1),
(3, (SELECT id FROM categories WHERE slug='desserts' AND restaurant_id=3), 'tiramisu', 'Tiramisu', 'Tiramisu fait maison', NULL, 3.00, NULL, 'available', 2);

-- ==================== BOISSONS ====================
-- Prix : 1,50€ (33cl) / 3€ (1L25-1L50)

INSERT INTO products (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order) VALUES
(3, (SELECT id FROM categories WHERE slug='boissons' AND restaurant_id=3), 'soda-33cl', 'Soda 33cl', 'Coca-Cola, Fanta, Orangina, Sprite...', NULL, 1.50, NULL, 'available', 1),
(3, (SELECT id FROM categories WHERE slug='boissons' AND restaurant_id=3), 'soda-125l', 'Soda 1L25-1L50', 'Coca-Cola, Fanta, Orangina, Sprite... (grande bouteille)', NULL, 3.00, NULL, 'available', 2);

-- ==================== NOTES ====================
-- Bundle pricing : Activé pour toutes les pizzas
-- 2 Pizzas Solo (26cm) = 13€ au lieu de 15€ (économie 2€)
-- 2 Pizzas Duo (31cm) = 15€ au lieu de 18€ (économie 3€)
-- Le calcul se fait automatiquement dans le panier (cart.js)
