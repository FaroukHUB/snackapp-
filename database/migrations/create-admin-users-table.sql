-- Création de la table admin_users si elle n'existe pas
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT UNSIGNED NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('owner', 'manager', 'staff') DEFAULT 'staff',
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_restaurant_username` (`restaurant_id`, `username`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un admin par défaut si la table est vide
INSERT IGNORE INTO `admin_users` (restaurant_id, username, password_hash, role, created_at)
SELECT 1, 'admin', '$2y$12$jVmFNt0ChRiQd4KkSnEbOeIoebjitflFUP4.xhcW8z3pkGuMN2CUe', 'owner', NOW()
WHERE NOT EXISTS (SELECT 1 FROM admin_users LIMIT 1);

-- Note: Le mot de passe par défaut est "admin123"
-- Hash généré avec PASSWORD_BCRYPT pour compatibilité maximale
-- IMPORTANT : Changez ce mot de passe après la première connexion !
