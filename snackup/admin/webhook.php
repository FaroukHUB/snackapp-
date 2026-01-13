<?php
/**
 * Webhook pour recevoir les commandes depuis le site web
 * Ce fichier doit être appelé depuis script.js quand le client envoie une commande
 *
 * Utilise bootstrap.php pour la cohérence avec le reste de l'admin
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json');

// 🔒 SÉCURITÉ: Autoriser uniquement les domaines de confiance
$allowed_origins = [
    'https://marvelous.mon-agenceweb.fr',
    'https://www.marvelous.mon-agenceweb.fr'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    // Bloquer les requêtes d'origines non autorisées
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Origine non autorisée']);
    exit;
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

// Valider les données requises
$required = ['customer_phone', 'items', 'total'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'error' => "Champ manquant: $field"]);
        exit;
    }
}

// Déterminer si on utilise MySQL ou JSON
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

if ($useMySQL) {
    // === MODE MYSQL ===
    try {
        $orderId = OrderRepository::createOrder(SNACK_RESTAURANT_ID, [
            'customer_name' => $data['customer_name'] ?? 'Client',
            'customer_phone' => preg_replace('/[^0-9+]/', '', $data['customer_phone']),
            'items' => $data['items'],
            'subtotal' => floatval($data['subtotal'] ?? $data['total']),
            'total' => floatval($data['total']),
            'notes' => $data['notes'] ?? null,
            'loyalty_reward_id' => $data['loyalty_reward_id'] ?? null
        ]);

        $order = OrderRepository::getById($orderId);

        echo json_encode([
            'success' => true,
            'order_id' => $order['order_number'],
            'message' => 'Commande reçue avec succès'
        ]);
    } catch (Exception $e) {
        error_log("Webhook MySQL Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    // === MODE JSON (fallback) ===
    require_once __DIR__ . '/config.php';

    $orders = loadData('orders.json');

    // Générer un ID unique pour la commande
    $today = date('Ymd');
    $orderCount = 0;

    // Compter les commandes du jour
    foreach ($orders as $order) {
        if (strpos($order['id'], 'ORD' . $today) === 0) {
            $orderCount++;
        }
    }

    $orderId = 'ORD' . $today . str_pad($orderCount + 1, 4, '0', STR_PAD_LEFT);

    // Créer la nouvelle commande
    $newOrder = [
        'id' => $orderId,
        'customer_name' => $data['customer_name'] ?? '',
        'customer_phone' => preg_replace('/[^0-9+]/', '', $data['customer_phone']),
        'items' => $data['items'],
        'subtotal' => floatval($data['subtotal'] ?? $data['total']),
        'total' => floatval($data['total']),
        'loyalty_reward_id' => $data['loyalty_reward_id'] ?? null,
        'loyalty_discount' => floatval($data['loyalty_discount'] ?? 0),
        'status' => 'received',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'notes' => $data['notes'] ?? '',
        'estimated_time' => null,
        'estimated_ready_at' => null,
        'ready_at' => null
    ];

    // Ajouter la commande
    $orders[] = $newOrder;

    // Sauvegarder
    if (saveData('orders.json', $orders)) {
        // Enregistrer le client si nouveau
        saveCustomerFromOrder($newOrder);

        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'message' => 'Commande reçue avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de l\'enregistrement'
        ]);
    }
}

/**
 * Enregistrer le client dans la base CRM (mode JSON uniquement)
 */
function saveCustomerFromOrder($order) {
    $customers = loadData('customers.json');

    // Vérifier si le client existe déjà
    $customerExists = false;
    foreach ($customers as &$customer) {
        if ($customer['phone'] === $order['customer_phone']) {
            // Mettre à jour les infos
            $customer['orders_count'] = ($customer['orders_count'] ?? 0) + 1;
            $customer['total_spent'] = ($customer['total_spent'] ?? 0) + $order['total'];
            $customer['last_order'] = date('Y-m-d H:i:s');

            // Ajouter des points de fidélité (1€ = 1 point)
            $customer['loyalty_points'] = ($customer['loyalty_points'] ?? 0) + floor($order['total']);

            $customerExists = true;
            break;
        }
    }

    // Si nouveau client, l'ajouter
    if (!$customerExists) {
        $customers[] = [
            'id' => 'CUST' . date('YmdHis') . rand(1000, 9999),
            'name' => $order['customer_name'] ?: 'Client',
            'phone' => $order['customer_phone'],
            'email' => '',
            'registered_at' => date('Y-m-d H:i:s'),
            'orders_count' => 1,
            'total_spent' => $order['total'],
            'loyalty_points' => floor($order['total']),
            'last_order' => date('Y-m-d H:i:s'),
            'notes' => ''
        ];
    }

    saveData('customers.json', $customers);
}
