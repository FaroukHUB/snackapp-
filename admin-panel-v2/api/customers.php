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
    case 'get_by_phone':
        getByPhone($useMySQL);
        break;
    // ===== ADRESSES =====
    case 'add_address':
        addAddress($useMySQL);
        break;
    case 'update_address':
        updateAddress($useMySQL);
        break;
    case 'delete_address':
        deleteAddress($useMySQL);
        break;
    case 'set_default_address':
        setDefaultAddress($useMySQL);
        break;
    // ===== PRÉFÉRENCES =====
    case 'update_preferences':
        updatePreferences($useMySQL);
        break;
    case 'update_admin_notes':
        updateAdminNotes($useMySQL);
        break;
    // ===== TAGS =====
    case 'add_tag':
        addTag($useMySQL);
        break;
    case 'remove_tag':
        removeTag($useMySQL);
        break;
    case 'get_available_tags':
        getAvailableTags();
        break;
    // ===== HISTORIQUE & STATS =====
    case 'get_detailed_history':
        getDetailedHistory($useMySQL);
        break;
    case 'get_favorite_products':
        getFavoriteProducts($useMySQL);
        break;
    default:
        jsonError('Action invalide');
}

/* =========================
   FONCTIONS
   ========================= */

function listCustomers(bool $useMySQL) {
    $orderBy = $_GET['order_by'] ?? 'orders_count DESC';
    $filterTag = $_GET['filter_tag'] ?? null;

    if ($useMySQL) {
        $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID, $orderBy);

        // Enrichir avec tags
        foreach ($customers as &$customer) {
            // Récupérer les tags du client
            $tags = Database::fetchAll(
                "SELECT tag FROM customer_tags WHERE customer_id = ?",
                [$customer['id']]
            );
            $customer['tags'] = array_column($tags, 'tag');

            // Parser JSON addresses et preferences
            $customer['addresses'] = json_decode($customer['addresses'] ?? '[]', true) ?: [];
            $customer['preferences'] = json_decode($customer['preferences'] ?? '{}', true) ?: [];
        }

        // Filtrer par tag si demandé
        if ($filterTag) {
            $customers = array_filter($customers, fn($c) => in_array($filterTag, $c['tags'] ?? []));
            $customers = array_values($customers);
        }

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

            // Récupérer les tags
            $tags = Database::fetchAll(
                "SELECT tag FROM customer_tags WHERE customer_id = ?",
                [$customer['id']]
            );
            $customer['tags'] = array_column($tags, 'tag');

            // Parser JSON
            $customer['addresses'] = json_decode($customer['addresses'] ?? '[]', true) ?: [];
            $customer['preferences'] = json_decode($customer['preferences'] ?? '{}', true) ?: [];

            // Récupérer produits favoris
            $favorites = Database::fetchAll(
                "SELECT
                    oi.product_name as name,
                    COUNT(*) as count,
                    SUM(oi.quantity) as total_quantity
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.customer_id = ?
                 GROUP BY oi.product_name
                 ORDER BY count DESC
                 LIMIT 3",
                [$customer['id']]
            );
            $customer['favorite_products'] = $favorites;

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

function getByPhone(bool $useMySQL) {
    $phone = $_GET['phone'] ?? '';

    if (empty($phone)) {
        jsonError('Numéro de téléphone manquant');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getByPhone($phone);

        if ($customer && $customer['restaurant_id'] === SNACK_RESTAURANT_ID) {
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Client non trouvé');
        }
    } else {
        $customers = loadData('customers.json') ?? [];
        $customer = null;

        foreach ($customers as $c) {
            if (($c['phone'] ?? '') === $phone) {
                $customer = $c;
                break;
            }
        }

        if ($customer) {
            jsonSuccess(['customer' => $customer]);
        } else {
            jsonError('Client non trouvé');
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

// ============================================
// NOUVELLES FONCTIONS - AMÉLIORATION CLIENTS
// ============================================

/* =========================
   GESTION ADRESSES
   ========================= */

function addAddress(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $type = $requestData['type'] ?? 'home'; // home ou work
    $label = $requestData['label'] ?? '';
    $address = trim($requestData['address'] ?? '');
    $notes = trim($requestData['notes'] ?? '');

    if (!$customerId || empty($address)) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);
        if (!$customer || $customer['restaurant_id'] !== SNACK_RESTAURANT_ID) {
            jsonError('Client introuvable');
        }

        $addresses = json_decode($customer['addresses'] ?? '[]', true) ?: [];

        // Limite: 2 adresses maximum
        if (count($addresses) >= 2) {
            jsonError('Maximum 2 adresses autorisées');
        }

        // Nouvelle adresse
        $newAddress = [
            'id' => uniqid('addr_'),
            'type' => $type,
            'label' => $label ?: ($type === 'home' ? 'Maison' : 'Bureau'),
            'address' => $address,
            'notes' => $notes,
            'is_default' => count($addresses) === 0 // Première adresse = défaut
        ];

        $addresses[] = $newAddress;

        Database::update('customers', [
            'addresses' => json_encode($addresses, JSON_UNESCAPED_UNICODE)
        ], [
            'id' => (int)$customerId,
            'restaurant_id' => SNACK_RESTAURANT_ID
        ]);

        jsonSuccess(['address' => $newAddress]);
    } else {
        jsonError('Mode JSON non supporté pour les adresses');
    }
}

function updateAddress(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $addressId = $requestData['address_id'] ?? null;
    $address = trim($requestData['address'] ?? '');
    $notes = trim($requestData['notes'] ?? '');

    if (!$customerId || !$addressId) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);
        if (!$customer) {
            jsonError('Client introuvable');
        }

        $addresses = json_decode($customer['addresses'] ?? '[]', true) ?: [];
        $found = false;

        foreach ($addresses as &$addr) {
            if ($addr['id'] === $addressId) {
                if (!empty($address)) $addr['address'] = $address;
                if (isset($requestData['notes'])) $addr['notes'] = $notes;
                if (isset($requestData['label'])) $addr['label'] = $requestData['label'];
                $found = true;
                break;
            }
        }

        if (!$found) {
            jsonError('Adresse introuvable');
        }

        Database::update('customers', [
            'addresses' => json_encode($addresses, JSON_UNESCAPED_UNICODE)
        ], [
            'id' => (int)$customerId
        ]);

        jsonSuccess();
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function deleteAddress(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $addressId = $requestData['address_id'] ?? null;

    if (!$customerId || !$addressId) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);
        if (!$customer) {
            jsonError('Client introuvable');
        }

        $addresses = json_decode($customer['addresses'] ?? '[]', true) ?: [];
        $addresses = array_filter($addresses, fn($addr) => $addr['id'] !== $addressId);
        $addresses = array_values($addresses);

        // Si on supprime l'adresse par défaut, mettre la première comme défaut
        $hasDefault = false;
        foreach ($addresses as $addr) {
            if ($addr['is_default'] ?? false) {
                $hasDefault = true;
                break;
            }
        }

        if (!$hasDefault && count($addresses) > 0) {
            $addresses[0]['is_default'] = true;
        }

        Database::update('customers', [
            'addresses' => json_encode($addresses, JSON_UNESCAPED_UNICODE)
        ], [
            'id' => (int)$customerId
        ]);

        jsonSuccess();
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function setDefaultAddress(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $addressId = $requestData['address_id'] ?? null;

    if (!$customerId || !$addressId) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);
        if (!$customer) {
            jsonError('Client introuvable');
        }

        $addresses = json_decode($customer['addresses'] ?? '[]', true) ?: [];

        foreach ($addresses as &$addr) {
            $addr['is_default'] = ($addr['id'] === $addressId);
        }

        Database::update('customers', [
            'addresses' => json_encode($addresses, JSON_UNESCAPED_UNICODE)
        ], [
            'id' => (int)$customerId
        ]);

        jsonSuccess();
    } else {
        jsonError('Mode JSON non supporté');
    }
}

/* =========================
   GESTION PRÉFÉRENCES
   ========================= */

function updatePreferences(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;

    if (!$customerId) {
        jsonError('ID client manquant');
    }

    if ($useMySQL) {
        $customer = CustomerRepository::getById((int)$customerId);
        if (!$customer || $customer['restaurant_id'] !== SNACK_RESTAURANT_ID) {
            jsonError('Client introuvable');
        }

        $preferences = json_decode($customer['preferences'] ?? '{}', true) ?: [];

        // Mettre à jour les champs fournis
        if (isset($requestData['allergies'])) {
            $preferences['allergies'] = is_array($requestData['allergies'])
                ? $requestData['allergies']
                : explode(',', trim($requestData['allergies']));
        }

        if (isset($requestData['favorites'])) {
            $preferences['favorites'] = is_array($requestData['favorites'])
                ? $requestData['favorites']
                : [];
        }

        if (isset($requestData['notes'])) {
            $preferences['notes'] = trim($requestData['notes']);
        }

        if (isset($requestData['delivery_instructions'])) {
            $preferences['delivery_instructions'] = trim($requestData['delivery_instructions']);
        }

        if (isset($requestData['preferred_time'])) {
            $preferences['preferred_time'] = trim($requestData['preferred_time']);
        }

        Database::update('customers', [
            'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE)
        ], [
            'id' => (int)$customerId,
            'restaurant_id' => SNACK_RESTAURANT_ID
        ]);

        jsonSuccess(['preferences' => $preferences]);
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function updateAdminNotes(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $notes = trim($requestData['notes'] ?? '');

    if (!$customerId) {
        jsonError('ID client manquant');
    }

    if ($useMySQL) {
        $rowsAffected = Database::update('customers', [
            'admin_notes' => $notes
        ], [
            'id' => (int)$customerId,
            'restaurant_id' => SNACK_RESTAURANT_ID
        ]);

        if ($rowsAffected > 0 || $rowsAffected === 0) {
            jsonSuccess();
        } else {
            jsonError('Client introuvable');
        }
    } else {
        jsonError('Mode JSON non supporté');
    }
}

/* =========================
   GESTION TAGS
   ========================= */

function addTag(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $tag = trim($requestData['tag'] ?? '');

    if (!$customerId || empty($tag)) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        try {
            Database::insert('customer_tags', [
                'customer_id' => (int)$customerId,
                'tag' => $tag
            ]);

            jsonSuccess();
        } catch (Exception $e) {
            // Dupliquer = déjà existant
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonSuccess(); // Pas d'erreur si déjà existant
            } else {
                jsonError($e->getMessage());
            }
        }
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function removeTag(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? null;
    $tag = trim($requestData['tag'] ?? '');

    if (!$customerId || empty($tag)) {
        jsonError('Paramètres manquants');
    }

    if ($useMySQL) {
        Database::query(
            "DELETE FROM customer_tags WHERE customer_id = ? AND tag = ?",
            [(int)$customerId, $tag]
        );

        jsonSuccess();
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function getAvailableTags() {
    // Tags disponibles (statiques)
    $tags = [
        ['value' => 'VIP', 'label' => 'VIP', 'color' => '#fbbf24'],
        ['value' => 'Régulier', 'label' => 'Régulier', 'color' => '#60a5fa'],
        ['value' => 'Nouveau', 'label' => 'Nouveau', 'color' => '#34d399'],
        ['value' => 'Zone Centre', 'label' => 'Zone Centre', 'color' => '#a78bfa'],
        ['value' => 'Zone Est', 'label' => 'Zone Est', 'color' => '#f472b6'],
        ['value' => 'Zone Ouest', 'label' => 'Zone Ouest', 'color' => '#fb923c'],
        ['value' => 'Livraison', 'label' => 'Livraison', 'color' => '#22d3ee'],
        ['value' => 'Sur place', 'label' => 'Sur place', 'color' => '#4ade80'],
        ['value' => 'Entreprise', 'label' => 'Entreprise', 'color' => '#818cf8'],
    ];

    jsonSuccess(['tags' => $tags]);
}

/* =========================
   HISTORIQUE & STATS
   ========================= */

function getDetailedHistory(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;
    $limit = isset($requestData['limit']) ? (int)$requestData['limit'] : 10;

    if (!$customerId) {
        jsonError('ID client manquant');
    }

    if ($useMySQL) {
        $orders = CustomerRepository::getOrderHistory((int)$customerId, $limit);

        jsonSuccess(['orders' => $orders]);
    } else {
        jsonError('Mode JSON non supporté');
    }
}

function getFavoriteProducts(bool $useMySQL) {
    global $requestData;

    $customerId = $requestData['customer_id'] ?? $_GET['customer_id'] ?? null;

    if (!$customerId) {
        jsonError('ID client manquant');
    }

    if ($useMySQL) {
        // Récupérer les produits les plus commandés
        $favorites = Database::fetchAll(
            "SELECT
                oi.product_name as name,
                COUNT(*) as count,
                SUM(oi.quantity) as total_quantity,
                MAX(o.created_at) as last_ordered
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.customer_id = ?
             GROUP BY oi.product_name
             ORDER BY count DESC, total_quantity DESC
             LIMIT 5",
            [(int)$customerId]
        );

        jsonSuccess(['favorites' => $favorites]);
    } else {
        jsonError('Mode JSON non supporté');
    }
}
