-- Migration : Ajout des colonnes manquantes pour Snackup v2
-- Date : 2025-01-13
-- Description : Ajoute icon/flavor aux catégories et bundle pricing aux produits

-- ==================== TABLE: categories ====================
-- Ajouter colonnes icon et flavor

ALTER TABLE categories
ADD COLUMN IF NOT EXISTS icon VARCHAR(10) NULL COMMENT 'Emoji pour la catégorie' AFTER description;

ALTER TABLE categories
ADD COLUMN IF NOT EXISTS flavor ENUM('sale', 'sucre') NULL COMMENT 'Type de produits (salé/sucré)' AFTER icon;

-- ==================== TABLE: products ====================
-- Ajouter colonnes pour le bundle pricing (2 pizzas = prix réduit)

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_enabled TINYINT(1) DEFAULT 0 COMMENT 'Activer remise pour quantité' AFTER sort_order;

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_quantity INT DEFAULT 2 COMMENT 'Quantité pour remise (ex: 2 pizzas)' AFTER bundle_enabled;

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_price_solo DECIMAL(10,2) DEFAULT NULL COMMENT 'Prix pour 2x Solo (ex: 13€)' AFTER bundle_quantity;

ALTER TABLE products
ADD COLUMN IF NOT EXISTS bundle_price_duo DECIMAL(10,2) DEFAULT NULL COMMENT 'Prix pour 2x Duo (ex: 15€)' AFTER bundle_price_solo;

-- Ajouter index pour optimiser les requêtes
ALTER TABLE products
ADD INDEX IF NOT EXISTS idx_bundle_enabled (bundle_enabled);
