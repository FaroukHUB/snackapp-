<?php
/**
 * API de gestion des utilisateurs admin
 * Permet de créer, modifier, supprimer des admins
 */

require_once __DIR__ . '/../bootstrap.php';

// Vérifier que MySQL est actif
if (SNACK_USE_JSON || defined('SNACK_DB_ERROR')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cette fonctionnalité nécessite MySQL']);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Vérifier le token CSRF pour toutes les opérations de modification
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    verifyCsrfToken();
}

// Seul un owner peut gérer les admins
$currentAdminRole = $_SESSION['admin_role'] ?? 'staff';
if ($currentAdminRole !== 'owner') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé. Seul un propriétaire peut gérer les admins.']);
    exit;
}

$restaurantId = SNACK_RESTAURANT_ID;

// Récupérer l'action
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action manquante']);
    exit;
}

switch ($action) {
    case 'list':
        // Lister tous les admins du restaurant
        $admins = Database::fetchAll(
            "SELECT id, username, role, last_login, created_at
             FROM admin_users
             WHERE restaurant_id = ?
             ORDER BY created_at DESC",
            [$restaurantId]
        );

        echo json_encode([
            'success' => true,
            'admins' => $admins
        ]);
        break;

    case 'create':
        // Créer un nouvel admin
        $data = json_decode(file_get_contents('php://input'), true);

        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'staff';

        // Validation
        if (empty($username)) {
            echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur requis']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur trop court (min 3 caractères)']);
            exit;
        }

        if (empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Mot de passe requis']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mot de passe trop court (min 6 caractères)']);
            exit;
        }

        if (!in_array($role, ['owner', 'manager', 'staff'])) {
            echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
            exit;
        }

        // Vérifier que l'username n'existe pas déjà
        $existing = Database::fetchOne(
            "SELECT id FROM admin_users WHERE restaurant_id = ? AND username = ?",
            [$restaurantId, $username]
        );

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà']);
            exit;
        }

        // Hasher le mot de passe (fallback à BCRYPT si ARGON2ID pas dispo)
        if (defined('PASSWORD_ARGON2ID')) {
            $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        } else {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        }

        // Créer l'admin
        try {
            $adminId = Database::insert('admin_users', [
                'restaurant_id' => $restaurantId,
                'username' => $username,
                'password_hash' => $passwordHash,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            if ($adminId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin créé avec succès',
                    'admin_id' => $adminId
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la création (ID non retourné)']);
            }
        } catch (Exception $e) {
            error_log('Erreur création admin: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
        break;

    case 'update':
        // Modifier un admin existant
        $data = json_decode(file_get_contents('php://input'), true);

        $adminId = (int)($data['admin_id'] ?? 0);
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? '';

        if (!$adminId) {
            echo json_encode(['success' => false, 'message' => 'ID admin manquant']);
            exit;
        }

        // Vérifier que l'admin existe et appartient au restaurant
        $admin = Database::fetchOne(
            "SELECT id FROM admin_users WHERE id = ? AND restaurant_id = ?",
            [$adminId, $restaurantId]
        );

        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'Admin non trouvé']);
            exit;
        }

        // Ne pas permettre de modifier son propre compte (pour éviter de se bloquer)
        if ($adminId == ($_SESSION['admin_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas modifier votre propre compte']);
            exit;
        }

        $updateData = [];

        // Mettre à jour le username si fourni
        if (!empty($username)) {
            if (strlen($username) < 3) {
                echo json_encode(['success' => false, 'message' => 'Nom d\'utilisateur trop court (min 3 caractères)']);
                exit;
            }

            // Vérifier que l'username n'est pas déjà pris par un autre admin
            $existing = Database::fetchOne(
                "SELECT id FROM admin_users WHERE restaurant_id = ? AND username = ? AND id != ?",
                [$restaurantId, $username, $adminId]
            );

            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà pris']);
                exit;
            }

            $updateData['username'] = $username;
        }

        // Mettre à jour le mot de passe si fourni
        if (!empty($password)) {
            if (strlen($password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mot de passe trop court (min 6 caractères)']);
                exit;
            }

            $updateData['password_hash'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        // Mettre à jour le rôle si fourni
        if (!empty($role)) {
            if (!in_array($role, ['owner', 'manager', 'staff'])) {
                echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
                exit;
            }

            $updateData['role'] = $role;
        }

        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'Aucune donnée à mettre à jour']);
            exit;
        }

        // Effectuer la mise à jour
        $updated = Database::update('admin_users', $updateData, ['id' => $adminId]);

        if ($updated) {
            echo json_encode([
                'success' => true,
                'message' => 'Admin mis à jour avec succès'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        break;

    case 'delete':
        // Supprimer un admin
        $data = json_decode(file_get_contents('php://input'), true);

        $adminId = (int)($data['admin_id'] ?? 0);

        if (!$adminId) {
            echo json_encode(['success' => false, 'message' => 'ID admin manquant']);
            exit;
        }

        // Vérifier que l'admin existe et appartient au restaurant
        $admin = Database::fetchOne(
            "SELECT id, role FROM admin_users WHERE id = ? AND restaurant_id = ?",
            [$adminId, $restaurantId]
        );

        if (!$admin) {
            echo json_encode(['success' => false, 'message' => 'Admin non trouvé']);
            exit;
        }

        // Ne pas permettre de supprimer son propre compte
        if ($adminId == ($_SESSION['admin_id'] ?? 0)) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
            exit;
        }

        // Compter le nombre d'owners restants
        $ownerCount = Database::fetchOne(
            "SELECT COUNT(*) as count FROM admin_users WHERE restaurant_id = ? AND role = 'owner'",
            [$restaurantId]
        );

        // Si c'est le dernier owner, ne pas permettre la suppression
        if ($admin['role'] === 'owner' && $ownerCount['count'] <= 1) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer le dernier propriétaire']);
            exit;
        }

        // Supprimer l'admin
        $deleted = Database::delete('admin_users', ['id' => $adminId]);

        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Admin supprimé avec succès'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
        break;
}
