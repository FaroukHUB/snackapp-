<?php
/**
 * API: Gestion du statut du restaurant (ouvert/fermé pour les commandes)
 */

// Session doit être démarrée AVANT config.php pour éviter les conflits
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Fichier pour stocker le statut
$statusFile = DATA_DIR . 'restaurant-status.json';

// Action demandée
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// Initialiser le fichier si inexistant
if (!file_exists($statusFile)) {
    $defaultStatus = [
        'accepting_orders' => false,
        'last_updated' => date('Y-m-d H:i:s'),
        'updated_by' => 'system'
    ];
    file_put_contents($statusFile, json_encode($defaultStatus, JSON_PRETTY_PRINT));
}

// Lire le statut actuel
function getStatus() {
    global $statusFile;
    $content = file_get_contents($statusFile);
    return json_decode($content, true);
}

// Sauvegarder le statut
function saveStatus($data) {
    global $statusFile;
    return file_put_contents($statusFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Router
switch ($action) {
    case 'get':
        // Récupérer le statut actuel
        $status = getStatus();
        echo json_encode([
            'success' => true,
            'status' => $status
        ]);
        break;

    case 'toggle':
        // Basculer le statut (pour l'admin)
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $status = getStatus();
        $status['accepting_orders'] = !$status['accepting_orders'];
        $status['last_updated'] = date('Y-m-d H:i:s');
        $status['updated_by'] = 'admin';

        if (saveStatus($status)) {
            echo json_encode([
                'success' => true,
                'status' => $status,
                'message' => $status['accepting_orders'] ? 'Restaurant ouvert aux commandes' : 'Restaurant fermé aux commandes'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'open':
        // Ouvrir le restaurant
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $status = getStatus();
        $status['accepting_orders'] = true;
        $status['last_updated'] = date('Y-m-d H:i:s');
        $status['updated_by'] = 'admin';

        if (saveStatus($status)) {
            echo json_encode([
                'success' => true,
                'status' => $status,
                'message' => '✅ Restaurant ouvert aux commandes'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'close':
        // Fermer le restaurant
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $status = getStatus();
        $status['accepting_orders'] = false;
        $status['last_updated'] = date('Y-m-d H:i:s');
        $status['updated_by'] = 'admin';

        if (saveStatus($status)) {
            echo json_encode([
                'success' => true,
                'status' => $status,
                'message' => '🔒 Restaurant fermé aux commandes'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'toggle_delivery':
        // Toggle livraison ON/OFF
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        if (!isset($restaurant['delivery'])) {
            $restaurant['delivery'] = ['enabled' => true];
        }

        $restaurant['delivery']['enabled'] = !$restaurant['delivery']['enabled'];

        if (file_put_contents($restaurantFile, json_encode($restaurant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'delivery_enabled' => $restaurant['delivery']['enabled'],
                'message' => $restaurant['delivery']['enabled'] ? '🚚 Livraison activée' : '🏠 Livraison désactivée'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'get_settings':
        // Récupérer tous les paramètres du restaurant
        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        echo json_encode([
            'success' => true,
            'delivery' => $restaurant['delivery'] ?? ['enabled' => false],
            'platforms' => $restaurant['platforms'] ?? []
        ]);
        break;

    case 'update_platform':
        // Activer/Désactiver une plateforme
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $platformId = $input['platform_id'] ?? null;
        $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : null;

        if (!$platformId) {
            echo json_encode(['success' => false, 'error' => 'ID plateforme manquant']);
            exit;
        }

        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        $found = false;
        foreach ($restaurant['platforms'] as &$platform) {
            if ($platform['id'] === $platformId) {
                $platform['enabled'] = $enabled ?? !($platform['enabled'] ?? true);
                $found = true;
                break;
            }
        }

        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Plateforme introuvable']);
            exit;
        }

        if (file_put_contents($restaurantFile, json_encode($restaurant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'platforms' => $restaurant['platforms'],
                'message' => 'Plateforme mise à jour'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'add_platform':
        // Ajouter une nouvelle plateforme
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($input['name'] ?? '');
        $url = trim($input['url'] ?? '');

        if (!$name || !$url) {
            echo json_encode(['success' => false, 'error' => 'Nom et URL requis']);
            exit;
        }

        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        $id = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        $id = trim($id, '-');

        // Vérifier que l'ID n'existe pas déjà
        foreach ($restaurant['platforms'] as $p) {
            if ($p['id'] === $id) {
                $id .= '-' . rand(100, 999);
                break;
            }
        }

        $newPlatform = [
            'id' => $id,
            'name' => $name,
            'url' => $url,
            'enabled' => true
        ];

        $restaurant['platforms'][] = $newPlatform;

        if (file_put_contents($restaurantFile, json_encode($restaurant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'platform' => $newPlatform,
                'platforms' => $restaurant['platforms'],
                'message' => 'Plateforme ajoutée'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'delete_platform':
        // Supprimer une plateforme
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $platformId = $input['platform_id'] ?? null;

        if (!$platformId) {
            echo json_encode(['success' => false, 'error' => 'ID plateforme manquant']);
            exit;
        }

        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        $restaurant['platforms'] = array_values(array_filter(
            $restaurant['platforms'],
            fn($p) => $p['id'] !== $platformId
        ));

        if (file_put_contents($restaurantFile, json_encode($restaurant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'platforms' => $restaurant['platforms'],
                'message' => 'Plateforme supprimée'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    case 'save_theme':
        // Sauvegarder les couleurs du thème
        if (!isset($_SESSION['admin_logged_in'])) {
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $restaurantFile = dirname(dirname(__DIR__)) . '/config/restaurant.json';
        $restaurant = json_decode(file_get_contents($restaurantFile), true);

        if (!isset($restaurant['theme'])) {
            $restaurant['theme'] = ['colors' => []];
        }
        if (!isset($restaurant['theme']['colors'])) {
            $restaurant['theme']['colors'] = [];
        }

        // Mettre à jour les couleurs
        $colorFields = ['primary', 'secondary', 'accent', 'background', 'cardBg', 'text', 'textMuted'];
        foreach ($colorFields as $field) {
            if (isset($input[$field])) {
                $restaurant['theme']['colors'][$field] = $input[$field];
            }
        }

        if (file_put_contents($restaurantFile, json_encode($restaurant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode([
                'success' => true,
                'theme' => $restaurant['theme'],
                'message' => 'Couleurs sauvegardées'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur de sauvegarde']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}
?>
