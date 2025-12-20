<?php
/**
 * SnackApp v1 - Public API
 * API pour le site public (menu, restaurant, commandes)
 * Sans authentification requise
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Charger le bootstrap admin (contient Database et Repositories)
require_once __DIR__ . '/../admin-panel-v2/bootstrap.php';

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

// Router basé sur le paramètre 'endpoint'
$endpoint = $_GET['endpoint'] ?? $_GET['action'] ?? '';

switch ($endpoint) {

    case 'menu':
        getMenu($useMySQL);
        break;

    case 'restaurant':
        getRestaurant($useMySQL);
        break;

    case 'status':
        getStatus($useMySQL);
        break;

    case 'order':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            createOrder($useMySQL);
        } else {
            jsonError('Méthode non autorisée', 405);
        }
        break;

    case 'check-order':
        checkOrder($useMySQL);
        break;

    case 'loyalty':
        getLoyaltyInfo($useMySQL);
        break;

    default:
        jsonError('Endpoint invalide. Disponibles: menu, restaurant, status, order, loyalty', 400);
}

/* =========================
   ENDPOINTS
   ========================= */

/**
 * GET /api/public.php?endpoint=menu
 * Retourne le menu complet (catégories, produits, suppléments)
 */
function getMenu(bool $useMySQL) {
    if ($useMySQL) {
        $menu = MenuRepository::getFullMenu(SNACK_RESTAURANT_ID);

        jsonSuccess([
            'version' => 1,
            'lastUpdated' => date('c'),
            'menu' => $menu['menu'],
            'supplements' => $menu['supplements']
        ]);
    } else {
        // Fallback: lire les fichiers JSON
        $menuPath = SNACK_CONFIG_PATH . '/menu.json';
        $runtimePath = SNACK_CONFIG_PATH . '/menu.runtime.json';

        if (file_exists($runtimePath)) {
            $data = json_decode(file_get_contents($runtimePath), true);
        } elseif (file_exists($menuPath)) {
            $data = json_decode(file_get_contents($menuPath), true);
        } else {
            jsonError('Menu introuvable');
        }

        jsonSuccess([
            'version' => $data['version'] ?? 1,
            'lastUpdated' => $data['lastUpdated'] ?? date('c'),
            'menu' => $data['menu'] ?? ['categories' => []],
            'supplements' => $data['supplements'] ?? ['catalog' => [], 'defaultForCategories' => []]
        ]);
    }
}

/**
 * GET /api/public.php?endpoint=restaurant
 * Retourne les infos du restaurant (contact, horaires, FAQ, etc.)
 */
function getRestaurant(bool $useMySQL) {
    if ($useMySQL) {
        $data = RestaurantRepository::getPublicData(SNACK_RESTAURANT_ID);
        jsonSuccess($data);
    } else {
        // Fallback: lire restaurant.json
        $path = SNACK_CONFIG_PATH . '/restaurant.json';

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            jsonSuccess($data);
        } else {
            jsonError('Configuration restaurant introuvable');
        }
    }
}

/**
 * GET /api/public.php?endpoint=status
 * Retourne si le restaurant accepte les commandes
 */
function getStatus(bool $useMySQL) {
    if ($useMySQL) {
        $accepting = RestaurantRepository::isAcceptingOrders(SNACK_RESTAURANT_ID);
        jsonSuccess(['accepting_orders' => $accepting]);
    } else {
        // Fallback
        $path = SNACK_ROOT . '/admin-panel-v2/data/restaurant-status.json';

        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);
            jsonSuccess(['accepting_orders' => $data['accepting_orders'] ?? true]);
        } else {
            jsonSuccess(['accepting_orders' => true]);
        }
    }
}

/**
 * POST /api/public.php?endpoint=order
 * Crée une nouvelle commande depuis le site public
 */
function createOrder(bool $useMySQL) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonError('Données invalides');
    }

    // Validation
    $required = ['customer_phone', 'items', 'total'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            jsonError("Champ manquant: $field");
        }
    }

    // Vérifier si le restaurant accepte les commandes
    if ($useMySQL) {
        if (!RestaurantRepository::isAcceptingOrders(SNACK_RESTAURANT_ID)) {
            jsonError('Le restaurant n\'accepte pas les commandes actuellement', 503);
        }
    }

    // Créer la commande via l'API orders
    if ($useMySQL) {
        try {
            $orderId = OrderRepository::createOrder(SNACK_RESTAURANT_ID, [
                'customer_name' => $input['customer_name'] ?? 'Client',
                'customer_phone' => $input['customer_phone'],
                'items' => $input['items'],
                'subtotal' => $input['subtotal'] ?? $input['total'],
                'total' => (float)$input['total'],
                'notes' => $input['notes'] ?? null,
                'loyalty_reward_id' => $input['loyalty_reward_id'] ?? null
            ]);

            $order = OrderRepository::getById($orderId);

            jsonSuccess([
                'order_id' => $order['order_number'],
                'message' => 'Commande créée avec succès',
                'estimated_time' => '15-20 min'
            ]);
        } catch (Exception $e) {
            jsonError($e->getMessage());
        }
    } else {
        // Fallback JSON
        require_once SNACK_ROOT . '/admin-panel-v2/config.php';

        $orders = loadData('orders.json') ?? [];
        $orderId = 'FB-' . date('Ymd') . '-' . str_pad((string)(count($orders) + 1), 3, '0', STR_PAD_LEFT);

        $newOrder = [
            'id' => $orderId,
            'customer_name' => $input['customer_name'] ?? 'Client',
            'customer_phone' => $input['customer_phone'],
            'items' => $input['items'],
            'subtotal' => $input['subtotal'] ?? $input['total'],
            'total' => (float)$input['total'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'notes' => $input['notes'] ?? ''
        ];

        $orders[] = $newOrder;
        saveData('orders.json', $orders);

        // Mettre à jour le client
        $customers = loadData('customers.json') ?? [];
        $phone = $input['customer_phone'];
        $found = false;

        foreach ($customers as &$c) {
            if ($c['phone'] === $phone) {
                $c['orders_count'] = ($c['orders_count'] ?? 0) + 1;
                $c['total_spent'] = ($c['total_spent'] ?? 0) + $input['total'];
                $c['last_order'] = date('Y-m-d H:i:s');
                $c['name'] = $input['customer_name'] ?? $c['name'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $customers[] = [
                'name' => $input['customer_name'] ?? 'Client',
                'phone' => $phone,
                'orders_count' => 1,
                'total_spent' => $input['total'],
                'last_order' => date('Y-m-d H:i:s')
            ];
        }

        saveData('customers.json', $customers);

        jsonSuccess([
            'order_id' => $orderId,
            'message' => 'Commande créée avec succès',
            'estimated_time' => '15-20 min'
        ]);
    }
}

/**
 * GET /api/public.php?endpoint=check-order&id=XXX
 * Vérifie le statut d'une commande
 */
function checkOrder(bool $useMySQL) {
    $orderId = $_GET['id'] ?? '';

    if (empty($orderId)) {
        jsonError('ID de commande requis');
    }

    if ($useMySQL) {
        $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);

        if ($order) {
            jsonSuccess([
                'order_id' => $order['order_number'],
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'total' => $order['total']
            ]);
        } else {
            jsonError('Commande introuvable', 404);
        }
    } else {
        require_once SNACK_ROOT . '/admin-panel-v2/config.php';

        $orders = loadData('orders.json') ?? [];
        $order = null;

        foreach ($orders as $o) {
            if ($o['id'] === $orderId) {
                $order = $o;
                break;
            }
        }

        if ($order) {
            jsonSuccess([
                'order_id' => $order['id'],
                'status' => $order['status'],
                'created_at' => $order['created_at'],
                'total' => $order['total']
            ]);
        } else {
            jsonError('Commande introuvable', 404);
        }
    }
}

/**
 * GET /api/public.php?endpoint=loyalty&phone=XXXXX
 * Récupère les infos fidélité d'un client (points et récompenses disponibles)
 */
function getLoyaltyInfo(bool $useMySQL) {
    $phone = $_GET['phone'] ?? '';

    if (empty($phone)) {
        jsonError('Numéro de téléphone requis');
    }

    if (!$useMySQL) {
        jsonError('Fidélité non disponible en mode JSON');
    }

    // Chercher le client
    $customer = CustomerRepository::getByPhone(SNACK_RESTAURANT_ID, $phone);

    if (!$customer) {
        // Client pas encore enregistré - retourner 0 points
        jsonSuccess([
            'found' => false,
            'points' => 0,
            'rewards' => []
        ]);
    }

    // Récupérer les récompenses disponibles
    $allRewards = LoyaltyRepository::getRewards(SNACK_RESTAURANT_ID);
    $customerPoints = (int) ($customer['loyalty_points'] ?? 0);

    // Filtrer les récompenses accessibles
    $availableRewards = array_filter($allRewards, function($r) use ($customerPoints) {
        return $customerPoints >= $r['points_required'];
    });

    jsonSuccess([
        'found' => true,
        'customer_name' => $customer['name'],
        'loyalty_code' => $customer['loyalty_code'] ?? null,
        'points' => $customerPoints,
        'rewards' => array_values($availableRewards),
        'all_rewards' => $allRewards
    ]);
}
