-- Migration : Système de prix dégressifs (bundles)
-- Date : 2025-01-13
-- Instance : Atelier Pizza Roubaix (applicable à toutes les instances)

-- Ajouter colonnes pour gérer les bundles (ex: 2 pizzas = prix réduit)
ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_enabled TINYINT(1) DEFAULT 0
COMMENT 'Active le système de bundle (0=non, 1=oui)';

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_quantity INT DEFAULT 2
COMMENT 'Quantité pour activer le bundle (ex: 2 pour "achetez 2")';

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_price_solo DECIMAL(10,2) DEFAULT NULL
COMMENT 'Prix total pour le bundle Solo (ex: 13€ pour 2 pizzas Solo au lieu de 15€)';

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_price_duo DECIMAL(10,2) DEFAULT NULL
COMMENT 'Prix total pour le bundle Duo (ex: 15€ pour 2 pizzas Duo au lieu de 18€)';

-- Index pour optimiser les requêtes
ALTER TABLE products
ADD INDEX idx_bundle_enabled (bundle_enabled);

-- Commentaires
ALTER TABLE products
MODIFY COLUMN bundle_enabled TINYINT(1) DEFAULT 0
COMMENT 'Système de prix dégressifs activé (1) ou non (0)';

-- Exemple d'utilisation :
-- Pizza REINE : price_solo=7.50, price_duo=9.00
-- bundle_enabled=1, bundle_quantity=2
-- bundle_price_solo=13.00 (au lieu de 15.00)
-- bundle_price_duo=15.00 (au lieu de 18.00)
--
-- Logique frontend :
-- Si le client ajoute 2 pizzas Solo → économie de 2€
-- Si le client ajoute 2 pizzas Duo → économie de 3€
