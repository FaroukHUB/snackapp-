-- ============================================================================
-- SEED: Données initiales du système de cuisine types
-- Date: 2026-03-20
-- Description: Peuplement des types de cuisine, étapes et options exemples
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. TYPES DE CUISINE (11 types)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_types` (`id`, `name`, `slug`, `icon_slug`, `description`, `sort_order`) VALUES
(1, 'Tacos', 'tacos', 'icon-tacos', 'Tacos français avec viandes et sauces fromagères', 10),
(2, 'Burger', 'burger', 'icon-burger', 'Burgers avec steaks, garnitures et sauces', 20),
(3, 'Kebab', 'kebab', 'icon-kebab', 'Kebabs et sandwichs viandes grillées', 30),
(4, 'Pizza', 'pizza', 'icon-pizza', 'Pizzas avec bases et ingrédients personnalisables', 40),
(5, 'Pâtes', 'pates', 'icon-pates', 'Pâtes avec sauces et accompagnements', 50),
(6, 'Riz Crousty', 'riz_crousty', 'icon-riz-crousty', 'Riz croustillant avec viandes et sauces', 60),
(7, 'Sandwich', 'sandwich', 'icon-sandwich', 'Sandwichs et paninis variés', 70),
(8, 'Sushi', 'sushi', 'icon-sushi', 'Sushis, makis et california rolls', 80),
(9, 'Bowl', 'bowl', 'icon-bowl', 'Bowls composés (poké, buddha, etc.)', 90),
(10, 'Boissons', 'boissons', 'icon-drink', 'Boissons froides et chaudes', 100),
(11, 'Desserts', 'desserts', 'icon-dessert', 'Desserts et sucreries', 110);

-- ----------------------------------------------------------------------------
-- 2. ÉTAPES POUR TACOS (id=1)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
-- Étape 1 : Taille
(1, 'Taille', 'taille', 'Format du tacos', TRUE, 1, 1, FALSE, TRUE, 10),
-- Étape 2 : Viandes (jusqu'à 3)
(1, 'Viande(s)', 'viandes', 'Choix des viandes (jusqu\'à 3)', TRUE, 1, 3, FALSE, TRUE, 20),
-- Étape 3 : Sauce fromagère
(1, 'Sauce fromagère', 'sauce-fromagere', 'Sauce au fromage fondu', TRUE, 1, 1, FALSE, FALSE, 30),
-- Étape 4 : Crudités
(1, 'Crudités', 'crudites', 'Salade, tomates, oignons...', FALSE, 0, 0, TRUE, FALSE, 40),
-- Étape 5 : Sauces
(1, 'Sauces', 'sauces', 'Sauces supplémentaires', FALSE, 0, 3, FALSE, FALSE, 50);

-- ----------------------------------------------------------------------------
-- 3. OPTIONS POUR TACOS
-- ----------------------------------------------------------------------------
-- Options Taille (step_id sera récupéré via la requête)
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='taille'), 'S', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='taille'), 'M', 1.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='taille'), 'L', 2.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='taille'), 'XL', 3.50, 40);

-- Options Viandes
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Poulet', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Bœuf', 0.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Cordon bleu', 1.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Kefta', 1.00, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Merguez', 0.50, 50),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='viandes'), 'Nuggets', 0.50, 60);

-- Options Sauce fromagère
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauce-fromagere'), 'Cheddar', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauce-fromagere'), 'Raclette', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauce-fromagere'), 'Biggy', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauce-fromagere'), 'Fromagère maison', 0.00, 40);

-- Options Crudités
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='crudites'), 'Salade', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='crudites'), 'Tomates', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='crudites'), 'Oignons', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='crudites'), 'Cornichons', 0.00, 40);

-- Options Sauces
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Harissa', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Algérienne', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Samouraï', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Blanche', 0.00, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Ketchup', 0.00, 50),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=1 AND slug='sauces'), 'Mayonnaise', 0.00, 60);

-- ----------------------------------------------------------------------------
-- 4. ÉTAPES POUR BURGER (id=2)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
(2, 'Pain', 'pain', 'Type de pain', FALSE, 1, 1, FALSE, FALSE, 10),
(2, 'Viande', 'viande', 'Steak ou poulet', TRUE, 1, 1, FALSE, TRUE, 20),
(2, 'Fromage', 'fromage', 'Type de fromage', FALSE, 0, 2, FALSE, TRUE, 30),
(2, 'Garnitures', 'garnitures', 'Salade, tomates, oignons...', FALSE, 0, 0, TRUE, FALSE, 40),
(2, 'Sauces', 'sauces', 'Jusqu\'à 3 sauces', FALSE, 0, 3, FALSE, FALSE, 50);

-- Options Pain
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='pain'), 'Classique', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='pain'), 'Sésame', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='pain'), 'Complet', 0.50, 30);

-- Options Viande
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='viande'), 'Steak bœuf 100g', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='viande'), 'Steak bœuf 200g', 2.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='viande'), 'Poulet pané', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='viande'), 'Poulet grillé', 0.00, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='viande'), 'Végétarien', 0.00, 50);

-- Options Fromage
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='fromage'), 'Cheddar', 0.50, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='fromage'), 'Raclette', 0.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='fromage'), 'Bleu', 0.70, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='fromage'), 'Chèvre', 0.70, 40);

-- Options Garnitures
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='garnitures'), 'Salade', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='garnitures'), 'Tomates', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='garnitures'), 'Oignons rouges', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='garnitures'), 'Cornichons', 0.00, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='garnitures'), 'Bacon', 1.50, 50);

-- Options Sauces
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='sauces'), 'Ketchup', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='sauces'), 'Mayonnaise', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='sauces'), 'Moutarde', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='sauces'), 'BBQ', 0.00, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=2 AND slug='sauces'), 'Burger sauce', 0.00, 50);

-- ----------------------------------------------------------------------------
-- 5. ÉTAPES POUR KEBAB (id=3)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
(3, 'Format', 'format', 'Sandwich, assiette ou galette', TRUE, 1, 1, FALSE, TRUE, 10),
(3, 'Viande', 'viande', 'Type de viande', TRUE, 1, 1, FALSE, TRUE, 20),
(3, 'Crudités', 'crudites', 'Salade, tomates, oignons...', FALSE, 0, 0, TRUE, FALSE, 30),
(3, 'Sauces', 'sauces', 'Jusqu\'à 2 sauces', FALSE, 0, 2, FALSE, FALSE, 40);

-- Options Format
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='format'), 'Sandwich', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='format'), 'Galette', 0.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='format'), 'Assiette', 2.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='format'), 'Barquette', 1.50, 40);

-- Options Viande
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='viande'), 'Poulet', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='viande'), 'Dinde', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='viande'), 'Mixte', 0.50, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='viande'), 'Kefta', 0.50, 40);

-- Options Crudités
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='crudites'), 'Salade', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='crudites'), 'Tomates', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='crudites'), 'Oignons', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='crudites'), 'Concombre', 0.00, 40);

-- Options Sauces
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='sauces'), 'Blanche', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='sauces'), 'Harissa', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='sauces'), 'Algérienne', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=3 AND slug='sauces'), 'Samouraï', 0.00, 40);

-- ----------------------------------------------------------------------------
-- 6. ÉTAPES POUR PIZZA (id=4)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
(4, 'Taille', 'taille', 'Diamètre de la pizza', TRUE, 1, 1, FALSE, TRUE, 10),
(4, 'Base', 'base', 'Sauce tomate, crème...', TRUE, 1, 1, FALSE, FALSE, 20),
(4, 'Ingrédients', 'ingredients', 'Ingrédients supplémentaires', FALSE, 0, 0, FALSE, TRUE, 30);

-- Options Taille
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='taille'), '26cm (Small)', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='taille'), '33cm (Medium)', 3.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='taille'), '40cm (Large)', 6.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='taille'), '50cm (XL)', 10.00, 40);

-- Options Base
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='base'), 'Sauce tomate', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='base'), 'Crème fraîche', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='base'), 'Barbecue', 0.50, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='base'), 'Pesto', 0.50, 40);

-- Options Ingrédients
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Fromage supplément', 1.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Jambon', 1.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Champignons', 1.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Olives', 0.80, 40),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Poivrons', 1.00, 50),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=4 AND slug='ingredients'), 'Oignons', 0.80, 60);

-- ----------------------------------------------------------------------------
-- 7. ÉTAPES POUR PÂTES (id=5)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
(5, 'Type de pâtes', 'type-pates', 'Spaghetti, penne, tagliatelles...', TRUE, 1, 1, FALSE, FALSE, 10),
(5, 'Sauce', 'sauce', 'Sauce principale', TRUE, 1, 1, FALSE, TRUE, 20),
(5, 'Accompagnements', 'accompagnements', 'Viande, fromage...', FALSE, 0, 0, FALSE, TRUE, 30);

-- Options Type de pâtes
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='type-pates'), 'Spaghetti', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='type-pates'), 'Penne', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='type-pates'), 'Tagliatelles', 0.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='type-pates'), 'Fusilli', 0.00, 40);

-- Options Sauce
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='sauce'), 'Carbonara', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='sauce'), 'Bolognaise', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='sauce'), '4 Fromages', 1.00, 30),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='sauce'), 'Pesto', 0.50, 40);

-- Options Accompagnements
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='accompagnements'), 'Poulet grillé', 2.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='accompagnements'), 'Lardons', 1.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=5 AND slug='accompagnements'), 'Parmesan', 1.00, 30);

-- ----------------------------------------------------------------------------
-- 8. ÉTAPES POUR RIZ CROUSTY (id=6)
-- ----------------------------------------------------------------------------
INSERT INTO `cuisine_type_steps` (`cuisine_type_id`, `name`, `slug`, `description`, `is_required_default`, `min_choices_default`, `max_choices_default`, `allow_removal_default`, `has_price_modifier_default`, `sort_order`) VALUES
(6, 'Taille', 'taille', 'Format du riz crousty', TRUE, 1, 1, FALSE, TRUE, 10),
(6, 'Viande', 'viande', 'Type de viande', TRUE, 1, 2, FALSE, TRUE, 20),
(6, 'Sauce', 'sauce', 'Sauce principale', TRUE, 1, 1, FALSE, FALSE, 30);

-- Options Taille
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='taille'), 'Small', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='taille'), 'Medium', 1.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='taille'), 'Large', 3.00, 30);

-- Options Viande
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='viande'), 'Poulet', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='viande'), 'Bœuf', 0.50, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='viande'), 'Cordon bleu', 1.00, 30);

-- Options Sauce
INSERT INTO `cuisine_type_step_options` (`cuisine_type_step_id`, `name`, `price_modifier`, `sort_order`) VALUES
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='sauce'), 'Algérienne', 0.00, 10),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='sauce'), 'Samouraï', 0.00, 20),
((SELECT id FROM cuisine_type_steps WHERE cuisine_type_id=6 AND slug='sauce'), 'Biggy', 0.00, 30);

-- ----------------------------------------------------------------------------
-- 9-11. TYPES SIMPLES (Sandwich, Sushi, Bowl) - Pas d'étapes par défaut
-- Ces types n'ont pas d'étapes prédéfinies, les restaurateurs les créent
-- ----------------------------------------------------------------------------

-- ----------------------------------------------------------------------------
-- 12. BOISSONS & DESSERTS (Pas d'étapes, catégories communes)
-- ----------------------------------------------------------------------------
-- Les types "Boissons" et "Desserts" n'ont pas d'étapes de composition
-- Ils servent uniquement à catégoriser les produits simples

-- ============================================================================
-- FIN DU SEED
-- Total: 11 types, ~35 étapes, ~100 options
-- ============================================================================
