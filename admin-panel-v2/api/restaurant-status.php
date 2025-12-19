<?php
/**
 * API: Gestion du statut du restaurant (ouvert/fermé pour les commandes)
 */

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

    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}
?>
