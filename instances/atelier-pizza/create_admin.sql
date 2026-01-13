-- Créer un utilisateur admin pour le panel d'administration
-- L'Atelier Pizza Roubaix
--
-- Identifiants par défaut :
-- Username: admin
-- Password: admin123
--
-- ⚠️ IMPORTANT: Changez le mot de passe dès la première connexion!

SET NAMES utf8mb4;

-- Supprimer l'admin si il existe déjà (pour réinitialiser)
DELETE FROM admin_users WHERE restaurant_id = 3 AND username = 'admin';

-- Créer l'utilisateur admin
-- Mot de passe: admin123 (hashé avec password_hash PHP)
INSERT INTO admin_users (restaurant_id, username, password_hash, role) VALUES
(3, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner');

-- Vérifier la création
SELECT id, username, role, created_at
FROM admin_users
WHERE restaurant_id = 3;

SELECT '✅ Admin créé avec succès!' as status;
SELECT '   Username: admin' as info;
SELECT '   Password: admin123' as info;
SELECT '   ⚠️  Changez le mot de passe dès la première connexion!' as warning;
