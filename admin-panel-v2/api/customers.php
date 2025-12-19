<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$requestData = json_decode(file_get_contents('php://input'), true);
if ($requestData) {
    $action = $requestData['action'] ?? $action;
}

switch ($action) {
    case 'list':
        listCustomers();
        break;
    case 'get':
        getCustomer();
        break;
    case 'add':
        addCustomer();
        break;
    case 'update':
        updateCustomer();
        break;
    case 'delete':
        deleteCustomer();
        break;
    case 'add_points':
        addLoyaltyPoints();
        break;
    case 'send_promo':
        sendPromotion();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}

function listCustomers() {
    $customers = loadData('customers.json');

    // Trier par dernière commande (plus récents en premier)
    usort($customers, function($a, $b) {
        return strtotime($b['last_order']) - strtotime($a['last_order']);
    });

    echo json_encode([
        'success' => true,
        'customers' => $customers,
        'stats' => [
            'total' => count($customers),
            'vip' => count(array_filter($customers, fn($c) => $c['orders_count'] >= 10)),
            'total_points' => array_sum(array_column($customers, 'loyalty_points'))
        ]
    ]);
}

function getCustomer() {
    global $requestData;
    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;

    if (!$customerId) {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
        return;
    }

    $customers = loadData('customers.json');
    $customer = array_values(array_filter($customers, fn($c) => $c['id'] === $customerId))[0] ?? null;

    if ($customer) {
        echo json_encode(['success' => true, 'customer' => $customer]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Client introuvable']);
    }
}

function addCustomer() {
    global $requestData;

    $required = ['name', 'phone'];
    foreach ($required as $field) {
        if (!isset($requestData[$field]) || empty($requestData[$field])) {
            echo json_encode(['success' => false, 'error' => "Champ manquant: $field"]);
            return;
        }
    }

    $customers = loadData('customers.json');

    // Vérifier si le téléphone existe déjà
    foreach ($customers as $c) {
        if ($c['phone'] === $requestData['phone']) {
            echo json_encode(['success' => false, 'error' => 'Ce numéro existe déjà']);
            return;
        }
    }

    $newCustomer = [
        'id' => 'CUST' . date('YmdHis') . rand(1000, 9999),
        'name' => $requestData['name'],
        'phone' => $requestData['phone'],
        'email' => $requestData['email'] ?? '',
        'registered_at' => date('Y-m-d H:i:s'),
        'orders_count' => 0,
        'total_spent' => 0,
        'loyalty_points' => 0,
        'last_order' => date('Y-m-d H:i:s'),
        'notes' => $requestData['notes'] ?? ''
    ];

    $customers[] = $newCustomer;
    saveData('customers.json', $customers);

    echo json_encode(['success' => true, 'customer' => $newCustomer]);
}

function updateCustomer() {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    if (!$customerId) {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
        return;
    }

    $customers = loadData('customers.json');
    $updated = false;

    foreach ($customers as &$customer) {
        if ($customer['id'] === $customerId) {
            if (isset($requestData['name'])) $customer['name'] = $requestData['name'];
            if (isset($requestData['phone'])) $customer['phone'] = $requestData['phone'];
            if (isset($requestData['email'])) $customer['email'] = $requestData['email'];
            if (isset($requestData['notes'])) $customer['notes'] = $requestData['notes'];
            $updated = true;
            break;
        }
    }

    if ($updated) {
        saveData('customers.json', $customers);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Client introuvable']);
    }
}

function deleteCustomer() {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;
    if (!$customerId) {
        echo json_encode(['success' => false, 'error' => 'ID manquant']);
        return;
    }

    $customers = loadData('customers.json');
    $filtered = array_filter($customers, fn($c) => $c['id'] !== $customerId);

    if (count($filtered) < count($customers)) {
        saveData('customers.json', array_values($filtered));
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Client introuvable']);
    }
}

function addLoyaltyPoints() {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $points = $requestData['points'] ?? null;

    if (!$customerId || $points === null) {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        return;
    }

    $customers = loadData('customers.json');
    $updated = false;

    foreach ($customers as &$customer) {
        if ($customer['id'] === $customerId) {
            $customer['loyalty_points'] = ($customer['loyalty_points'] ?? 0) + intval($points);
            $updated = true;
            break;
        }
    }

    if ($updated) {
        saveData('customers.json', $customers);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Client introuvable']);
    }
}

function sendPromotion() {
    global $requestData;

    $customerIds = $requestData['customer_ids'] ?? [];
    $message = $requestData['message'] ?? '';

    if (empty($customerIds) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        return;
    }

    $customers = loadData('customers.json');
    $recipients = [];

    foreach ($customers as $customer) {
        if (in_array($customer['id'], $customerIds)) {
            $recipients[] = [
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'url' => sendWhatsAppMessage($customer['phone'], $message)
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'recipients' => $recipients
    ]);
}
?>
