<?php
/**
 * SnackApp v1 - Customers API
 * Gestion des clients
 * Support MySQL avec fallback JSON
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
requireAdmin();

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
$requestData = json_decode(file_get_contents('php://input'), true);
if ($requestData) {
    $action = $requestData['action'] ?? $action;
}

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

switch ($action) {
    case 'list':
        listCustomers($useMySQL);
        break;
    case 'get':
        getCustomer($useMySQL);
        break;
    case 'add':
        addCustomer($useMySQL);
        break;
    case 'update':
        updateCustomer($useMySQL);
        break;
    case 'delete':
        deleteCustomer($useMySQL);
        break;
    case 'add_points':
        addLoyaltyPoints($useMySQL);
        break;
    case 'search':
        searchCustomers($useMySQL);
        break;
    case 'send_promo':
        sendPromotion($useMySQL);
        break;
    case 'get_by_loyalty_code':
        getByLoyaltyCode($useMySQL);
        break;
    default:
        jsonError('Action invalide');
}

/* =========================
   FONCTIONS
   ========================= */

function listCustomers(bool $useMySQL) {
    $orderBy = $_GET['order_by'] ?? 'orders_count DESC';

    if ($useMySQL) {
        $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID, $orderBy);
        $stats = CustomerRepository::getStats(SNACK_RESTAURANT_ID);

        jsonSuccess([
            'customers' => $customers,
            'stats' => $stats
        ]);
    } else {
        // Fallback JSON
        $customers = loadData('customers.json') ?? [];

        usort($customers, function($a, $b) {
            return ($b['orders_count'] ?? 0) - ($a['orders_count'] ?? 0);
        });

        jsonSuccess([
            'customers' => $customers,
            'stats' => [
                'total' => count($customers),
                'vip' => count(array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) >= 10)),
                'regular' => count(array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) >= 3 && ($c['orders_count'] ?? 0) < 10)),
                'new' => count(array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) < 3)),
                'total_revenue' => array_sum(array_column($customers, 'total_spent'))
            ]
        ]);
    }
}

function getCustomer(bool $useMySQL) {
    global $requestData;
    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;

    if (!$customerId) {
        jsonError('ID manquant');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);

        if ($customer) {
            // Récupérer l'historique des commandes
            $orderHistory = CustomerRepository::getOrderHistory((int)$customerId);
            $customer['order_history'] = $orderHistory;
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Client introuvable');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $customer = null;

        foreach ($customers as $c) {
            if (($c['id'] ?? '') === $customerId) {
                $customer = $c;
                break;
            }
        }

        if ($customer) {
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Client introuvable');
        }
    }
}

function addCustomer(bool $useMySQL) {
    global $requestData;

    $required = ['name', 'phone'];
    foreach ($required as $field) {
        if (!isset($requestData[$field]) || empty($requestData[$field])) {
            jsonError("Champ manquant: $field");
        }
    }

    if ($useMySQL) {
        try {
            $customerId = CustomerRepository::addCustomer(SNACK_RESTAURANT_ID, [
                'name' => $requestData['name'],
                'phone' => $requestData['phone'],
                'email' => $requestData['email'] ?? null
            ]);

            $customer = CustomerRepository::getById($customerId);
            jsonSuccess(['customer' => $customer]);
        } catch (Exception $e) {
            jsonError($e->getMessage());
        }
    } else {
        $customers = loadData('customers.json') ?? [];

        foreach ($customers as $c) {
            if (($c['phone'] ?? '') === $requestData['phone']) {
                jsonError('Ce numéro existe déjà');
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
            'last_order' => null,
            'notes' => $requestData['notes'] ?? ''
        ];

        $customers[] = $newCustomer;
        saveData('customers.json', $customers);

        jsonSuccess(['customer' => $newCustomer]);
    }
}

function updateCustomer(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    if (!$customerId) {
        jsonError('ID manquant');
    }

    if ($useMySQL) {
        $updateData = [];
        if (isset($requestData['name'])) $updateData['name'] = $requestData['name'];
        if (isset($requestData['phone'])) $updateData['phone'] = $requestData['phone'];
        if (isset($requestData['email'])) $updateData['email'] = $requestData['email'];

        if (empty($updateData)) {
            jsonError('Aucune donnée à mettre à jour');
        }

        $rowsAffected = Database::update('customers', $updateData, [
            'id' => (int)$customerId,
            'restaurant_id' => SNACK_RESTAURANT_ID
        ]);

        if ($rowsAffected > 0) {
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $updated = false;

        foreach ($customers as &$customer) {
            if (($customer['id'] ?? '') === $customerId) {
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
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    }
}

function deleteCustomer(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;
    if (!$customerId) {
        jsonError('ID manquant');
    }

    if ($useMySQL) {
        $deleted = CustomerRepository::deleteCustomer((int)$customerId, SNACK_RESTAURANT_ID);

        if ($deleted) {
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $filtered = array_filter($customers, fn($c) => ($c['id'] ?? '') !== $customerId);

        if (count($filtered) < count($customers)) {
            saveData('customers.json', array_values($filtered));
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    }
}

function addLoyaltyPoints(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $points = $requestData['points'] ?? null;
    $reason = $requestData['reason'] ?? 'Bonus manuel';

    if (!$customerId || $points === null) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        // Mettre à jour les points du client
        $rowsAffected = Database::query(
            "UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ? AND restaurant_id = ?",
            [(int)$points, (int)$customerId, SNACK_RESTAURANT_ID]
        )->rowCount();

        if ($rowsAffected > 0) {
            // Enregistrer la transaction
            Database::insert('loyalty_transactions', [
                'customer_id' => (int)$customerId,
                'restaurant_id' => SNACK_RESTAURANT_ID,
                'points' => (int)$points,
                'type' => $points > 0 ? 'bonus' : 'adjustment',
                'description' => $reason
            ]);

            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $updated = false;

        foreach ($customers as &$customer) {
            if (($customer['id'] ?? '') === $customerId) {
                $customer['loyalty_points'] = ($customer['loyalty_points'] ?? 0) + intval($points);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            saveData('customers.json', $customers);
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    }
}

function searchCustomers(bool $useMySQL) {
    $query = $_GET['q'] ?? '';

    if (strlen($query) < 2) {
        jsonError('Recherche trop courte (min 2 caractères)');
    }

    if ($useMySQL) {
        $customers = CustomerRepository::search(SNACK_RESTAURANT_ID, $query);
        jsonSuccess(['customers' => $customers]);
    } else {
        $customers = loadData('customers.json') ?? [];
        $query = strtolower($query);

        $filtered = array_filter($customers, function($c) use ($query) {
            return strpos(strtolower($c['name'] ?? ''), $query) !== false ||
                   strpos($c['phone'] ?? '', $query) !== false ||
                   strpos(strtolower($c['loyalty_code'] ?? ''), $query) !== false;
        });

        jsonSuccess(['customers' => array_values($filtered)]);
    }
}

function getByLoyaltyCode(bool $useMySQL) {
    $code = $_GET['code'] ?? '';

    if (empty($code)) {
        jsonError('Code manquant');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getByLoyaltyCode($code);

        if ($customer && $customer['restaurant_id'] === SNACK_RESTAURANT_ID) {
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Code fidélité invalide');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $customer = null;

        foreach ($customers as $c) {
            if (($c['loyalty_code'] ?? '') === $code) {
                $customer = $c;
                break;
            }
        }

        if ($customer) {
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Code fidélité invalide');
        }
    }
}

function sendPromotion(bool $useMySQL) {
    global $requestData;

    $customerIds = $requestData['customer_ids'] ?? [];
    $message = $requestData['message'] ?? '';

    if (empty($customerIds) || empty($message)) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        $recipients = [];
        foreach ($customerIds as $id) {
            $customer = CustomerRepository::getById((int)$id);
            if ($customer && $customer['restaurant_id'] === SNACK_RESTAURANT_ID) {
                $recipients[] = [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'url' => sendWhatsAppMessage($customer['phone'], $message)
                ];
            }
        }

        jsonSuccess(['recipients' => $recipients]);
    } else {
        $customers = loadData('customers.json') ?? [];
        $recipients = [];

        foreach ($customers as $customer) {
            if (in_array($customer['id'] ?? '', $customerIds)) {
                $recipients[] = [
                    'name' => $customer['name'],
                    'phone' => $customer['phone'],
                    'url' => sendWhatsAppMessage($customer['phone'], $message)
                ];
            }
        }

        jsonSuccess(['recipients' => $recipients]);
    }
}
