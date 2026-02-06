<?php
/**
 * API: Gestion du statut du restaurant (ouvert/fermé pour les commandes)
 * Version 2.0 - Utilise la base de données MySQL
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json');

// CORS centralisé - API publique
handlePublicCors();

// Action demandée
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

// Vérifier que la BD est disponible
if (!defined('SNACK_RESTAURANT_ID')) {
    echo json_encode(['success' => false, 'error' => 'Configuration manquante']);
    exit;
}

$restaurantId = SNACK_RESTAURANT_ID;

// Router
switch ($action) {
    case 'get':
        // Récupérer le statut actuel
        $acceptingOrders = RestaurantRepository::isAcceptingOrders($restaurantId);
        $deliveryEnabled = RestaurantRepository::isDeliveryEnabled($restaurantId);

        echo json_encode([
            'success' => true,
            'status' => [
                'accepting_orders' => $acceptingOrders,
                'delivery_enabled' => $deliveryEnabled,
                'last_updated' => date('Y-m-d H:i:s')
            ]
        ]);
        break;

    case 'toggle':
        // Basculer le statut (pour l'admin)
        requireAdmin();

        $newStatus = RestaurantRepository::toggleAcceptingOrders($restaurantId);
        echo json_encode([
            'success' => true,
            'status' => [
                'accepting_orders' => $newStatus,
                'last_updated' => date('Y-m-d H:i:s')
            ],
            'message' => $newStatus ? 'Restaurant ouvert aux commandes' : 'Restaurant fermé aux commandes'
        ]);
        break;

    case 'open':
        // Ouvrir le restaurant
        requireAdmin();

        RestaurantRepository::toggleAcceptingOrders($restaurantId, true);
        echo json_encode([
            'success' => true,
            'status' => [
                'accepting_orders' => true,
                'last_updated' => date('Y-m-d H:i:s')
            ],
            'message' => '✅ Restaurant ouvert aux commandes'
        ]);
        break;

    case 'close':
        // Fermer le restaurant
        requireAdmin();

        RestaurantRepository::toggleAcceptingOrders($restaurantId, false);
        echo json_encode([
            'success' => true,
            'status' => [
                'accepting_orders' => false,
                'last_updated' => date('Y-m-d H:i:s')
            ],
            'message' => '🔒 Restaurant fermé aux commandes'
        ]);
        break;

    case 'toggle_delivery':
        // Toggle livraison ON/OFF
        requireAdmin();

        $newStatus = RestaurantRepository::toggleDelivery($restaurantId);
        echo json_encode([
            'success' => true,
            'delivery_enabled' => $newStatus,
            'message' => $newStatus ? '🚚 Livraison activée' : '🏠 Livraison désactivée'
        ]);
        break;

    case 'get_settings':
        // Récupérer tous les paramètres du restaurant
        $deliveryEnabled = RestaurantRepository::isDeliveryEnabled($restaurantId);
        $platforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);

        // Transformer les plateformes au format attendu
        $platformsFormatted = array_map(function($p) {
            return [
                'id' => $p['slug'],
                'name' => $p['name'],
                'url' => $p['url'],
                'icon' => $p['icon'],
                'enabled' => (bool) $p['is_enabled']
            ];
        }, $platforms);

        echo json_encode([
            'success' => true,
            'delivery' => ['enabled' => $deliveryEnabled],
            'platforms' => $platformsFormatted
        ]);
        break;

    case 'update_platform':
        // Activer/Désactiver une plateforme
        requireAdmin();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $platformId = $input['platform_id'] ?? null;
        $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : null;

        if (!$platformId) {
            echo json_encode(['success' => false, 'error' => 'ID plateforme manquant']);
            exit;
        }

        // Trouver la plateforme par slug
        $platforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);
        $platform = null;
        foreach ($platforms as $p) {
            if ($p['slug'] === $platformId) {
                $platform = $p;
                break;
            }
        }

        if (!$platform) {
            echo json_encode(['success' => false, 'error' => 'Plateforme introuvable']);
            exit;
        }

        // Si enabled n'est pas spécifié, toggle
        if ($enabled === null) {
            RestaurantRepository::toggleDeliveryPlatform($platform['id']);
        } else {
            RestaurantRepository::updateDeliveryPlatform($platform['id'], ['enabled' => $enabled]);
        }

        // Retourner les plateformes mises à jour
        $updatedPlatforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);
        $platformsFormatted = array_map(function($p) {
            return [
                'id' => $p['slug'],
                'name' => $p['name'],
                'url' => $p['url'],
                'enabled' => (bool) $p['is_enabled']
            ];
        }, $updatedPlatforms);

        echo json_encode([
            'success' => true,
            'platforms' => $platformsFormatted,
            'message' => 'Plateforme mise à jour'
        ]);
        break;

    case 'add_platform':
        // Ajouter une nouvelle plateforme
        requireAdmin();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($input['name'] ?? '');
        $url = trim($input['url'] ?? '');

        if (!$name || !$url) {
            echo json_encode(['success' => false, 'error' => 'Nom et URL requis']);
            exit;
        }

        $newId = RestaurantRepository::addDeliveryPlatform($restaurantId, [
            'name' => $name,
            'url' => $url,
            'enabled' => true
        ]);

        // Retourner les plateformes mises à jour
        $platforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);
        $platformsFormatted = array_map(function($p) {
            return [
                'id' => $p['slug'],
                'name' => $p['name'],
                'url' => $p['url'],
                'enabled' => (bool) $p['is_enabled']
            ];
        }, $platforms);

        echo json_encode([
            'success' => true,
            'platforms' => $platformsFormatted,
            'message' => 'Plateforme ajoutée'
        ]);
        break;

    case 'delete_platform':
        // Supprimer une plateforme
        requireAdmin();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $platformId = $input['platform_id'] ?? null;

        if (!$platformId) {
            echo json_encode(['success' => false, 'error' => 'ID plateforme manquant']);
            exit;
        }

        // Trouver la plateforme par slug
        $platforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);
        $platform = null;
        foreach ($platforms as $p) {
            if ($p['slug'] === $platformId) {
                $platform = $p;
                break;
            }
        }

        if (!$platform) {
            echo json_encode(['success' => false, 'error' => 'Plateforme introuvable']);
            exit;
        }

        RestaurantRepository::deleteDeliveryPlatform($platform['id']);

        // Retourner les plateformes mises à jour
        $updatedPlatforms = RestaurantRepository::getDeliveryPlatforms($restaurantId);
        $platformsFormatted = array_map(function($p) {
            return [
                'id' => $p['slug'],
                'name' => $p['name'],
                'url' => $p['url'],
                'enabled' => (bool) $p['is_enabled']
            ];
        }, $updatedPlatforms);

        echo json_encode([
            'success' => true,
            'platforms' => $platformsFormatted,
            'message' => 'Plateforme supprimée'
        ]);
        break;

    case 'save_theme':
        // Sauvegarder les couleurs du thème
        requireAdmin();

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $colors = [];
        if (isset($input['primary'])) $colors['primary'] = $input['primary'];
        if (isset($input['primaryDark'])) $colors['primaryDark'] = $input['primaryDark'];
        if (isset($input['secondary'])) $colors['secondary'] = $input['secondary'];
        if (isset($input['accent'])) $colors['accent'] = $input['accent'];

        if (!empty($colors)) {
            RestaurantRepository::updateTheme($restaurantId, $colors);
        }

        $theme = RestaurantRepository::getTheme($restaurantId);

        echo json_encode([
            'success' => true,
            'theme' => $theme,
            'message' => 'Couleurs sauvegardées'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}
