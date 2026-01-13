-- Migration: Amélioration gestion clients
-- Date: 2026-01-09
-- Description: Ajout adresses multiples, préférences, tags

-- ============================================
-- 1. AJOUTER COLONNES À TABLE CUSTOMERS
-- ============================================

-- Adresses multiples (max 2: Maison, Bureau)
-- Structure JSON: [{"type": "home", "label": "Maison", "address": "...", "notes": "...", "is_default": true}]
ALTER TABLE customers
ADD COLUMN addresses JSON DEFAULT NULL
COMMENT 'Adresses multiples (maison, bureau) avec notes';

-- Préférences client
-- Structure JSON: {"allergies": [...], "favorites": [...], "notes": "...", "delivery_instructions": "...", "preferred_time": "..."}
ALTER TABLE customers
ADD COLUMN preferences JSON DEFAULT NULL
COMMENT 'Préférences: allergies, favoris, notes, instructions livraison';

-- Notes admin (texte libre)
ALTER TABLE customers
ADD COLUMN admin_notes TEXT DEFAULT NULL
COMMENT 'Notes privées admin sur le client';

-- ============================================
-- 2. CRÉER TABLE TAGS
-- ============================================

CREATE TABLE IF NOT EXISTS customer_tags (
    customer_id INT UNSIGNED NOT NULL,
    tag VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (customer_id, tag),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,

    INDEX idx_tag (tag),
    INDEX idx_customer_created (customer_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tags de segmentation clients (VIP, Zone, Type)';

-- ============================================
-- 3. TAGS PAR DÉFAUT (INSÉRÉS AUTOMATIQUEMENT)
-- ============================================

-- Tags disponibles:
-- - VIP: Clients avec ≥10 commandes
-- - Zone Centre, Zone Est, Zone Ouest: Segmentation géographique
-- - Livraison, Sur place: Type de commande préféré
-- - Entreprise: Clients professionnels
-- - Régulier: 3-9 commandes
-- - Nouveau: < 3 commandes

-- Assignation automatique tags basés sur données existantes
-- VIP: Clients avec ≥10 commandes
INSERT INTO customer_tags (customer_id, tag)
SELECT id, 'VIP'
FROM customers
WHERE orders_count >= 10
ON DUPLICATE KEY UPDATE tag = tag;

-- Régulier: 3-9 commandes
INSERT INTO customer_tags (customer_id, tag)
SELECT id, 'Régulier'
FROM customers
WHERE orders_count >= 3 AND orders_count < 10
ON DUPLICATE KEY UPDATE tag = tag;

-- Nouveau: < 3 commandes
INSERT INTO customer_tags (customer_id, tag)
SELECT id, 'Nouveau'
FROM customers
WHERE orders_count < 3
ON DUPLICATE KEY UPDATE tag = tag;

-- ============================================
-- 4. INDEXES POUR PERFORMANCE
-- ============================================

-- Index sur orders_count pour filtres rapides
CREATE INDEX IF NOT EXISTS idx_orders_count ON customers(orders_count);

-- Index sur total_spent pour stats
CREATE INDEX IF NOT EXISTS idx_total_spent ON customers(total_spent);

-- Index sur last_order_at pour alertes clients inactifs
CREATE INDEX IF NOT EXISTS idx_last_order ON customers(last_order_at);

-- ============================================
-- 5. VÉRIFICATIONS
-- ============================================

-- Vérifier structure customers
SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'customers'
  AND COLUMN_NAME IN ('addresses', 'preferences', 'admin_notes');

-- Vérifier table tags
SELECT COUNT(*) as total_tags
FROM customer_tags;

-- Vérifier distribution tags
SELECT tag, COUNT(*) as count
FROM customer_tags
GROUP BY tag
ORDER BY count DESC;
