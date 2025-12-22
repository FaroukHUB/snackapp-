<?php
/**
 * SnackApp v1 - Orders API
 * Gestion des commandes
 * Support MySQL avec fallback JSON
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

// Vérifier authentification (sauf pour création de commande depuis le site)
$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$requestData = json_decode(file_get_contents('php://input'), true);
if ($requestData) {
    $action = $requestData['action'] ?? $action;
}

// L'action 'add' peut être appelée sans auth (depuis le site public)
if ($action !== 'add' && $action !== 'check_new') {
    requireAdmin();
}

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

/* =========================
   ROUTER
   ========================= */

switch ($action) {
    case 'list':
        listOrders($useMySQL);
        break;

    case 'get':
        getOrder($useMySQL);
        break;

    case 'update_status':
        updateOrderStatus($useMySQL);
        break;

    case 'archive':
        archiveOrder($useMySQL);
        break;

    case 'archive_all':
        archiveAllOrders($useMySQL);
        break;

    case 'add':
        addOrder($useMySQL);
        break;

    case 'check_new':
        checkNewOrders($useMySQL);
        break;

    case 'stats':
        getStats($useMySQL);
        break;

    default:
        jsonError('Action invalide');
}

/* =========================
   FONCTIONS
   ========================= */

function listOrders(bool $useMySQL) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $archived = isset($_GET['archived']) && $_GET['archived'] === '1';

    if ($useMySQL) {
        if ($archived) {
            $orders = OrderRepository::getArchivedOrders(SNACK_RESTAURANT_ID, $limit);
        } else {
            $orders = OrderRepository::getActiveOrders(SNACK_RESTAURANT_ID, $limit);
        }

        // Formater pour compatibilité
        foreach ($orders as &$order) {
            $order['id'] = $order['order_number'];
        }
    } else {
        require_once __DIR__ . '/../config.php';

        if ($archived) {
            $orders = loadData('orders-archive.json') ?? [];
        } else {
            $orders = loadData('orders.json') ?? [];
        }

        usort($orders, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        if ($limit > 0) {
            $orders = array_slice($orders, 0, $limit);
        }
    }

    jsonSuccess(['orders' => $orders]);
}

function getOrder(bool $useMySQL) {
    global $requestData;
    $orderId = $requestData['order_id'] ?? $_GET['order_id'] ?? null;

    if (!$orderId) {
        jsonError('ID manquant');
    }

    if ($useMySQL) {
        // Si c'est un numéro de commande (string), chercher par numéro
        if (!is_numeric($orderId)) {
            $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);
        } else {
            $order = OrderRepository::getById((int)$orderId);
        }

        if ($order) {
            $order['id'] = $order['order_number'];
        }
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];
        $order = null;

        foreach ($orders as $o) {
            if ($o['id'] === $orderId) {
                $order = $o;
                break;
            }
        }
    }

    if ($order) {
        jsonSuccess(['order' => $order]);
    } else {
        jsonError('Commande introuvable');
    }
}

function updateOrderStatus(bool $useMySQL) {
    global $requestData;

    $orderId = $requestData['order_id'] ?? null;
    $newStatus = $requestData['status'] ?? null;

    if (!$orderId || !$newStatus) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        // Trouver l'ID interne si on a un numéro de commande
        if (!is_numeric($orderId)) {
            $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);
            if (!$order) {
                jsonError('Commande introuvable');
            }
            $orderId = $order['id'];
        }

        OrderRepository::updateStatus((int)$orderId, $newStatus);
        jsonSuccess();
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];
        $updated = false;

        foreach ($orders as &$order) {
            if ($order['id'] === $orderId) {
                $order['status'] = $newStatus;
                $order['updated_at'] = date('Y-m-d H:i:s');

                if ($newStatus === 'ready') {
                    $order['ready_at'] = date('Y-m-d H:i:s');
                }
                if ($newStatus === 'completed') {
                    $order['completed_at'] = date('Y-m-d H:i:s');
                }

                $updated = true;
                break;
            }
        }

        if ($updated) {
            saveData('orders.json', $orders);
            jsonSuccess();
        } else {
            jsonError('Commande introuvable');
        }
    }
}

function archiveOrder(bool $useMySQL) {
    global $requestData;
    $orderId = $requestData['order_id'] ?? null;

    if (!$orderId) {
        jsonError('ID manquant');
    }

    if ($useMySQL) {
        if (!is_numeric($orderId)) {
            $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);
            if (!$order) {
                jsonError('Commande introuvable');
            }
            $orderId = $order['id'];
        }

        OrderRepository::archiveOrder((int)$orderId);
        jsonSuccess();
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];
        $archive = loadData('orders-archive.json') ?? [];

        $found = false;
        foreach ($orders as $i => $order) {
            if ($order['id'] === $orderId) {
                $archive[] = $order;
                unset($orders[$i]);
                $found = true;
                break;
            }
        }

        if ($found) {
            saveData('orders.json', array_values($orders));
            saveData('orders-archive.json', $archive);
            jsonSuccess();
        } else {
            jsonError('Commande introuvable');
        }
    }
}

function archiveAllOrders(bool $useMySQL) {
    if ($useMySQL) {
        $count = OrderRepository::archiveAllCompleted(SNACK_RESTAURANT_ID);
        jsonSuccess(['archived' => $count]);
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];
        $archive = loadData('orders-archive.json') ?? [];

        $archive = array_merge($archive, $orders);
        saveData('orders.json', []);
        saveData('orders-archive.json', $archive);

        jsonSuccess(['archived' => count($orders)]);
    }
}

function addOrder(bool $useMySQL) {
    global $requestData;

    $required = ['customer_phone', 'items', 'total'];
    foreach ($required as $field) {
        if (!isset($requestData[$field])) {
            jsonError("Champ manquant: $field");
        }
    }

    if ($useMySQL) {
        try {
            $orderId = OrderRepository::createOrder(SNACK_RESTAURANT_ID, [
                'customer_name' => $requestData['customer_name'] ?? 'Client',
                'customer_phone' => $requestData['customer_phone'],
                'items' => $requestData['items'],
                'subtotal' => $requestData['subtotal'] ?? $requestData['total'],
                'total' => (float)$requestData['total'],
                'notes' => $requestData['notes'] ?? null,
                'pickup_time' => $requestData['pickup_time'] ?? null
            ]);

            $order = OrderRepository::getById($orderId);

            // Get loyalty_code from MySQL customer (created by OrderRepository)
            $loyaltyCode = null;
            $isNewCustomer = false;

            if (!empty($order['customer_id'])) {
                $customer = CustomerRepository::getById($order['customer_id']);
                if ($customer) {
                    $loyaltyCode = $customer['loyalty_code']; // SNACK-XXXX format from MySQL
                    // New customer = first order (orders_count is 1 after this order)
                    $isNewCustomer = ($customer['orders_count'] ?? 0) <= 1;
                }
            }

            jsonSuccess([
                'order_id' => $order['order_number'],
                'order' => $order,
                'loyalty_code' => $loyaltyCode,
                'is_new_customer' => $isNewCustomer
            ]);
        } catch (Exception $e) {
            jsonError($e->getMessage());
        }
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];

        $orderId = 'FB-' . date('Ymd') . '-' . str_pad((string)(count($orders) + 1), 3, '0', STR_PAD_LEFT);

        $newOrder = [
            'id' => $orderId,
            'customer_name' => $requestData['customer_name'] ?? 'Client',
            'customer_phone' => $requestData['customer_phone'],
            'items' => $requestData['items'],
            'subtotal' => $requestData['subtotal'] ?? $requestData['total'],
            'total' => (float)$requestData['total'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'notes' => $requestData['notes'] ?? ''
        ];

        $orders[] = $newOrder;
        saveData('orders.json', $orders);

        // Mettre à jour le client
        $customers = loadData('customers.json') ?? [];
        $phone = $requestData['customer_phone'];
        $found = false;
        $loyaltyCode = null;
        $isNewCustomer = false;

        // Helper function to get max loyalty code number
        $getMaxLoyaltyNum = function($customers) {
            $maxId = 0;
            foreach ($customers as $cust) {
                if (isset($cust['loyalty_code']) && preg_match('/MAR-(\d+)/', $cust['loyalty_code'], $m)) {
                    $maxId = max($maxId, (int)$m[1]);
                }
            }
            return $maxId;
        };

        // Helper function to compare phone numbers (flexible matching)
        $phonesMatch = function($phone1, $phone2) {
            $clean1 = preg_replace('/[^0-9]/', '', $phone1);
            $clean2 = preg_replace('/[^0-9]/', '', $phone2);
            // Match if last 8 digits are the same
            return strlen($clean1) >= 8 && strlen($clean2) >= 8 &&
                   substr($clean1, -8) === substr($clean2, -8);
        };

        foreach ($customers as &$c) {
            if ($phonesMatch($c['phone'] ?? '', $phone)) {
                $c['orders_count'] = ($c['orders_count'] ?? 0) + 1;
                $c['total_spent'] = ($c['total_spent'] ?? 0) + $requestData['total'];
                $c['loyalty_points'] = ($c['loyalty_points'] ?? 0) + (int)$requestData['total'];
                $c['last_order'] = date('Y-m-d H:i:s');
                $c['name'] = $requestData['customer_name'] ?? $c['name'];

                // Generate loyalty_code if not exists
                if (empty($c['loyalty_code'])) {
                    $maxId = $getMaxLoyaltyNum($customers);
                    $c['loyalty_code'] = 'MAR-' . str_pad((string)($maxId + 1), 4, '0', STR_PAD_LEFT);
                    $isNewCustomer = true; // Show code to existing customers who just got one
                }
                $loyaltyCode = $c['loyalty_code'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            // Generate simple loyalty code: MAR-XXXX
            $maxId = $getMaxLoyaltyNum($customers);
            $loyaltyCode = 'MAR-' . str_pad((string)($maxId + 1), 4, '0', STR_PAD_LEFT);
            $isNewCustomer = true;

            $customers[] = [
                'loyalty_code' => $loyaltyCode,
                'name' => $requestData['customer_name'] ?? 'Client',
                'phone' => $phone,
                'orders_count' => 1,
                'total_spent' => $requestData['total'],
                'loyalty_points' => (int)$requestData['total'],
                'last_order' => date('Y-m-d H:i:s'),
                'registered_at' => date('Y-m-d H:i:s')
            ];
        }

        saveData('customers.json', $customers);

        jsonSuccess([
            'order_id' => $orderId,
            'order' => $newOrder,
            'loyalty_code' => $loyaltyCode,
            'is_new_customer' => $isNewCustomer
        ]);
    }
}

function checkNewOrders(bool $useMySQL) {
    $since = $_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));

    if ($useMySQL) {
        $hasNew = OrderRepository::hasNewOrders(SNACK_RESTAURANT_ID, $since);
        $newOrders = $hasNew ? OrderRepository::getNewOrders(SNACK_RESTAURANT_ID, $since) : [];

        foreach ($newOrders as &$order) {
            $order['id'] = $order['order_number'];
        }

        jsonSuccess([
            'has_new' => $hasNew,
            'orders' => $newOrders,
            'checked_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];

        $newOrders = array_filter($orders, fn($o) =>
            isset($o['created_at']) && strtotime($o['created_at']) > strtotime($since)
        );

        jsonSuccess([
            'has_new' => count($newOrders) > 0,
            'orders' => array_values($newOrders),
            'checked_at' => date('Y-m-d H:i:s')
        ]);
    }
}

function getStats(bool $useMySQL) {
    if ($useMySQL) {
        $stats = OrderRepository::getTodayStats(SNACK_RESTAURANT_ID);
        jsonSuccess(['stats' => $stats]);
    } else {
        require_once __DIR__ . '/../config.php';
        $orders = loadData('orders.json') ?? [];

        $today = date('Y-m-d');
        $todayOrders = array_filter($orders, fn($o) =>
            isset($o['created_at']) && strpos($o['created_at'], $today) === 0
        );

        $stats = [
            'today' => count($todayOrders),
            'pending' => count(array_filter($todayOrders, fn($o) => ($o['status'] ?? '') !== 'completed')),
            'completed' => count(array_filter($todayOrders, fn($o) => ($o['status'] ?? '') === 'completed')),
            'revenue' => array_sum(array_column($todayOrders, 'total')),
            'avg_order' => count($todayOrders) > 0 ? array_sum(array_column($todayOrders, 'total')) / count($todayOrders) : 0
        ];

        jsonSuccess(['stats' => $stats]);
    }
}
