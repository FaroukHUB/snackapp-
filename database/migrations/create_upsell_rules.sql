-- Table upsell_rules pour gérer les suggestions de produits
CREATE TABLE IF NOT EXISTS upsell_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    when_categories JSON NOT NULL COMMENT 'Slugs des catégories qui déclenchent l''upsell',
    suggest_categories JSON NOT NULL COMMENT 'Slugs des catégories à suggérer',
    message VARCHAR(255) NOT NULL DEFAULT 'Un petit kiff avec ceci ?',
    priority INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_restaurant (restaurant_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer une règle par défaut pour le restaurant 3 (L'Atelier Pizza)
INSERT INTO upsell_rules (restaurant_id, when_categories, suggest_categories, message, priority)
VALUES (
    3,
    '["pizzas-sauce-tomate", "pizzas-creme", "pizzas-originales", "pates", "gratins"]',
    '["desserts", "texmex", "boissons"]',
    'Un petit kiff avec ceci ?',
    1
);
