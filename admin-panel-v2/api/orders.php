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

    // 🔒 SÉCURITÉ: Rate limiting amélioré avec fingerprint (IP + User Agent)
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $fingerprint = hash('sha256', $clientIP . '|' . $userAgent);
    $rateLimitFile = __DIR__ . '/../cache/rate_limit_' . $fingerprint . '.json';

    // Créer le dossier cache si nécessaire
    $cacheDir = __DIR__ . '/../cache';
    if (!file_exists($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }

    // Nettoyer les anciens fichiers cache (> 24h)
    foreach (glob($cacheDir . '/rate_limit_*.json') as $file) {
        if (filemtime($file) < time() - 86400) {
            @unlink($file);
        }
    }

    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        $timeDiff = time() - ($data['timestamp'] ?? 0);

        // Max 5 commandes par minute
        if ($timeDiff < 60 && ($data['count'] ?? 0) >= 5) {
            error_log('[RATE_LIMIT] Bloqué - IP: ' . $clientIP);
            jsonError('Trop de commandes. Veuillez patienter 1 minute.', 429);
        }

        if ($timeDiff < 60) {
            $data['count'] = ($data['count'] ?? 0) + 1;
        } else {
            $data = ['timestamp' => time(), 'count' => 1];
        }
    } else {
        $data = ['timestamp' => time(), 'count' => 1];
    }

    file_put_contents($rateLimitFile, json_encode($data));

    $required = ['customer_phone', 'items', 'total'];
    foreach ($required as $field) {
        if (!isset($requestData[$field])) {
            jsonError("Champ manquant: $field");
        }
    }

    if ($useMySQL) {
        try {
            $loyaltyRewardId = $requestData['loyalty_reward_id'] ?? null;
            $loyaltyCustomerId = $requestData['loyalty_customer_id'] ?? null;

            // Créer la commande
            $orderId = OrderRepository::createOrder(SNACK_RESTAURANT_ID, [
                'customer_name' => $requestData['customer_name'] ?? 'Client',
                'customer_phone' => $requestData['customer_phone'],
                'items' => $requestData['items'],
                'subtotal' => $requestData['subtotal'] ?? $requestData['total'],
                'total' => (float)$requestData['total'],
                'loyalty_reward_id' => $loyaltyRewardId,
                'notes' => $requestData['notes'] ?? null,
                'pickup_time' => $requestData['pickup_time'] ?? null
            ]);

            // ✅ Ajouter les points gagnés (100 DA = 1 point)
            $phone = $requestData['customer_phone'];
            $customer = CustomerRepository::getByPhone(SNACK_RESTAURANT_ID, $phone);
            $pointsEarned = 0;
            $pointsDeducted = 0;

            // 🔒 SÉCURITÉ: Vérifier que le numéro correspond au compte fidélité
            $loyaltyCustomerId = $requestData['loyalty_customer_id'] ?? null;
            if ($loyaltyRewardId && $loyaltyCustomerId) {
                // Si une récompense est sélectionnée, vérifier que c'est bien le même client
                if (!$customer || $customer['id'] != $loyaltyCustomerId) {
                    jsonError('Le numéro de téléphone ne correspond pas au compte fidélité utilisé pour la récompense');
                }
            }

            if ($customer) {
                $amountPaid = (float)$requestData['total'];
                if ($amountPaid > 0) {
                    $pointsEarned = floor($amountPaid / 100); // 100 DA = 1 point
                    CustomerRepository::addPoints($customer['id'], SNACK_RESTAURANT_ID, $pointsEarned, $orderId);
                }

                // ✅ Débiter les points si une récompense a été sélectionnée
                if ($loyaltyRewardId) {
                    $reward = Database::fetchOne(
                        "SELECT name, points_required FROM loyalty_rewards WHERE id = ? AND restaurant_id = ?",
                        [$loyaltyRewardId, SNACK_RESTAURANT_ID]
                    );

                    if ($reward) {
                        $pointsDeducted = (int)$reward['points_required'];
                        LoyaltyRepository::redeemPoints($customer['id'], SNACK_RESTAURANT_ID, $pointsDeducted, $reward['name']);
                    }
                }
            }

            $order = OrderRepository::getById($orderId);
            jsonSuccess([
                'order_id' => $order['order_number'],
                'order' => $order,
                'loyalty_points' => $pointsEarned, // Pour compatibilité frontend
                'loyalty_code' => $customer['loyalty_code'] ?? null, // Code carte fidélité
                'loyalty_points_earned' => $pointsEarned,
                'loyalty_points_deducted' => $pointsDeducted,
                'loyalty_points_net' => $pointsEarned - $pointsDeducted
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
            'loyalty_reward_id' => $requestData['loyalty_reward_id'] ?? null,
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
        $pointsEarned = 0;

        foreach ($customers as &$c) {
            // Mettre à jour le compte qui passe commande
            if ($c['phone'] === $phone) {
                $c['orders_count'] = ($c['orders_count'] ?? 0) + 1;
                $c['total_spent'] = ($c['total_spent'] ?? 0) + $requestData['total'];
                $c['last_order'] = date('Y-m-d H:i:s');
                $c['name'] = $requestData['customer_name'] ?? $c['name'];

                // ✅ Ajouter points gagnés (100 DA = 1 point)
                if ($requestData['total'] > 0) {
                    $pointsEarned = floor($requestData['total'] / 100);
                    $c['loyalty_points'] = ($c['loyalty_points'] ?? 0) + $pointsEarned;
                }

                $found = true;
            }
        }

        if (!$found) {
            $customers[] = [
                'name' => $requestData['customer_name'] ?? 'Client',
                'phone' => $phone,
                'orders_count' => 1,
                'total_spent' => $requestData['total'],
                'last_order' => date('Y-m-d H:i:s')
            ];
        }

        saveData('customers.json', $customers);

        jsonSuccess([
            'order_id' => $orderId,
            'order' => $newOrder,
            'loyalty_points' => $pointsEarned
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
