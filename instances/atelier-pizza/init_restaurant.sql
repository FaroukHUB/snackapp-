-- Script d'initialisation : L'Atelier Pizza Roubaix
-- Date : 2025-01-13
-- Description : Crée le restaurant et ses paramètres de base

SET NAMES utf8mb4;

-- ==================== RESTAURANT ====================
-- Insérer le restaurant principal (forcer l'ID à 3)

INSERT INTO restaurants (id, slug, name, logo, primary_color, is_active) VALUES
(3, 'atelier-pizza-roubaix', 'L''Atelier Pizza', NULL, '#c10000', 1)
ON DUPLICATE KEY UPDATE
  slug = 'atelier-pizza-roubaix',
  name = 'L''Atelier Pizza',
  primary_color = '#c10000',
  is_active = 1;

-- ==================== PARAMÈTRES ====================
-- Configurer les infos du restaurant

INSERT INTO restaurant_settings (restaurant_id, phone, address, city, postal_code, accepting_orders, loyalty_enabled, loyalty_points_per_euro) VALUES
(3, '0320363948', '70 Boulevard de la République', 'Roubaix', '59100', 1, 1, 10)
ON DUPLICATE KEY UPDATE
  phone = '0320363948',
  address = '70 Boulevard de la République',
  city = 'Roubaix',
  postal_code = '59100',
  accepting_orders = 1,
  loyalty_enabled = 1,
  loyalty_points_per_euro = 10;

-- ==================== HORAIRES ====================
-- Lundi à Jeudi : 11h30-14h et 18h-23h
-- Vendredi : 11h30-14h et 18h-23h30
-- Samedi : 11h30-23h30 (service continu)
-- Dimanche : 18h-23h

INSERT INTO opening_hours (restaurant_id, day_of_week, opens, closes, is_closed) VALUES
(3, 0, '11:30:00', '14:00:00', 0),  -- Lundi matin
(3, 1, '11:30:00', '14:00:00', 0),  -- Mardi matin
(3, 2, '11:30:00', '14:00:00', 0),  -- Mercredi matin
(3, 3, '11:30:00', '14:00:00', 0),  -- Jeudi matin
(3, 4, '11:30:00', '14:00:00', 0),  -- Vendredi matin
(3, 5, '11:30:00', '23:30:00', 0),  -- Samedi (service continu)
(3, 6, '18:00:00', '23:00:00', 0)   -- Dimanche soir uniquement
ON DUPLICATE KEY UPDATE
  opens = VALUES(opens),
  closes = VALUES(closes),
  is_closed = VALUES(is_closed);

-- Note: Ce schéma simple ne gère qu'une plage horaire par jour
-- Pour Lundi-Vendredi avec coupure déjeuner/dîner, il faudra modifier le schéma
-- ou gérer ça dans la logique applicative avec la config JS

SELECT 'Restaurant Atelier Pizza créé avec succès!' as status;
