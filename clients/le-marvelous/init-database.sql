-- =============================================
-- LE MARVELOUS - Initialisation Base de Données
-- Diner 50's · Ouled Moussa, Algérie
-- Date: 2024-12-21
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------
-- 1. Restaurant
-- ---------------------------------------------
INSERT INTO `restaurants` (`id`, `slug`, `name`, `logo`, `primary_color`, `is_active`) VALUES
(2, 'le-marvelous', 'Le Marvelous', 'marvel.jpeg', '#2ec4b6', 1);

-- ---------------------------------------------
-- 2. Restaurant Settings
-- ---------------------------------------------
INSERT INTO `restaurant_settings` (
    `restaurant_id`,
    `phone`,
    `extra_phones`,
    `whatsapp_number`,
    `address`,
    `city`,
    `postal_code`,
    `latitude`,
    `longitude`,
    `instagram`,
    `facebook`,
    `tiktok`,
    `accepting_orders`,
    `loyalty_enabled`,
    `loyalty_points_per_euro`
) VALUES (
    2,
    '+213556782194',
    NULL,
    '+213556782194',
    'Riad City, Cité OMS 562 logements',
    'Ouled Moussa',
    '35520',
    36.7053937,
    3.3693709,
    'https://www.instagram.com/lemarvelous.50/',
    '',
    '',
    1,
    1,
    10
);

-- ---------------------------------------------
-- 3. Horaires d'ouverture
-- 0=Lundi, 1=Mardi, ... 6=Dimanche
-- ---------------------------------------------
INSERT INTO `opening_hours` (`restaurant_id`, `day_of_week`, `opens`, `closes`, `is_closed`) VALUES
(2, 0, '07:00:00', '21:30:00', 0),  -- Lundi
(2, 1, '07:00:00', '21:30:00', 0),  -- Mardi
(2, 2, '07:00:00', '21:30:00', 0),  -- Mercredi
(2, 3, '07:00:00', '21:30:00', 0),  -- Jeudi
(2, 4, '07:00:00', '11:30:00', 0),  -- Vendredi matin (pause pour prière)
(2, 5, '07:00:00', '21:30:00', 0),  -- Samedi
(2, 6, '07:00:00', '21:30:00', 0);  -- Dimanche

-- Note: Vendredi a 2 créneaux (07:00-11:30 et 15:30-21:30)
-- On gère le 2e créneau dans l'application

-- ---------------------------------------------
-- 4. Catégories du Menu
-- ---------------------------------------------
INSERT INTO `categories` (`id`, `restaurant_id`, `name`, `slug`, `description`, `image`, `sort_order`, `is_active`) VALUES
(100, 2, 'Crêpes Salées Signature', 'crepes-salees-signature', 'Nos créations maison', 'crepes-salees.jpg', 1, 1),
(101, 2, 'Crêpes Salées Classiques', 'crepes-salees-classiques', 'Les incontournables', 'crepes-classiques.jpg', 2, 1),
(102, 2, 'Crêpes Sucrées', 'crepes-sucrees', 'Pour les gourmands', 'crepes-sucrees.jpg', 3, 1),
(103, 2, 'Gaufres', 'gaufres', 'Gaufres maison', 'gaufres.jpg', 4, 1),
(104, 2, 'Boissons Chaudes', 'boissons-chaudes', 'Café, thé et plus', 'boissons-chaudes.jpg', 5, 1),
(105, 2, 'Boissons Fraîches', 'boissons-fraiches', 'Jus et smoothies', 'boissons-fraiches.jpg', 6, 1),
(106, 2, 'Menu Enfant', 'menu-enfant', 'Pour les petits', 'menu-enfant.jpg', 7, 1);

-- ---------------------------------------------
-- 5. Produits - Crêpes Salées Signature
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 100, 'La Marvelous', 'la-marvelous', 'Viande hachée, fromage, oignons, poivrons, sauce maison', 'marvelous.jpg', 550.00, NULL, 'available', 1),
(2, 100, 'La Mexicana', 'la-mexicana', 'Poulet épicé, guacamole, cheddar, jalapeños', 'mexicana.jpg', 580.00, NULL, 'available', 2),
(2, 100, 'La Forestière', 'la-forestiere', 'Champignons, jambon de dinde, gruyère, crème fraîche', 'forestiere.jpg', 520.00, NULL, 'available', 3),
(2, 100, 'La Chicken BBQ', 'chicken-bbq', 'Poulet grillé, sauce BBQ, oignons caramélisés, cheddar', 'chicken-bbq.jpg', 560.00, NULL, 'available', 4);

-- ---------------------------------------------
-- 6. Produits - Crêpes Salées Classiques
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 101, 'Crêpe Jambon Fromage', 'jambon-fromage', 'Jambon de dinde, fromage fondu', 'jambon-fromage.jpg', 380.00, NULL, 'available', 1),
(2, 101, 'Crêpe Thon', 'crepe-thon', 'Thon, mayonnaise, fromage', 'thon.jpg', 400.00, NULL, 'available', 2),
(2, 101, 'Crêpe Viande Hachée', 'viande-hachee', 'Viande hachée épicée, fromage', 'viande-hachee.jpg', 420.00, NULL, 'available', 3),
(2, 101, 'Crêpe Poulet', 'crepe-poulet', 'Poulet grillé, sauce blanche, fromage', 'poulet.jpg', 430.00, NULL, 'available', 4);

-- ---------------------------------------------
-- 7. Produits - Crêpes Sucrées
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 102, 'Crêpe Nutella', 'crepe-nutella', 'Nutella généreux', 'nutella.jpg', 300.00, NULL, 'available', 1),
(2, 102, 'Crêpe Nutella Banane', 'nutella-banane', 'Nutella, bananes fraîches', 'nutella-banane.jpg', 350.00, NULL, 'available', 2),
(2, 102, 'Crêpe Confiture', 'crepe-confiture', 'Confiture au choix', 'confiture.jpg', 250.00, NULL, 'available', 3),
(2, 102, 'Crêpe Miel Amandes', 'miel-amandes', 'Miel, amandes effilées', 'miel-amandes.jpg', 320.00, NULL, 'available', 4),
(2, 102, 'Crêpe Caramel Beurre Salé', 'caramel-beurre-sale', 'Sauce caramel maison', 'caramel.jpg', 340.00, NULL, 'available', 5),
(2, 102, 'Crêpe Fruits Rouges', 'fruits-rouges', 'Coulis fruits rouges, chantilly', 'fruits-rouges.jpg', 380.00, NULL, 'available', 6);

-- ---------------------------------------------
-- 8. Produits - Gaufres
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 103, 'Gaufre Nature', 'gaufre-nature', 'Sucre glace', 'gaufre-nature.jpg', 200.00, NULL, 'available', 1),
(2, 103, 'Gaufre Nutella', 'gaufre-nutella', 'Nutella généreux', 'gaufre-nutella.jpg', 300.00, NULL, 'available', 2),
(2, 103, 'Gaufre Chantilly', 'gaufre-chantilly', 'Chantilly maison', 'gaufre-chantilly.jpg', 280.00, NULL, 'available', 3),
(2, 103, 'Gaufre Complète', 'gaufre-complete', 'Nutella, banane, chantilly, amandes', 'gaufre-complete.jpg', 400.00, NULL, 'available', 4);

-- ---------------------------------------------
-- 9. Produits - Boissons Chaudes
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 104, 'Café Express', 'cafe-express', 'Café court', 'cafe.jpg', 100.00, NULL, 'available', 1),
(2, 104, 'Café Crème', 'cafe-creme', 'Café avec lait', 'cafe-creme.jpg', 150.00, NULL, 'available', 2),
(2, 104, 'Cappuccino', 'cappuccino', 'Café, lait mousseux, cacao', 'cappuccino.jpg', 200.00, NULL, 'available', 3),
(2, 104, 'Chocolat Chaud', 'chocolat-chaud', 'Chocolat onctueux', 'chocolat-chaud.jpg', 200.00, NULL, 'available', 4),
(2, 104, 'Thé à la Menthe', 'the-menthe', 'Thé vert, menthe fraîche', 'the-menthe.jpg', 150.00, NULL, 'available', 5);

-- ---------------------------------------------
-- 10. Produits - Boissons Fraîches
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 105, 'Jus d''Orange Frais', 'jus-orange', 'Oranges pressées', 'jus-orange.jpg', 200.00, NULL, 'available', 1),
(2, 105, 'Citronnade', 'citronnade', 'Citron, sucre, eau fraîche', 'citronnade.jpg', 150.00, NULL, 'available', 2),
(2, 105, 'Smoothie Banane', 'smoothie-banane', 'Banane, lait, miel', 'smoothie-banane.jpg', 250.00, NULL, 'available', 3),
(2, 105, 'Smoothie Fruits Rouges', 'smoothie-fruits-rouges', 'Mix fruits rouges', 'smoothie-fruits-rouges.jpg', 280.00, NULL, 'available', 4),
(2, 105, 'Eau Minérale', 'eau-minerale', '50cl', 'eau.jpg', 50.00, NULL, 'available', 5),
(2, 105, 'Soda', 'soda', 'Coca, Fanta, Sprite', 'soda.jpg', 100.00, NULL, 'available', 6);

-- ---------------------------------------------
-- 11. Produits - Menu Enfant
-- ---------------------------------------------
INSERT INTO `products` (`restaurant_id`, `category_id`, `name`, `slug`, `description`, `image`, `price_solo`, `price_menu`, `status`, `sort_order`) VALUES
(2, 106, 'Menu Petit Marvel', 'menu-petit-marvel', 'Mini crêpe salée + jus + surprise', 'menu-enfant-sale.jpg', 400.00, NULL, 'available', 1),
(2, 106, 'Menu Petit Gourmand', 'menu-petit-gourmand', 'Mini crêpe sucrée + jus + surprise', 'menu-enfant-sucre.jpg', 380.00, NULL, 'available', 2);

-- ---------------------------------------------
-- 12. Suppléments
-- ---------------------------------------------
INSERT INTO `supplements` (`restaurant_id`, `name`, `price`, `status`, `sort_order`) VALUES
(2, 'Fromage', 50.00, 'available', 1),
(2, 'Oeuf', 50.00, 'available', 2),
(2, 'Champignons', 80.00, 'available', 3),
(2, 'Poulet', 100.00, 'available', 4),
(2, 'Viande hachée', 100.00, 'available', 5),
(2, 'Nutella', 80.00, 'available', 6),
(2, 'Banane', 50.00, 'available', 7),
(2, 'Chantilly', 50.00, 'available', 8),
(2, 'Amandes', 60.00, 'available', 9),
(2, 'Fruits rouges', 80.00, 'available', 10);

-- ---------------------------------------------
-- 13. FAQ
-- ---------------------------------------------
INSERT INTO `faq` (`restaurant_id`, `question`, `answer`, `sort_order`) VALUES
(2, 'Quels moyens de paiement acceptez-vous ?', 'Argent liquide uniquement.', 1),
(2, 'Livrez-vous ?', 'Oui, livraison en commande directe (WhatsApp / téléphone).', 2),
(2, 'Convient-il pour les familles ?', 'Oui, nous avons une salle dédiée aux familles et un menu enfant.', 3),
(2, 'Y a-t-il un espace pour les femmes ?', 'Oui, nous disposons d''une salle réservée aux femmes.', 4),
(2, 'Peut-on regarder du sport sur place ?', 'Oui, écrans disponibles pour regarder du sport.', 5);

-- ---------------------------------------------
-- 14. Récompenses Fidélité
-- ---------------------------------------------
INSERT INTO `loyalty_rewards` (`restaurant_id`, `name`, `description`, `points_required`, `reward_type`, `reward_value`, `is_active`) VALUES
(2, 'Boisson offerte', 'Une boisson au choix offerte', 500, 'free_item', 0, 1),
(2, 'Crêpe sucrée offerte', 'Une crêpe sucrée au choix', 800, 'free_item', 0, 1),
(2, '-200 DA sur commande', 'Réduction de 200 DA', 1000, 'discount_amount', 200.00, 1),
(2, 'Crêpe salée offerte', 'Une crêpe salée signature au choix', 1500, 'free_item', 0, 1);

-- ---------------------------------------------
-- 15. Admin User
-- ---------------------------------------------
INSERT INTO `admin_users` (`restaurant_id`, `username`, `password_hash`, `role`) VALUES
(2, 'admin', '$2y$10$LH.5Uy1h5NkqI5pPJWA3AOP/U6VBHGEPIJGJLr8yLGkMI3hJxKzSK', 'owner');
-- Password: Marvelous2025!

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- VERIFICATION
-- =============================================
SELECT 'Restaurant Le Marvelous créé avec succès!' AS status;
SELECT COUNT(*) AS categories_count FROM categories WHERE restaurant_id = 2;
SELECT COUNT(*) AS products_count FROM products WHERE restaurant_id = 2;
SELECT COUNT(*) AS supplements_count FROM supplements WHERE restaurant_id = 2;
