<?php
/**
 * SnackApp v1 - Admin Dashboard
 * Support MySQL avec fallback JSON
 * Updated: 2025-12-24 - Enlarged order items and supplements
 */

// 🔒 SÉCURITÉ: Ne jamais afficher les erreurs en production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Bootstrap charge déjà tous les repositories
require_once __DIR__ . '/bootstrap.php';
requireAdmin();

// 🔒 SÉCURITÉ: Générer token CSRF pour la page
$csrfToken = getCsrfToken();

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

// ============================================
// CHARGER LES DONNÉES
// ============================================

if ($useMySQL) {
    // Mode MySQL
    $restaurant = getCurrentRestaurant();
    $restaurantName = $restaurant['name'] ?? 'Restaurant';
    $primaryColor = $restaurant['primary_color'] ?? '#c58a3a';

    // Toujours charger les settings depuis restaurant.json (source de vérité)
    $restaurantSettings = json_decode(file_get_contents(__DIR__ . '/../config/restaurant.json'), true) ?? [];

    // ⚡ PAGINATION: 10 commandes par page
    $ordersPage = isset($_GET['orders_page']) ? (int)$_GET['orders_page'] : 1;
    $ordersPerPage = 10;
    $allOrders = OrderRepository::getActiveOrders(SNACK_RESTAURANT_ID, 9999);
    $totalOrders = count($allOrders);
    $totalOrdersPages = ceil($totalOrders / $ordersPerPage);
    $ordersOffset = ($ordersPage - 1) * $ordersPerPage;
    $orders = array_slice($allOrders, $ordersOffset, $ordersPerPage);

    $archivedOrders = OrderRepository::getArchivedOrders(SNACK_RESTAURANT_ID, 500);
    $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    $stats = OrderRepository::getTodayStats(SNACK_RESTAURANT_ID);

    // Auto-archive des commandes > 24h
    OrderRepository::autoArchive(SNACK_RESTAURANT_ID);

    // Formater les commandes pour compatibilité template
    foreach ($orders as &$o) {
        $o['id'] = $o['order_number'] ?? $o['id'];
    }
    foreach ($archivedOrders as &$o) {
        $o['id'] = $o['order_number'] ?? $o['id'];
    }

    // Menu categories (mode JSON - lecture depuis menu.json)
    $menuJsonPath = SNACK_ROOT . '/config/menu.json';
    $menuData = [];
    if (file_exists($menuJsonPath)) {
        $menuData = json_decode(file_get_contents($menuJsonPath), true) ?: [];
    }
    $products = $menuData['menu']['categories'] ?? [];

    // Settings pour les formulaires
    $settings = RestaurantRepository::getSettings(SNACK_RESTAURANT_ID);
    $openingHours = RestaurantRepository::getOpeningHours(SNACK_RESTAURANT_ID);
    $faqItems = RestaurantRepository::getFaq(SNACK_RESTAURANT_ID);
    $whatsappConfig = RestaurantRepository::getWhatsAppConfig(SNACK_RESTAURANT_ID);

    // Stats avancées
    $weekStats = OrderRepository::getWeekStats(SNACK_RESTAURANT_ID);
    $monthStats = OrderRepository::getMonthStats(SNACK_RESTAURANT_ID);
    $topProducts = OrderRepository::getTopProducts(SNACK_RESTAURANT_ID, 30, 5);
    $peakHours = OrderRepository::getPeakHours(SNACK_RESTAURANT_ID, 30);
    $dailyRevenue = OrderRepository::getDailyRevenue(SNACK_RESTAURANT_ID, 7);
    
    // Données fidélité
     $loyaltyConfig = LoyaltyRepository::getConfig(SNACK_RESTAURANT_ID);
     $loyaltyRewards = LoyaltyRepository::getAllRewards(SNACK_RESTAURANT_ID);
     $loyaltyLeaderboard = LoyaltyRepository::getLeaderboard(SNACK_RESTAURANT_ID, 10);


} else {
    // Mode JSON (fallback)
    require_once __DIR__ . '/config.php';

    $config = loadConfig();
    $restaurantName = $config['restaurant']['name'] ?? $config['name'] ?? 'Restaurant';
    $primaryColor = $config['branding']['primaryColor'] ?? $config['theme']['colors']['brand'] ?? '#c58a3a';

    $restaurantSettings = json_decode(file_get_contents(__DIR__ . '/../config/restaurant.json'), true) ?? [];
    $orders = loadData('orders.json') ?? [];
    $archivedOrders = loadData('orders-archive.json') ?? [];
    $customers = loadData('customers.json') ?? [];

    // Auto-archive JSON
    $now = time();
    $archiveThreshold = 24 * 60 * 60;
    $ordersToKeep = [];

    foreach ($orders as $order) {
        $orderTime = strtotime($order['created_at'] ?? '');
        if ($orderTime && ($now - $orderTime) > $archiveThreshold && ($order['status'] ?? '') === 'completed') {
            $archivedOrders[] = $order;
        } else {
            $ordersToKeep[] = $order;
        }
    }

    if (count($ordersToKeep) !== count($orders)) {
        $orders = $ordersToKeep;
        saveData('orders.json', $orders);
        saveData('orders-archive.json', $archivedOrders);
    }

    // Trier et limiter
    usort($orders, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $orders = array_slice($orders, 0, 50);

    // Stats JSON
    $today = date('Y-m-d');
    $stats = ['pending' => 0, 'completed' => 0, 'today' => 0, 'revenue' => 0];
    foreach ($orders as $order) {
        if (strpos($order['created_at'], $today) === 0) {
            $stats['today']++;
            $stats['revenue'] += $order['total'] ?? 0;
        }
        $stats[($order['status'] ?? 'pending') === 'completed' ? 'completed' : 'pending']++;
    }

    $products = $config['menu']['categories'] ?? [];
    $openingHours = $restaurantSettings['openingHours'] ?? [];
    $faqItems = $restaurantSettings['faq']['items'] ?? [];
    $whatsappConfig = $restaurantSettings['whatsapp'] ?? [];
    $settings = $restaurantSettings;
}

// ============================================
// TRAITER LES ACTIONS POST
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
$action = $_POST['action'];

    // === GESTION FIDÉLITÉ ===

    // Ajouter une récompense
    if ($action === 'add_reward') {
        header('Content-Type: application/json');
        try {
            $rewardId = LoyaltyRepository::addReward(SNACK_RESTAURANT_ID, [
                'name' => $_POST['reward_name'] ?? '',
                'description' => $_POST['reward_description'] ?? '',
                'points_required' => (int) ($_POST['points_required'] ?? 100),
                'reward_type' => $_POST['reward_type'] ?? 'discount_percent',
                'reward_value' => (float) ($_POST['reward_value'] ?? 10)
            ]);
            echo json_encode(['success' => true, 'reward_id' => $rewardId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Supprimer une récompense
    if ($action === 'delete_reward') {
        header('Content-Type: application/json');
        $rewardId = (int) ($_POST['reward_id'] ?? 0);
        $deleted = LoyaltyRepository::deleteReward($rewardId);
        echo json_encode(['success' => $deleted]);
        exit;
    }

    // Modifier config fidélité
    if ($action === 'update_loyalty_config') {
        header('Content-Type: application/json');
        $enabled = ($_POST['loyalty_enabled'] ?? '1') === '1';
        $pointsPerEuro = (int) ($_POST['points_per_euro'] ?? 1);
        $updated = LoyaltyRepository::updateConfig(SNACK_RESTAURANT_ID, $enabled, $pointsPerEuro);
        echo json_encode(['success' => $updated]);
        exit;
    }

    // Ajouter des points manuellement à un client
    if ($action === 'add_loyalty_points') {
        header('Content-Type: application/json');
        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $points = (int) ($_POST['points'] ?? 0);
        if ($customerId > 0 && $points != 0) {
            LoyaltyRepository::addPoints($customerId, SNACK_RESTAURANT_ID, $points, null, 'Ajout manuel admin');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
        }
        exit;
    }

    // Rechercher un client (AJAX)
    if ($action === 'search_customer') {
        header('Content-Type: application/json');
        $query = trim($_POST['query'] ?? '');
        if (strlen($query) >= 2) {
            $results = CustomerRepository::search(SNACK_RESTAURANT_ID, $query);
            echo json_encode(['success' => true, 'customers' => $results]);
        } else {
            echo json_encode(['success' => false, 'customers' => []]);
        }
        exit;
    }

    // Utiliser une récompense (échanger des points)
    if ($action === 'redeem_reward') {
        header('Content-Type: application/json');
        try {
            $customerId = (int) ($_POST['customer_id'] ?? 0);
            $rewardId = (int) ($_POST['reward_id'] ?? 0);

            if ($customerId <= 0 || $rewardId <= 0) {
                throw new Exception('Données invalides');
            }

            // Récupérer le client et la récompense
            $customer = CustomerRepository::getById($customerId);
            $rewards = LoyaltyRepository::getAllRewards(SNACK_RESTAURANT_ID);
            $reward = null;
            foreach ($rewards as $r) {
                if ($r['id'] == $rewardId) {
                    $reward = $r;
                    break;
                }
            }

            if (!$customer || !$reward) {
                throw new Exception('Client ou récompense non trouvé');
            }

            $customerPoints = (int) ($customer['loyalty_points'] ?? 0);
            $pointsRequired = (int) $reward['points_required'];

            if ($customerPoints < $pointsRequired) {
                throw new Exception('Points insuffisants (' . $customerPoints . '/' . $pointsRequired . ')');
            }

            // Déduire les points
            $success = LoyaltyRepository::redeemPoints($customerId, SNACK_RESTAURANT_ID, $pointsRequired, $reward['name']);

            if ($success) {
                $newPoints = $customerPoints - $pointsRequired;
                echo json_encode([
                    'success' => true,
                    'message' => 'Récompense "' . $reward['name'] . '" appliquée !',
                    'new_points' => $newPoints
                ]);
            } else {
                throw new Exception('Erreur lors de l\'échange');
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Ajouter un client manuellement
    if ($action === 'add_customer') {
        header('Content-Type: application/json');
        try {
            $name = trim($_POST['customer_name'] ?? '');
            $phone = trim($_POST['customer_phone'] ?? '');

            if (empty($phone)) {
                throw new Exception("Le numéro de téléphone est requis");
            }

            $customerId = CustomerRepository::addCustomer(SNACK_RESTAURANT_ID, [
                'name' => $name ?: 'Client',
                'phone' => $phone
            ]);

            echo json_encode(['success' => true, 'customer_id' => $customerId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Supprimer un client
    if ($action === 'delete_customer') {
        header('Content-Type: application/json');
        try {
            $customerId = (int) ($_POST['customer_id'] ?? 0);

            if ($customerId <= 0) {
                throw new Exception("ID client invalide");
            }

            $deleted = CustomerRepository::deleteCustomer($customerId, SNACK_RESTAURANT_ID);

            if ($deleted) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Impossible de supprimer ce client");
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // Changement de statut commande
    if ($_POST['action'] === 'change_status' && isset($_POST['order_id'], $_POST['new_status'])) {
        if ($useMySQL) {
            $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $_POST['order_id']);
            if ($order) {
                $oldStatus = $order['status'] ?? '';
                OrderRepository::updateStatus($order['id'], $_POST['new_status']);

                // === ATTRIBUTION AUTOMATIQUE DES POINTS FIDÉLITÉ ===
                // Quand une commande passe à "completed" pour la première fois
                if ($_POST['new_status'] === 'completed' && $oldStatus !== 'completed') {
                    $loyaltyConfig = LoyaltyRepository::getConfig(SNACK_RESTAURANT_ID);

                    if ($loyaltyConfig['enabled'] && !empty($order['customer_id'])) {
                        // 1. D'abord déduire les points si une récompense était utilisée
                        if (!empty($order['loyalty_reward_id']) && empty($order['loyalty_redeemed'])) {
                            OrderRepository::redeemLoyaltyPoints($order['id'], SNACK_RESTAURANT_ID);
                        }

                        // 2. Ensuite ajouter les points gagnés sur cette commande
                        $orderTotal = (float) ($order['total'] ?? 0);
                        $pointsPerEuro = (int) ($loyaltyConfig['points_per_euro'] ?? 1);
                        $pointsEarned = (int) floor($orderTotal * $pointsPerEuro);

                        if ($pointsEarned > 0) {
                            LoyaltyRepository::addPoints(
                                $order['customer_id'],
                                SNACK_RESTAURANT_ID,
                                $pointsEarned,
                                $order['id'],
                                'Commande #' . ($order['order_number'] ?? $order['id']) . ' - ' . number_format($orderTotal, 0) . ' DA'
                            );
                        }
                    }
                }
            }
        } else {
            foreach ($orders as &$order) {
                if ($order['id'] === $_POST['order_id']) {
                    $order['status'] = $_POST['new_status'];
                    if ($_POST['new_status'] === 'completed') {
                        $order['completed_at'] = date('Y-m-d H:i:s');
                    }
                    break;
                }
            }
            saveData('orders.json', $orders);
        }
        header('Location: index.php');
        exit;
    }

    // Archiver toutes les commandes
    if ($_POST['action'] === 'clear_orders') {
        if ($useMySQL) {
            OrderRepository::archiveAllCompleted(SNACK_RESTAURANT_ID);
        } else {
            $archivedOrders = array_merge($archivedOrders, $orders);
            saveData('orders-archive.json', $archivedOrders);
            saveData('orders.json', []);
        }
        header('Location: index.php');
        exit;
    }

       // Sauvegarder les réglages
    if ($_POST['action'] === 'save_settings') {
        // Vérifier quel formulaire a été soumis
        $isContactForm = isset($_POST['social_type']);
        $isHoursForm = isset($_POST['hours']);

        // Parser les réseaux sociaux dynamiques (seulement si formulaire contact soumis)
        if ($isContactForm) {
            $socialTypes = $_POST['social_type'] ?? [];
            $socialValues = $_POST['social_value'] ?? [];
            $socials = ['instagram' => '', 'facebook' => '', 'tiktok' => '', 'snapchat' => '', 'extra' => []];

            foreach ($socialTypes as $i => $type) {
                $value = trim($socialValues[$i] ?? '');
                if (empty($value)) continue;

                if (in_array($type, ['instagram', 'facebook', 'tiktok', 'snapchat']) && empty($socials[$type])) {
                    $socials[$type] = $value;
                } else {
                    $socials['extra'][] = ['type' => $type, 'value' => $value];
                }
            }

            // Parser les téléphones supplémentaires
            $extraPhones = array_filter(array_map('trim', $_POST['extra_phones'] ?? []));

            if ($useMySQL) {
                // Contact et social
                $settingsData = [];
                if (isset($_POST['phone'])) $settingsData['phone'] = $_POST['phone'];
                if (isset($_POST['whatsapp'])) $settingsData['whatsapp_number'] = $_POST['whatsapp'];
                $settingsData['instagram'] = $socials['instagram'];
                $settingsData['facebook'] = $socials['facebook'];
                $settingsData['tiktok'] = $socials['tiktok'];
                $settingsData['snapchat'] = $socials['snapchat'];
                $settingsData['extra_socials'] = json_encode($socials['extra']);
                $settingsData['extra_phones'] = json_encode($extraPhones);

                if (!empty($settingsData)) {
                    RestaurantRepository::updateSettings(SNACK_RESTAURANT_ID, $settingsData);
                }
            }

            // Mettre à jour restaurant.json - contact et social
            if (isset($_POST['phone'])) $restaurantSettings['contact']['phone'] = $_POST['phone'];
            if (isset($_POST['whatsapp'])) $restaurantSettings['contact']['whatsappOrdersNumber'] = $_POST['whatsapp'];
            $restaurantSettings['contact']['extra_phones'] = $extraPhones;
            $restaurantSettings['social']['instagram'] = $socials['instagram'];
            $restaurantSettings['social']['facebook'] = $socials['facebook'];
            $restaurantSettings['social']['tiktok'] = $socials['tiktok'];
            $restaurantSettings['social']['snapchat'] = $socials['snapchat'];
            $restaurantSettings['social']['extra'] = $socials['extra'];
        }

        // Traitement des horaires (formulaire horaires)
        if ($isHoursForm) {
            if ($useMySQL) {
                $hours = [];
                foreach ($_POST['hours'] as $i => $h) {
                    $hours[] = [
                        'opens' => $h['opens'] ?? '18:30',
                        'closes' => $h['closes'] ?? '23:30'
                    ];
                }
                RestaurantRepository::updateOpeningHours(SNACK_RESTAURANT_ID, $hours);
            }

            // Mettre à jour restaurant.json - horaires
            $restaurantSettings['openingHours'] = [];
            $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            foreach ($days as $i => $day) {
                $dayData = ['day' => $day];
                // Nouveau format avec slots multiples
                if (isset($_POST['hours'][$i]['slots'])) {
                    $slots = [];
                    foreach ($_POST['hours'][$i]['slots'] as $slot) {
                        if (!empty($slot['opens']) && !empty($slot['closes'])) {
                            $slots[] = [
                                'opens' => $slot['opens'],
                                'closes' => $slot['closes']
                            ];
                        }
                    }
                    $dayData['slots'] = $slots;
                    // Garder compatibilité avec ancien format (premier créneau)
                    if (!empty($slots)) {
                        $dayData['opens'] = $slots[0]['opens'];
                        $dayData['closes'] = $slots[0]['closes'];
                    }
                } else {
                    // Ancien format
                    $dayData['opens'] = $_POST['hours'][$i]['opens'] ?? '18:30';
                    $dayData['closes'] = $_POST['hours'][$i]['closes'] ?? '23:30';
                }
                $restaurantSettings['openingHours'][] = $dayData;
            }
        }

        // Sauvegarder restaurant.json
        file_put_contents(__DIR__ . '/../config/restaurant.json', json_encode($restaurantSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        header('Location: index.php#settings');
        exit;
    }

    // Sauvegarder FAQ
    if ($_POST['action'] === 'save_faq') {
        $faqItemsNew = [];
        if (isset($_POST['faq_q']) && isset($_POST['faq_a'])) {
            foreach ($_POST['faq_q'] as $i => $q) {
                if (trim($q) !== '' && trim($_POST['faq_a'][$i]) !== '') {
                    $faqItemsNew[] = ['question' => trim($q), 'answer' => trim($_POST['faq_a'][$i])];
                }
            }
        }

        if ($useMySQL) {
            RestaurantRepository::updateFaq(SNACK_RESTAURANT_ID, $faqItemsNew);
        }
        // Toujours mettre à jour restaurant.json pour le site public
        $restaurantSettings['faq']['items'] = $faqItemsNew;
        file_put_contents(__DIR__ . '/../config/restaurant.json', json_encode($restaurantSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        header('Location: index.php#settings');
        exit;
    }

    // Sauvegarder configuration WhatsApp Business API
    if ($_POST['action'] === 'save_whatsapp_config') {
        if ($useMySQL) {
            RestaurantRepository::updateWhatsAppConfig(SNACK_RESTAURANT_ID, [
                'token' => $_POST['whatsapp_token'] ?? '',
                'phoneNumberId' => $_POST['whatsapp_phone_id'] ?? '',
                'businessAccountId' => $_POST['whatsapp_business_id'] ?? ''
            ]);
        } else {
            if (!isset($restaurantSettings['whatsapp'])) {
                $restaurantSettings['whatsapp'] = [];
            }
            $newToken = trim($_POST['whatsapp_token'] ?? '');
            if (!empty($newToken)) {
                $restaurantSettings['whatsapp']['token'] = $newToken;
            }
            $restaurantSettings['whatsapp']['phoneNumberId'] = trim($_POST['whatsapp_phone_id'] ?? '');
            $restaurantSettings['whatsapp']['businessAccountId'] = trim($_POST['whatsapp_business_id'] ?? '');
            $restaurantSettings['whatsapp']['configured'] = !empty($restaurantSettings['whatsapp']['token']);

            file_put_contents(__DIR__ . '/../config/restaurant.json', json_encode($restaurantSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
        header('Location: index.php#settings');
        exit;
    }
}

// ============================================
// EXPORT CSV
// ============================================

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');

    if ($_GET['export'] === 'customers') {
        header('Content-Disposition: attachment; filename=clients_' . date('Y-m-d') . '.csv');

        if ($useMySQL) {
            echo CustomerRepository::exportCSV(SNACK_RESTAURANT_ID);
        } else {
            $customers = loadData('customers.json') ?? [];
            echo "\xEF\xBB\xBF";
            echo "Nom,Téléphone,Commandes,Total dépensé\n";
            foreach ($customers as $c) {
                echo '"' . ($c['name'] ?? '') . '","' . ($c['phone'] ?? '') . '",' . ($c['orders_count'] ?? 0) . ',' . ($c['total_spent'] ?? 0) . "\n";
            }
        }
        exit;
    }

    if ($_GET['export'] === 'stats') {
        $period = $_GET['period'] ?? 'month';
        header('Content-Disposition: attachment; filename=stats_' . $period . '_' . date('Y-m-d') . '.csv');
        if ($useMySQL) {
            echo OrderRepository::exportStatsCSV(SNACK_RESTAURANT_ID, $period);
        }
        exit;
    }



    if ($_GET['export'] === 'archives') {
        $filterMonth = $_GET['month'] ?? '';
        $filteredOrders = $archivedOrders;

        // Filtrer par mois si spécifié
        if ($filterMonth) {
            $filteredOrders = array_filter($archivedOrders, function($o) use ($filterMonth) {
                $orderMonth = date('Y-m', strtotime($o['created_at'] ?? 'now'));
                return $orderMonth === $filterMonth;
            });
        }

        $filename = $filterMonth
            ? 'commandes_' . $filterMonth . '.csv'
            : 'commandes_archivees_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        echo "\xEF\xBB\xBF";
        echo "ID,Date,Heure,Client,Téléphone,Total,Statut,Produits\n";
        foreach ($filteredOrders as $o) {
            $items = array_map(fn($i) => ($i['quantity'] ?? 1) . 'x ' . ($i['name'] ?? $i['product_name'] ?? ''), $o['items'] ?? []);
            $dateTime = $o['created_at'] ?? '';
            $date = date('Y-m-d', strtotime($dateTime));
            $time = date('H:i', strtotime($dateTime));
            echo '"' . ($o['id'] ?? $o['order_number'] ?? '') . '","' . $date . '","' . $time . '","' . ($o['customer_name'] ?? '') . '","' . ($o['customer_phone'] ?? '') . '",' . ($o['total'] ?? 0) . ',"' . ($o['status'] ?? '') . '","' . implode('; ', $items) . "\"\n";
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($restaurantName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #0f1525 100%);
            background-attachment: fixed;
            color: #fff;
            padding-bottom: 90px;
            min-height: 100vh;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.03) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 16px 20px;
            margin-bottom: 20px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .header h1 { font-size: 20px; font-weight: 700; }
        .btn {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, <?php echo $primaryColor; ?>dd 100%);
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px <?php echo $primaryColor; ?>40;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px <?php echo $primaryColor; ?>50; }
        .btn:active { transform: translateY(0); }
        .btn-sm { padding: 8px 14px; font-size: 12px; border-radius: 10px; }
        .btn-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 15px rgba(16,185,129,0.3); }
        .btn-gray { background: linear-gradient(135deg, #4b5563 0%, #374151 100%); box-shadow: 0 4px 15px rgba(75,85,99,0.3); }
        .btn-whatsapp { background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); box-shadow: 0 4px 15px rgba(37,211,102,0.3); }
        .card {
            background: linear-gradient(135deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 4px 24px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .card:hover { border-color: rgba(255,255,255,0.12); }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .stat-card {
            background: #1e293b;
            padding: 18px;
            text-align: center;
            border-radius: 16px;
            border: 1px solid #374151;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 28px rgba(0,0,0,0.5); }
        .stat-number { font-size: 28px; font-weight: 700; margin-top: 6px; background: linear-gradient(135deg, #fff 0%, #e0e0e0 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(180deg, rgba(20,20,35,0.95) 0%, rgba(15,15,25,0.98) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-around;
            padding: 8px 8px calc(8px + env(safe-area-inset-bottom));
            z-index: 1000;
        }
        .nav-btn {
            flex: 1;
            text-align: center;
            padding: 8px 4px;
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 10px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        .nav-btn:hover { background: rgba(255,255,255,0.05); }
        .nav-btn.active { color: <?php echo $primaryColor; ?>; background: <?php echo $primaryColor; ?>15; }
        .nav-btn i { display: block; font-size: 20px; margin-bottom: 4px; }
        .section { display: none; animation: fadeIn 0.3s ease; }
        .section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #9ca3af; font-size: 13px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            background: rgba(0,0,0,0.3);
            color: white;
            font-size: 15px;
            transition: all 0.2s ease;
        }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
            box-shadow: 0 0 0 3px <?php echo $primaryColor; ?>30;
        }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .hours-grid { display: grid; grid-template-columns: 100px 1fr 1fr; gap: 10px; align-items: center; margin-bottom: 10px; }
        .hours-grid span { color: #9ca3af; font-weight: 500; }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        .checkbox-item:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); }
        .checkbox-item input { width: 20px; height: 20px; accent-color: <?php echo $primaryColor; ?>; }
        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 768px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .hours-grid { grid-template-columns: 1fr; gap: 8px; }
            .hours-grid span { font-weight: 600; margin-bottom: 4px; }
        }

        @media (max-width: 480px) {
            body { padding-bottom: calc(75px + env(safe-area-inset-bottom)); }
            .container { padding: 12px; }

            /* Header mobile - style app */
            .header {
                padding: 14px;
                flex-direction: row;
                align-items: center;
                gap: 10px;
                margin: 0 -12px 16px;
                border-radius: 0 0 20px 20px;
                position: sticky;
                top: 0;
                z-index: 100;
            }
            .header h1 { font-size: 17px; }
            .header > div:last-child { margin-left: auto; }

            /* Boutons tactiles */
            .btn {
                padding: 12px 16px;
                font-size: 14px;
                border-radius: 14px;
                min-height: 44px;
            }
            .btn-sm { padding: 10px 14px; font-size: 12px; min-height: 40px; }

            /* Stats - style moderne */
            .stats { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card {
                padding: 16px 12px;
                border-radius: 18px;
            }
            .stat-card > div:first-child { font-size: 11px; opacity: 0.8; }
            .stat-number { font-size: 24px; }

            /* Cards - style fluide */
            .card {
                padding: 16px;
                margin-bottom: 12px;
                border-radius: 18px;
            }

            /* Navigation basse - style iOS/Android */
            .bottom-nav {
                padding: 6px 12px calc(6px + env(safe-area-inset-bottom));
                gap: 4px;
            }
            .nav-btn {
                padding: 8px 6px;
                font-size: 9px;
                border-radius: 14px;
                min-width: 54px;
            }
            .nav-btn i { font-size: 18px; margin-bottom: 3px; }

            /* Commandes - layout mobile */
            .card > div:first-child { flex-direction: column; gap: 10px; }
            .card > div:first-child > div:last-child { text-align: left !important; }

            /* Filtres - scroll horizontal fluide */
            .customer-filters-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                scroll-snap-type: x mandatory;
                padding-bottom: 8px;
                margin: 0 -12px;
                padding: 0 12px 10px;
                scrollbar-width: none;
            }
            .customer-filters-wrap::-webkit-scrollbar { display: none; }
            .customer-filters-wrap > div {
                display: flex;
                gap: 8px;
                min-width: max-content;
            }
            .customer-filters-wrap .btn { scroll-snap-align: start; }

            /* Formulaires tactiles */
            .form-group { margin-bottom: 14px; }
            .form-group label { font-size: 12px; margin-bottom: 8px; }
            .form-group input, .form-group textarea, .form-group select {
                padding: 14px 16px;
                font-size: 16px;
                border-radius: 14px;
                min-height: 48px;
            }

            /* Horaires - style card */
            .hours-grid {
                background: rgba(0,0,0,0.25);
                padding: 14px;
                border-radius: 14px;
                margin-bottom: 10px;
                margin-bottom: 10px;
            }
            .hours-grid input[type="time"] {
                width: 100%;
                padding: 10px;
            }

            /* Archives */
            .card > div[style*="border-bottom"] {
                flex-wrap: wrap;
                gap: 8px;
            }

            /* Section titres */
            h2 { font-size: 18px; }
            h3 { font-size: 15px; }

            /* Toggle restaurant */
            #restaurant-status-toggle h3 { font-size: 14px; }
            #status-indicator { font-size: 24px !important; }
        }

        /* Très petits écrans */
        @media (max-width: 360px) {
            .container { padding: 8px; }
            .btn { padding: 8px 10px; font-size: 12px; }
            .stat-number { font-size: 18px; }
            .nav-btn i { font-size: 14px; }
            .nav-btn div { font-size: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><?php echo htmlspecialchars($restaurantName); ?></h1>
                <p style="color: #9ca3af; font-size: 12px;">Administration</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn btn-sm"><i class="fas fa-sync-alt"></i></a>
                <a href="logout.php" class="btn btn-sm btn-gray"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>

        <!-- Toggle Restaurant -->
        <div id="restaurant-status-toggle" class="card" style="border: 2px solid #444; cursor: pointer;" onclick="toggleRestaurantStatus()">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="font-size: 16px;"><i class="fas fa-power-off"></i> <span id="status-text">Chargement...</span></h3>
                    <p style="color: #9ca3af; font-size: 12px;" id="status-subtitle">Cliquez pour changer</p>
                </div>
                <div id="status-indicator" style="font-size: 28px;"><i class="fas fa-circle-notch fa-spin"></i></div>
            </div>
        </div>

        <!-- COMMANDES -->
        <div id="section-orders" class="section active" style="background: #0f1419; padding: 20px; border-radius: 16px; margin: -10px; margin-bottom: 20px;">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h2><i class="fas fa-receipt" style="color: <?php echo $primaryColor; ?>;"></i> Commandes (<?php echo $totalOrders; ?>)</h2>
                    <?php if (!empty($orders)): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #9ca3af; font-size: 14px;">
                            <input type="checkbox" id="selectAllOrders" onclick="toggleAllOrders(this)" style="width: 18px; height: 18px; cursor: pointer;">
                            <span>Tout sélectionner</span>
                        </label>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px;">
                    <?php if (!empty($orders)): ?>
                    <!-- Bouton supprimer sélection (caché par défaut) -->
                    <button id="deleteSelectedOrders" onclick="deleteSelectedOrders()"
                            style="display: none; padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <i class="fas fa-trash"></i> Supprimer (<span id="selectedOrdersCount">0</span>)
                    </button>
                    <form method="POST" style="margin: 0;">
                        <input type="hidden" name="action" value="clear_orders">
                        <button type="submit" onclick="return confirm('Archiver toutes les commandes terminées ?')" class="btn btn-sm btn-gray"><i class="fas fa-archive"></i> Archiver tout</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats" style="margin-bottom: 20px;">
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div style="color: #f59e0b;"><i class="fas fa-clock"></i></div>
                    <div class="stat-number" style="color: #f59e0b;"><?php echo $stats['pending']; ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">En attente</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #10b981;">
                    <div style="color: #10b981;"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-number" style="color: #10b981;"><?php echo $stats['completed']; ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">Terminées</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid <?php echo $primaryColor; ?>;">
                    <div style="color: <?php echo $primaryColor; ?>;"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-number" style="color: <?php echo $primaryColor; ?>;"><?php echo $stats['today']; ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">Aujourd'hui</div>
                </div>
            </div>

            <!-- Liste des commandes -->
            <?php if (empty($orders)): ?>
                <div class="card" style="text-align: center; padding: 60px;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #555; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af; font-size: 16px;">Aucune commande en cours</p>
                    <p style="color: #6b7280; font-size: 13px;">Les nouvelles commandes apparaîtront ici</p>
                </div>
            <?php else: ?>
                <!-- NOUVEAU DESIGN: Grid 3 colonnes responsive (espacement identique clients) -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                <?php foreach ($orders as $order):
                    $isCompleted = ($order['status'] ?? '') === 'completed';
                    $statusColor = $isCompleted ? '#10b981' : '#f59e0b';
                    $statusIcon = $isCompleted ? 'check-circle' : 'clock';
                    $statusText = $isCompleted ? 'Terminée' : 'En attente';
                    $items = $order['items'] ?? [];
                    $itemCount = count($items);
                ?>
                <!-- CARTE COMMANDE (Design moderne sombre) -->
                <div class="card order-card-compact" style="position: relative; border-left: 4px solid <?php echo $statusColor; ?>; padding: 18px; cursor: pointer; transition: all 0.3s ease; background: #1e293b; border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"
                     onclick="showOrderDetails('<?php echo htmlspecialchars($order['id'], ENT_QUOTES); ?>')"
                     onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.5)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.3)'">

                    <!-- Checkbox sélection (en haut à gauche) -->
                    <input type="checkbox" class="order-checkbox" data-order-id="<?php echo htmlspecialchars($order['id'], ENT_QUOTES); ?>"
                           onclick="event.stopPropagation(); updateOrdersSelection();"
                           style="position: absolute; top: 12px; left: 12px; width: 20px; height: 20px; cursor: pointer; z-index: 10;">


                    <!-- Header: Numéro + Statut -->
                    <div style="display: flex; justify-between; align-items: center; margin-bottom: 14px; padding-left: 30px;">
                        <span style="font-size: 18px; font-weight: 700; color: white;">
                            <i class="fas fa-hashtag" style="font-size: 14px;"></i><?php echo htmlspecialchars($order['id']); ?>
                        </span>
                        <span style="background: <?php echo $statusColor; ?>; color: white; padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; box-shadow: 0 2px 6px <?php echo $statusColor; ?>44;">
                            <i class="fas fa-<?php echo $statusIcon; ?>"></i> <?php echo $statusText; ?>
                        </span>
                    </div>

                    <!-- Client: Avatar + Nom + Téléphone -->
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 2px solid #374151;">
                        <div style="width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #059669, #10b981); display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold; color: white; flex-shrink: 0; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
                            <?php echo strtoupper(substr($order['customer_name'] ?? 'C', 0, 1)); ?>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 15px; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars($order['customer_name'] ?? 'Client'); ?>
                            </div>
                            <div style="font-size: 12px; color: #9ca3af; margin-top: 2px;">
                                <i class="fas fa-phone" style="color: #10b981;"></i>
                                <?php
                                $phone = $order['customer_phone'] ?? '';
                                echo htmlspecialchars(strlen($phone) > 12 ? substr($phone, 0, 12) . '..' : $phone);
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Items (3 premiers) -->
                    <div style="margin-bottom: 12px;">
                        <div style="font-size: 11px; color: #9ca3af; margin-bottom: 6px; font-weight: 600;">
                            <i class="fas fa-shopping-bag" style="color: #10b981;"></i> <?php echo $itemCount; ?> article<?php echo $itemCount > 1 ? 's' : ''; ?>
                        </div>
                        <?php
                        $displayItems = array_slice($items, 0, 3);
                        foreach ($displayItems as $item):
                        ?>
                            <div style="font-size: 12px; color: #e5e7eb; padding: 4px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <strong style="color: <?php echo $primaryColor; ?>;"><?php echo $item['quantity'] ?? 1; ?>x</strong>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($itemCount > 3): ?>
                            <div style="font-size: 11px; color: #9ca3af; font-style: italic; margin-top: 4px;">
                                <i class="fas fa-ellipsis-h" style="font-size: 9px;"></i> <?php echo $itemCount - 3; ?> autre<?php echo ($itemCount - 3) > 1 ? 's' : ''; ?>...
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Total + Fidélité -->
                    <div style="margin-bottom: 12px; padding: 12px; background: #0f172a; border-radius: 12px; border: 2px solid <?php echo $primaryColor; ?>;">
                        <div style="font-size: 22px; font-weight: 800; color: <?php echo $primaryColor; ?>; text-align: center;">
                            <i class="fas fa-coins" style="font-size: 18px; margin-right: 4px;"></i><?php echo number_format($order['total'] ?? 0, 0); ?> DA
                        </div>
                        <?php if (!empty($order['loyalty_reward_id']) || !empty($order['loyalty_code'])): ?>
                            <div style="text-align: center; margin-top: 6px; font-size: 11px; color: #f59e0b; font-weight: 700; background: rgba(245,158,11,0.1); padding: 4px 8px; border-radius: 6px; display: inline-block; width: 100%;">
                                <i class="fas fa-gift"></i>
                                <?php if (!empty($order['loyalty_code'])): ?>
                                    <?php echo htmlspecialchars($order['loyalty_code']); ?>
                                <?php endif; ?>
                                <?php if (!empty($order['loyalty_reward_id'])): ?>
                                    - Récompense appliquée
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Heure + Note -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; color: #9ca3af; font-weight: 600;">
                            <i class="fas fa-clock" style="color: #9ca3af;"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?>
                        </span>
                        <?php if (!empty($order['notes'])): ?>
                            <span style="background: #dc2626; color: white; padding: 4px 8px; border-radius: 8px; font-size: 10px; font-weight: 700; box-shadow: 0 2px 6px rgba(220, 38, 38, 0.3);">
                                <i class="fas fa-sticky-note"></i> Note
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Bouton imprimer (visible si imprimante connectée) -->
                    <div class="print-button-container" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 2px solid #374151;">
                        <button class="btn-print-order"
                                data-order-id="<?php echo htmlspecialchars($order['id'], ENT_QUOTES); ?>"
                                onclick="event.stopPropagation(); printOrder('<?php echo htmlspecialchars($order['id'], ENT_QUOTES); ?>');"
                                style="width: 100%; padding: 10px; background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);"
                                onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.5)'"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 2px 8px rgba(16, 185, 129, 0.3)'">
                            <i class="fas fa-print"></i> Imprimer le ticket
                        </button>
                    </div>

                    <!-- Bouton détails -->
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 2px solid #374151;">
                        <div style="text-align: center; font-size: 12px; color: #10b981; font-weight: 700;">
                            <i class="fas fa-info-circle"></i> Cliquer pour plus de détails
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- ⚡ PAGINATION COMMANDES -->
                <?php if ($totalOrdersPages > 1): ?>
                <div style="display: flex; justify-content: center; align-items: center; gap: 15px; margin-top: 30px; padding: 20px;">
                    <?php if ($ordersPage > 1): ?>
                        <a href="?orders_page=<?php echo $ordersPage - 1; ?>#orders"
                           style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                           onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                            <i class="fas fa-chevron-left"></i> Précédent
                        </a>
                    <?php endif; ?>

                    <span style="color: white; font-weight: 600; padding: 0 10px;">
                        Page <?php echo $ordersPage; ?> / <?php echo $totalOrdersPages; ?>
                        <span style="color: #9ca3af; font-size: 13px; margin-left: 5px;">(<?php echo $totalOrders; ?> commandes)</span>
                    </span>

                    <?php if ($ordersPage < $totalOrdersPages): ?>
                        <a href="?orders_page=<?php echo $ordersPage + 1; ?>#orders"
                           style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background: rgba(255,255,255,0.1); color: white; text-decoration: none; border-radius: 10px; font-weight: 600; transition: all 0.3s;"
                           onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                           onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                            Suivant <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- MODAL DÉTAILS COMMANDE -->
            <div id="order-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; overflow-y: auto; padding: 20px;" onclick="if(event.target === this) closeOrderDetails()">
                <div style="max-width: 600px; margin: 40px auto; background: #1a1f2e; border-radius: 20px; padding: 25px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);" onclick="event.stopPropagation()">
                    <div id="order-details-content"></div>
                </div>
            </div>

            <!-- MODAL SÉLECTION LIVREUR -->
            <div id="delivery-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 10000; align-items: center; justify-content: center; padding: 20px;" onclick="if(event.target === this) closeDeliveryModal()">
                <div style="max-width: 500px; width: 100%; background: #1a1f2e; border-radius: 20px; padding: 25px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1);" onclick="event.stopPropagation()">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: white; margin: 0; font-size: 20px;">
                            <i class="fas fa-motorcycle" style="color: #f59e0b;"></i> Sélectionner un livreur
                        </h3>
                        <button onclick="closeDeliveryModal()" style="background: #374151; border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; font-size: 18px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <p style="color: #9ca3af; font-size: 14px; margin-bottom: 20px;">
                        Choisissez un livreur pour lui envoyer les détails de la commande sur WhatsApp
                    </p>

                    <div style="background: #0f1419; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #1e293b;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; color: #9ca3af; font-size: 11px; text-transform: uppercase;">Livreur</th>
                                    <th style="padding: 12px; text-align: left; color: #9ca3af; font-size: 11px; text-transform: uppercase;">WhatsApp</th>
                                    <th style="padding: 12px;"></th>
                                </tr>
                            </thead>
                            <tbody id="livreurs-list">
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 30px; color: #9ca3af;">
                                        <i class="fas fa-spinner fa-spin"></i> Chargement...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <a href="livreurs-manager.php" style="color: #60a5fa; text-decoration: none; font-size: 13px;">
                            <i class="fas fa-cog"></i> Gérer les livreurs
                        </a>
                    </div>
                </div>
            </div>

            <script>
            // Stocker les données des commandes pour le modal
            const ordersData = <?php echo json_encode($orders); ?>;

            function showOrderDetails(orderId) {
                const order = ordersData.find(o => o.id === orderId);
                if (!order) return;

                const isCompleted = (order.status || '') === 'completed';
                const statusColor = isCompleted ? '#10b981' : '#f59e0b';
                const statusIcon = isCompleted ? 'check-circle' : 'clock';
                const statusText = isCompleted ? 'Terminée' : 'En attente';
                const primaryColor = '<?php echo $primaryColor; ?>';

                let html = `
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="color: white; margin: 0; font-size: 22px;">
                            <i class="fas fa-receipt" style="color: ${primaryColor};"></i>
                            Commande #${order.id}
                        </h3>
                        <button onclick="closeOrderDetails()" style="background: #374151; border: none; color: white; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; font-size: 18px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Statut -->
                    <div style="text-align: center; padding: 15px; background: ${statusColor}22; border: 2px solid ${statusColor}; border-radius: 12px; margin-bottom: 20px;">
                        <i class="fas fa-${statusIcon}" style="font-size: 24px; color: ${statusColor};"></i>
                        <div style="font-size: 16px; font-weight: 600; color: ${statusColor}; margin-top: 8px;">${statusText}</div>
                        <div style="font-size: 12px; color: #9ca3af; margin-top: 4px;">
                            ${new Date(order.created_at).toLocaleDateString('fr-FR')} à ${new Date(order.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}
                        </div>
                    </div>

                    <!-- Client -->
                    <div style="padding: 15px; background: #2a2a3e; border-radius: 12px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, ${primaryColor}, #d97706); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: white;">
                                ${(order.customer_name || 'C').charAt(0).toUpperCase()}
                            </div>
                            <div>
                                <div style="font-size: 16px; font-weight: 600; color: white;">${order.customer_name || 'Client'}</div>
                                <a href="tel:${order.customer_phone}" style="color: #10b981; text-decoration: none; font-size: 14px;">
                                    <i class="fas fa-phone"></i> ${order.customer_phone}
                                </a>
                                ${order.loyalty_code ? `<div style="display: inline-block; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; margin-left: 10px;"><i class="fas fa-id-card"></i> ${order.loyalty_code}</div>` : ''}
                            </div>
                        </div>
                    </div>

                    <!-- Mode de commande + Paiement -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div style="padding: 12px; background: #2a2a3e; border-radius: 10px; border-left: 3px solid #3b82f6;">
                            <div style="font-size: 11px; color: #9ca3af; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Mode</div>
                            <div style="font-size: 14px; color: white; font-weight: 600;">
                                ${(() => {
                                    // Extraire le mode simple depuis mode_notes
                                    if (order.mode_notes) {
                                        if (order.mode_notes.includes('📦') || order.mode_notes.includes('À EMPORTER')) {
                                            return '<i class="fas fa-shopping-bag" style="color: #10b981;"></i> À emporter';
                                        } else if (order.mode_notes.includes('🏠') || order.mode_notes.includes('SUR PLACE')) {
                                            return '<i class="fas fa-utensils" style="color: #f59e0b;"></i> Sur place';
                                        } else if (order.mode_notes.includes('🚗') || order.mode_notes.includes('LIVRAISON')) {
                                            return '<i class="fas fa-motorcycle" style="color: #3b82f6;"></i> Livraison';
                                        }
                                    }
                                    // Fallback sur notes
                                    if (order.notes && order.notes.includes('LIVRAISON')) return '<i class="fas fa-motorcycle" style="color: #3b82f6;"></i> Livraison';
                                    if (order.notes && order.notes.includes('SUR PLACE')) return '<i class="fas fa-utensils" style="color: #f59e0b;"></i> Sur place';
                                    return '<i class="fas fa-shopping-bag" style="color: #10b981;"></i> À emporter';
                                })()}
                            </div>
                        </div>
                        <div style="padding: 12px; background: #2a2a3e; border-radius: 10px; border-left: 3px solid #10b981;">
                            <div style="font-size: 11px; color: #9ca3af; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Paiement</div>
                            <div style="font-size: 14px; color: white; font-weight: 600;">
                                ${(() => {
                                    let paymentIcon = '';
                                    let paymentText = '';
                                    let extraInfo = '';

                                    // Type de paiement
                                    if (order.payment_method === 'ccp') {
                                        paymentIcon = '<i class="fas fa-credit-card" style="color: #10b981;"></i>';
                                        paymentText = ' CCP';
                                    } else if (order.payment_method === 'baridi_mob') {
                                        paymentIcon = '<i class="fas fa-mobile-alt" style="color: #fbbf24;"></i>';
                                        paymentText = ' BaridiMob';
                                    } else {
                                        paymentIcon = '<i class="fas fa-money-bill-wave" style="color: #10b981;"></i>';
                                        paymentText = ' Espèces';

                                        // Info appoint/monnaie pour espèces
                                        if (order.delivery_instructions) {
                                            if (order.delivery_instructions.includes('monnaie exacte')) {
                                                extraInfo = '<br><span style="color: #10b981; font-size: 12px;"><i class="fas fa-check-circle"></i> J\'ai l\'appoint</span>';
                                            } else {
                                                const changeMatch = order.delivery_instructions.match(/Monnaie pour (\d+) DA/);
                                                if (changeMatch) {
                                                    extraInfo = `<br><span style="color: #fbbf24; font-size: 12px;"><i class="fas fa-coins"></i> Prévoir ${changeMatch[1]} DA</span>`;
                                                }
                                            }
                                        }
                                    }

                                    return paymentIcon + paymentText + extraInfo;
                                })()}
                            </div>
                        </div>
                    </div>
                `;

                // Précommande (date/heure) pour "À emporter"
                if (order.preorder_date || order.preorder_time) {
                    let preorderDisplay = '';
                    if (order.preorder_date && order.preorder_time) {
                        const dateObj = new Date(order.preorder_date);
                        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        const formattedDate = dateObj.toLocaleDateString('fr-FR', options);
                        preorderDisplay = `<strong style="color: #10b981;">${formattedDate}</strong> à <strong style="color: #10b981;">${order.preorder_time}</strong>`;
                    } else if (order.preorder_date) {
                        const dateObj = new Date(order.preorder_date);
                        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                        const formattedDate = dateObj.toLocaleDateString('fr-FR', options);
                        preorderDisplay = `<strong style="color: #10b981;">${formattedDate}</strong>`;
                    } else if (order.preorder_time) {
                        preorderDisplay = `<strong style="color: #10b981;">${order.preorder_time}</strong>`;
                    }

                    html += `
                        <div style="padding: 14px; background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.05) 100%); border: 2px solid #10b981; border-radius: 10px; margin-bottom: 20px;">
                            <div style="font-size: 11px; color: #6ee7b7; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="fas fa-clock"></i> ⏰ PRÉCOMMANDE
                            </div>
                            <div style="font-size: 14px; color: white; font-weight: 600;">
                                ${preorderDisplay}
                            </div>
                        </div>
                    `;
                }

                // Salle et Table pour "Sur place"
                if ((order.mode_notes && order.mode_notes.includes('SUR PLACE')) || (order.notes && order.notes.includes('SUR PLACE'))) {
                    // Chercher d'abord dans mode_notes, puis fallback sur notes
                    const searchText = order.mode_notes || order.notes || '';
                    const salleMatch = searchText.match(/Salle (Famille|Femme)/);
                    const tableMatch = searchText.match(/Table ([A-Z0-9]+)/i);

                    html += `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                            <div style="padding: 12px; background: #2a2a3e; border-radius: 10px; border-left: 3px solid #ec4899;">
                                <div style="font-size: 11px; color: #9ca3af; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Salle</div>
                                <div style="font-size: 14px; color: white; font-weight: 600;">
                                    ${salleMatch && salleMatch[1] === 'Famille' ? '<i class="fas fa-users" style="color: #ec4899;"></i> Famille' : '<i class="fas fa-female" style="color: #ec4899;"></i> Femme'}
                                </div>
                            </div>
                            <div style="padding: 12px; background: #2a2a3e; border-radius: 10px; border-left: 3px solid #8b5cf6;">
                                <div style="font-size: 11px; color: #9ca3af; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Table</div>
                                <div style="font-size: 14px; color: white; font-weight: 600;">
                                    <i class="fas fa-chair" style="color: #8b5cf6;"></i> ${tableMatch ? tableMatch[1] : 'N/A'}
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Adresse de livraison si applicable
                if (order.delivery_address) {
                    html += `
                        <div style="padding: 12px; background: linear-gradient(135deg, rgba(59,130,246,0.1) 0%, rgba(37,99,235,0.05) 100%); border: 1px solid rgba(59,130,246,0.3); border-radius: 10px; margin-bottom: 20px;">
                            <div style="font-size: 11px; color: #60a5fa; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">
                                <i class="fas fa-map-marker-alt"></i> Adresse de livraison
                            </div>
                            <div style="font-size: 14px; color: white; font-weight: 600;">${order.delivery_address}</div>
                            ${order.delivery_instructions ? `<div style="font-size: 12px; color: #9ca3af; margin-top: 6px; font-style: italic;"><i class="fas fa-info-circle"></i> ${order.delivery_instructions}</div>` : ''}
                        </div>
                    `;
                }

                html += `

                    <!-- Articles COMPLETS -->
                    <div style="background: #1e293b; padding: 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #374151;">
                        <div style="font-weight: 600; color: white; margin-bottom: 12px; font-size: 14px;">
                            <i class="fas fa-shopping-bag"></i> Articles (${(order.items || []).length})
                        </div>
                `;

                (order.items || []).forEach((item, i) => {
                    html += `
                        <div style="padding: ${i > 0 ? '12px' : '8px'} 0; ${i > 0 ? 'border-top: 1px solid #374151; margin-top: 8px;' : ''}">
                            <div style="color: #e5e7eb; font-size: 15px; font-weight: 600; margin-bottom: 4px;">
                                <strong style="color: ${primaryColor}; font-size: 16px;">${item.quantity || 1}x</strong>
                                <strong>${item.name}</strong>
                            </div>
                    `;

                    if (item.supplements && item.supplements.length > 0) {
                        html += `<div style="color: #fbbf24; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;">
                            <i class="fas fa-plus-circle" style="font-size: 11px;"></i> ${item.supplements.map(s => s.name).join(', ')}
                        </div>`;
                    }

                    if (item.removed_ingredients && item.removed_ingredients.length > 0) {
                        html += `<div style="color: #ef4444; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;">
                            <i class="fas fa-minus-circle" style="font-size: 11px;"></i> Sans: ${item.removed_ingredients.join(', ')}
                        </div>`;
                    }

                    if (item.selected_drink) {
                        html += `<div style="color: #60a5fa; font-size: 13px; margin-left: 28px; margin-top: 4px;">
                            <i class="fas fa-glass" style="font-size: 11px;"></i> ${item.selected_drink}
                        </div>`;
                    }

                    if (item.selected_options) {
                        const opts = typeof item.selected_options === 'string' ? JSON.parse(item.selected_options) : item.selected_options;
                        if (opts) {
                            if (opts.boisson) html += `<div style="color: #60a5fa; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-glass" style="font-size: 11px;"></i> ${opts.boisson}</div>`;
                            if (opts.viennoiserie) html += `<div style="color: #f59e0b; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-bread-slice" style="font-size: 11px;"></i> ${opts.viennoiserie}</div>`;
                            if (opts.patisserie) html += `<div style="color: #ec4899; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-cake" style="font-size: 11px;"></i> ${opts.patisserie}</div>`;
                            if (opts.selectedBeverage && opts.selectedBeverage.name) html += `<div style="color: #10b981; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-salad" style="font-size: 11px;"></i> ${opts.selectedBeverage.name}</div>`;
                            if (opts.selectedVariant && opts.selectedVariant.name) html += `<div style="color: #8b5cf6; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-coffee" style="font-size: 11px;"></i> ${opts.selectedVariant.name}</div>`;
                            // Ancien système (rétrocompatibilité)
                            if (opts.selectedCapsule) html += `<div style="color: #6366f1; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-hashtag" style="font-size: 11px;"></i> Capsule n°${opts.selectedCapsule}</div>`;
                            // Nouveau système L'Or (numéros d'intensité) - support objet et string
                            if (opts.selectedCapsuleNumber) {
                                const capsuleNum = typeof opts.selectedCapsuleNumber === 'object' ? opts.selectedCapsuleNumber.name : opts.selectedCapsuleNumber;
                                html += `<div style="color: #6366f1; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-hashtag" style="font-size: 11px;"></i> Intensité L'Or: ${capsuleNum}</div>`;
                            }
                            // Nouveau système Caps (couleurs capsules) - support objet et string
                            if (opts.selectedCapsuleColor) {
                                const capsuleColor = typeof opts.selectedCapsuleColor === 'object' ? opts.selectedCapsuleColor.name : opts.selectedCapsuleColor;
                                html += `<div style="color: #9b87f5; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-palette" style="font-size: 11px;"></i> Couleur capsule: ${capsuleColor}</div>`;
                            }
                            if (opts.crepe) html += `<div style="color: #f59e0b; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-utensils" style="font-size: 11px;"></i> Crêpe: ${opts.crepe}</div>`;
                            if (opts.sauce) html += `<div style="color: #ef4444; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-droplet" style="font-size: 11px;"></i> Sauce: ${opts.sauce}</div>`;
                            if (opts.accompagnement) html += `<div style="color: #fbbf24; font-size: 13px; font-weight: 600; margin-left: 28px; margin-top: 4px;"><i class="fas fa-bowl-food" style="font-size: 11px;"></i> ${opts.accompagnement}</div>`;
                        }
                    }

                    html += `</div>`;
                });

                html += `</div>`;

                // Notes
                if (order.notes) {
                    html += `
                        <div style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); border: 2px solid #ef4444; color: white; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 700; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);">
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <i class="fas fa-info-circle" style="color: white; font-size: 18px; margin-top: 2px;"></i>
                                <div>
                                    <strong style="display: block; margin-bottom: 6px; color: white; font-size: 13px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 800;">📋 INFO COMMANDE</strong>
                                    <span style="color: white; font-weight: 600;">${order.notes}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Fidélité
                if (order.loyalty_reward_id && order.reward_name) {
                    html += `
                        <div style="background: rgba(245,158,11,0.15); border: 1px solid #f59e0b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
                            <i class="fas fa-gift" style="color: #f59e0b;"></i>
                            <strong style="color: #f59e0b;">RÉCOMPENSE FIDÉLITÉ:</strong>
                            <span style="color: white; margin-left: 8px;">${order.reward_name}</span>
                        </div>
                    `;
                }

                // Détail du total (si frais de livraison)
                const deliveryFee = parseFloat(order.delivery_fee || 0);
                const subtotal = parseFloat(order.subtotal || order.total);

                if (deliveryFee > 0) {
                    html += `
                        <div style="background: #1e293b; padding: 16px; border-radius: 12px; margin-bottom: 12px; border: 1px solid #374151;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #9ca3af; font-size: 14px;">
                                <span><i class="fas fa-receipt" style="font-size: 12px;"></i> Sous-total:</span>
                                <span style="color: white; font-weight: 600;">${subtotal.toLocaleString('fr-FR')} DA</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: #f59e0b; font-size: 14px; font-weight: 600;">
                                <span><i class="fas fa-motorcycle"></i> Frais de livraison:</span>
                                <span>+${deliveryFee.toLocaleString('fr-FR')} DA</span>
                            </div>
                            <div style="border-top: 2px dashed #374151; margin: 12px 0; padding-top: 12px; display: flex; justify-content: space-between; font-size: 18px; font-weight: 800;">
                                <span style="color: white;"><i class="fas fa-coins"></i> Total:</span>
                                <span style="color: ${primaryColor};">${order.total.toLocaleString('fr-FR')} DA</span>
                            </div>
                        </div>
                    `;
                } else {
                    // Total simple sans frais
                    html += `
                        <div style="text-align: center; padding: 20px; background: ${primaryColor}22; border: 2px solid ${primaryColor}; border-radius: 12px; margin-bottom: 20px;">
                            <div style="font-size: 14px; color: #9ca3af; margin-bottom: 8px;"><i class="fas fa-hand-holding-usd"></i> Total</div>
                            <div style="font-size: 32px; font-weight: 800; color: ${primaryColor};">
                                <i class="fas fa-coins" style="font-size: 28px;"></i> ${order.total.toLocaleString('fr-FR')} DA
                            </div>
                        </div>
                    `;
                }

                // Actions
                html += `<div style="display: grid; grid-template-columns: 1fr auto; gap: 12px;">`;

                if (!isCompleted) {
                    html += `
                        <form method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="order_id" value="${order.id}">
                            <button type="submit" name="new_status" value="completed" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                                <i class="fas fa-check-circle"></i> Marquer terminée
                            </button>
                        </form>
                    `;
                } else {
                    html += `
                        <div style="padding: 10px 16px; background: linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(5,150,105,0.1) 100%); border: 2px solid rgba(16,185,129,0.3); border-radius: 12px; color: #10b981; font-weight: 600; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px;">
                            <i class="fas fa-check-circle"></i> Terminée
                        </div>
                    `;
                }

                // Bouton "Envoyer au livreur" (seulement pour livraisons)
                const isDelivery = order.notes && order.notes.includes('LIVRAISON');
                if (isDelivery) {
                    html += `
                        <button onclick="openSendToDeliveryModal('${order.id}')" style="padding: 14px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-motorcycle"></i> Envoyer au livreur
                        </button>
                    `;
                }

                html += `
                    <a href="https://wa.me/${order.customer_phone.replace(/[^0-9]/g, '')}?text=${encodeURIComponent('Salam alaykoum c\'est le Marvellous 🧇 votre commande #' + order.id + ' est prête vous pouvez venir la récupérer marhabaa 🌟')}" target="_blank" style="width: 60px; height: 60px; background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); font-size: 24px;">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
                `;

                document.getElementById('order-details-content').innerHTML = html;
                document.getElementById('order-details-modal').style.display = 'block';
                document.body.style.overflow = 'hidden';
            }

            function closeOrderDetails() {
                document.getElementById('order-details-modal').style.display = 'none';
                document.body.style.overflow = 'auto';
            }

            // Fermer avec Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeOrderDetails();
            });

            // ========================================
            // GESTION ENVOI AU LIVREUR
            // ========================================
            let currentOrderIdForDelivery = null;
            let livreurs = [];

            // Charger les livreurs
            async function loadLivreurs() {
                try {
                    const res = await fetch('api/livreurs.php?action=list');
                    const data = await res.json();
                    if (data.success && data.livreurs) {
                        livreurs = Object.values(data.livreurs).filter(l => l.actif);
                    }
                } catch (err) {
                    console.error('Erreur chargement livreurs:', err);
                }
            }

            // Ouvrir modal sélection livreur
            function openSendToDeliveryModal(orderId) {
                currentOrderIdForDelivery = orderId;
                const modal = document.getElementById('delivery-modal');
                if (!modal) return;

                const tbody = document.getElementById('livreurs-list');
                if (livreurs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:30px;color:#9ca3af">Aucun livreur actif. <a href="livreurs-manager.php" style="color:#60a5fa">Ajouter un livreur</a></td></tr>';
                } else {
                    tbody.innerHTML = livreurs.map(l => `
                        <tr style="cursor:pointer" onclick="sendToDelivery('${orderId}', '${l.id}')">
                            <td style="padding:12px;border-bottom:1px solid #374151">
                                <i class="fas fa-user" style="color:#f59e0b"></i> <strong>${escapeHtml(l.prenom)}</strong>
                            </td>
                            <td style="padding:12px;border-bottom:1px solid #374151;color:#9ca3af">
                                <i class="fab fa-whatsapp"></i> ${escapeHtml(l.whatsapp)}
                            </td>
                            <td style="padding:12px;border-bottom:1px solid #374151;text-align:right">
                                <i class="fas fa-chevron-right" style="color:#60a5fa"></i>
                            </td>
                        </tr>
                    `).join('');
                }

                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }

            function closeDeliveryModal() {
                document.getElementById('delivery-modal').style.display = 'none';
                document.body.style.overflow = 'auto';
                currentOrderIdForDelivery = null;
            }

            // Envoyer la commande au livreur
            async function sendToDelivery(orderId, livreurId) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'send_to_delivery');
                    formData.append('order_id', orderId);
                    formData.append('livreur_id', livreurId);
                    formData.append('csrf_token', '<?= $csrfToken ?>');

                    const res = await fetch('api/livreurs.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await res.json();

                    if (data.success && data.whatsapp_url) {
                        // Ouvrir WhatsApp
                        window.open(data.whatsapp_url, '_blank');
                        closeDeliveryModal();
                        closeOrderDetails();

                        // Toast de succès
                        const toast = document.createElement('div');
                        toast.style.cssText = 'position:fixed;top:20px;right:20px;background:#10b981;color:white;padding:16px 20px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.3);z-index:99999;animation:slideIn .3s ease';
                        toast.innerHTML = '<i class="fas fa-check-circle"></i> Envoyé au livreur !';
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 3000);
                    } else {
                        alert('Erreur: ' + (data.message || data.error || 'Impossible d\'envoyer'));
                    }
                } catch (err) {
                    console.error('Erreur envoi:', err);
                    alert('Erreur réseau: ' + err.message);
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Charger les livreurs au démarrage
            loadLivreurs();
            </script>

        </div>
        <!-- FIN SECTION ORDERS -->

        <!-- CLIENTS -->
        <div id="section-customers" class="section">
            <?php
            // Calculer les stats clients
            $totalCustomers = count($customers);
            $newCustomers = 0;
            $regularCustomers = 0;
            $vipCustomers = 0;

            foreach ($customers as $c) {
                $ordersCount = $c['orders_count'] ?? 0;
                $totalSpent = $c['total_spent'] ?? 0;

                if ($ordersCount <= 1) {
                    $newCustomers++;
                } elseif ($totalSpent >= 200 || $ordersCount >= 10) {
                    $vipCustomers++;
                } else {
                    $regularCustomers++;
                }
            }
            ?>

            <!-- Header avec boutons -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h2><i class="fas fa-users"></i> Clients (<?= $totalCustomers ?>)</h2>
                    <?php if (!empty($customers)): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #9ca3af; font-size: 14px;">
                            <input type="checkbox" id="selectAllClients" onclick="toggleAllClients(this)" style="width: 18px; height: 18px; cursor: pointer;">
                            <span>Tout sélectionner</span>
                        </label>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <?php if (!empty($customers)): ?>
                    <!-- Bouton supprimer sélection (caché par défaut) -->
                    <button id="deleteSelectedClients" onclick="deleteSelectedClients()"
                            style="display: none; padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <i class="fas fa-trash"></i> Supprimer (<span id="selectedClientsCount">0</span>)
                    </button>
                    <?php endif; ?>
                    <a href="clients.php" target="_blank" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: 2px solid #fbbf24;">
                        <i class="fas fa-address-book"></i> Fichier Clients
                    </a>
                    <a href="?export=customers" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> CSV</a>
                    <button onclick="toggleBroadcastPanel()" class="btn btn-sm btn-whatsapp"><i class="fab fa-whatsapp"></i> Diffusion</button>
                    <button onclick="openAddCustomerModal()" class="btn btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"><i class="fas fa-plus"></i> Ajouter</button>
                </div>
            </div>

            <!-- Stats Clients -->
            <div class="stats" style="margin-bottom: 20px;">
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div style="color: #3b82f6;"><i class="fas fa-seedling"></i></div>
                    <div class="stat-number" style="color: #3b82f6;"><?= $newCustomers ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">Nouveaux</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #10b981;">
                    <div style="color: #10b981;"><i class="fas fa-star"></i></div>
                    <div class="stat-number" style="color: #10b981;"><?= $regularCustomers ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">Réguliers</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div style="color: #f59e0b;"><i class="fas fa-crown"></i></div>
                    <div class="stat-number" style="color: #f59e0b;"><?= $vipCustomers ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">VIP</div>
                </div>
            </div>

            <!-- Filtres rapides -->
            <div class="customer-filters-wrap" style="margin-bottom: 15px;">
                <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                    <button onclick="filterCustomers('all')" class="btn btn-sm customer-filter active" data-filter="all" style="white-space: nowrap;"><i class="fas fa-users"></i> Tous</button>
                    <button onclick="filterCustomers('vip')" class="btn btn-sm customer-filter" data-filter="vip" style="background: rgba(245,158,11,.2); border-color: #f59e0b; white-space: nowrap;"><i class="fas fa-crown"></i> VIP</button>
                    <button onclick="filterCustomers('regular')" class="btn btn-sm customer-filter" data-filter="regular" style="background: rgba(16,185,129,.2); border-color: #10b981; white-space: nowrap;"><i class="fas fa-star"></i> Réguliers</button>
                    <button onclick="filterCustomers('new')" class="btn btn-sm customer-filter" data-filter="new" style="background: rgba(59,130,246,.2); border-color: #3b82f6; white-space: nowrap;"><i class="fas fa-seedling"></i> Nouveaux</button>
                </div>
            </div>

            <!-- Panel WhatsApp groupé (Diffusion) -->
            <div class="card" id="whatsapp-panel" style="display: none; margin-bottom: 15px; border: 2px solid #25D366;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h4 style="color: #25D366; margin: 0;"><i class="fab fa-whatsapp"></i> Diffusion WhatsApp</h4>
                    <button onclick="toggleBroadcastPanel()" class="btn btn-sm btn-gray"><i class="fas fa-times"></i></button>
                </div>
                <div class="form-group">
                    <label>Message promotionnel</label>
                    <textarea id="wa-message" rows="4" placeholder="Ex: Bonjour ! Profitez de -20% sur tous les burgers ce weekend avec le code BURGER20 ! À très vite chez nous !"></textarea>
                </div>
                <div style="background: #1e293b; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                        <i class="fas fa-info-circle"></i> Cochez les clients ci-dessous, puis cliquez sur "Envoyer". WhatsApp s'ouvrira pour chaque client sélectionné.
                    </p>
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button onclick="selectAllCustomers()" class="btn btn-sm btn-gray"><i class="fas fa-check-double"></i> Tout sélectionner</button>
                    <button onclick="deselectAllCustomers()" class="btn btn-sm btn-gray"><i class="fas fa-times"></i> Tout désélectionner</button>
                    <button onclick="openWhatsAppLinks()" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Envoyer (<span id="selected-count">0</span> sélectionnés)</button>
                </div>
            </div>

            <!-- Barre de recherche -->
            <div style="margin-bottom: 15px;">
                <input type="text" id="customerSearch" placeholder="🔍 Rechercher par nom, téléphone ou code fidélité..."
                       onkeyup="searchCustomers()"
                       autocomplete="off"
                       style="width: 100%; padding: 12px 16px; background: #1e293b; border: 1px solid #374151; border-radius: 10px; color: white; font-size: 14px;">
            </div>

            <!-- Tableau des clients -->
            <div class="card" style="padding: 0; overflow: hidden;">
                <?php if (empty($customers)): ?>
                    <div style="text-align: center; padding: 60px; color: #666;">
                        <div style="font-size: 4em; margin-bottom: 20px;"><i class="fas fa-users" style="color: #555;"></i></div>
                        <p style="font-size: 1.2em; color: #9ca3af;">Aucun client enregistré</p>
                        <p style="color: #6b7280;">Les clients apparaîtront ici après leur première commande</p>
                    </div>
                <?php else: ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #1e293b; text-align: left;">
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af; white-space: nowrap;">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCustomers(this)" style="width: 16px; height: 16px; display: none;" class="broadcast-cb">
                                </th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af;">Client</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af;">Téléphone</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af;">Code Fidélité</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af; text-align: center;">Cmd</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af; text-align: center;">Dépensé</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af; text-align: center;">Points</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af;">Statut</th>
                                <th style="padding: 12px 15px; font-weight: 600; color: #9ca3af; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <?php foreach ($customers as $customer):
                                $ordersCount = $customer['orders_count'] ?? 0;
                                $totalSpent = $customer['total_spent'] ?? 0;
                                $loyaltyPoints = $customer['loyalty_points'] ?? 0;

                                // Déterminer la catégorie
                                if ($ordersCount <= 1) {
                                    $category = 'new';
                                    $badge = '<span style="background: #3b82f6; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px; white-space: nowrap;"><i class="fas fa-seedling"></i> Nouveau</span>';
                                    $avatarBg = '#3b82f6';
                                } elseif ($totalSpent >= 200 || $ordersCount >= 10) {
                                    $category = 'vip';
                                    $badge = '<span style="background: #f59e0b; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px; white-space: nowrap;"><i class="fas fa-crown"></i> VIP</span>';
                                    $avatarBg = '#f59e0b';
                                } else {
                                    $category = 'regular';
                                    $badge = '<span style="background: #10b981; color: white; padding: 3px 8px; border-radius: 10px; font-size: 10px; white-space: nowrap;"><i class="fas fa-star"></i> Régulier</span>';
                                    $avatarBg = '#10b981';
                                }
                            ?>
                            <tr class="customer-row" data-category="<?= $category ?>" data-search="<?= strtolower(($customer['name'] ?? '') . ' ' . ($customer['phone'] ?? '') . ' ' . ($customer['loyalty_code'] ?? '')) ?>" style="border-bottom: 1px solid #2d3748; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='transparent'">
                                <td style="padding: 10px 15px;">
                                    <!-- Checkbox pour suppression -->
                                    <input type="checkbox" class="client-checkbox" data-customer-id="<?= $customer['id'] ?>" onclick="updateClientsSelection();" style="width: 18px; height: 18px; cursor: pointer;">
                                    <!-- Checkbox pour broadcast (caché) -->
                                    <input type="checkbox" class="customer-checkbox broadcast-cb" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" data-customer-id="<?= $customer['id'] ?>" onchange="updateSelectedCount()" style="width: 16px; height: 16px; display: none;">
                                </td>
                                <td style="padding: 10px 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: <?= $avatarBg ?>; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: bold; color: white; flex-shrink: 0;">
                                            <?= strtoupper(substr($customer['name'] ?? 'C', 0, 1)) ?>
                                        </div>
                                        <span style="font-weight: 500;"><?= htmlspecialchars($customer['name'] ?? 'Client') ?></span>
                                    </div>
                                </td>
                                <td style="padding: 10px 15px; color: #9ca3af;"><?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></td>
                                <td style="padding: 10px 15px;">
                                    <?php if (!empty($customer['loyalty_code'])): ?>
                                    <span style="color: #f59e0b; font-weight: 600; font-size: 11px;"><?= htmlspecialchars($customer['loyalty_code']) ?></span>
                                    <?php else: ?>
                                    <span style="color: #4b5563;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 15px; text-align: center; font-weight: 600; color: <?= $primaryColor ?>;"><?= $ordersCount ?></td>
                                <td style="padding: 10px 15px; text-align: center; font-weight: 600; color: #10b981;"><?= number_format($totalSpent, 0) ?> DA</td>
                                <td style="padding: 10px 15px; text-align: center; font-weight: 600; color: #f59e0b;"><?= $loyaltyPoints ?></td>
                                <td style="padding: 10px 15px;"><?= $badge ?></td>
                                <td style="padding: 10px 15px;">
                                    <div style="display: flex; gap: 6px; justify-content: center;">
                                        <?php
                                        // Formater le numéro pour WhatsApp (format international)
                                        $phone = $customer['phone'] ?? '';
                                        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
                                        // Si commence par 0, remplacer par 213 (Algérie)
                                        if (strlen($phoneClean) > 0 && $phoneClean[0] === '0') {
                                            $phoneClean = '213' . substr($phoneClean, 1);
                                        }
                                        // Si ne commence pas par un code pays, ajouter 213
                                        if (strlen($phoneClean) > 0 && !str_starts_with($phoneClean, '213') && !str_starts_with($phoneClean, '+')) {
                                            $phoneClean = '213' . $phoneClean;
                                        }
                                        ?>
                                        <a href="https://wa.me/<?= $phoneClean ?>" target="_blank" class="btn btn-sm btn-whatsapp" style="padding: 6px 10px;" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                                        <button onclick="openAddPointsModal(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'] ?? 'Client', ENT_QUOTES) ?>')" class="btn btn-sm" style="padding: 6px 10px; background: #f59e0b;" title="Ajouter points"><i class="fas fa-plus"></i></button>
                                        <button onclick="deleteCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'] ?? 'Client', ENT_QUOTES) ?>')" class="btn btn-sm" style="padding: 6px 10px; background: #dc2626;" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- FIN CLIENTS -->

<!-- Modal Ajouter Client -->
<div id="addCustomerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 20px; padding: 30px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <h3 style="margin: 0 0 20px 0; font-size: 1.5em;">➕ Nouveau Client</h3>
        <form id="addCustomerForm" onsubmit="submitAddCustomer(event)">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Nom</label>
                <input type="text" id="newCustomerName" placeholder="Nom du client" 
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333;">Téléphone *</label>
                <input type="tel" id="newCustomerPhone" placeholder="06 12 34 56 78" required
                       style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeAddCustomerModal()" 
                        style="flex: 1; padding: 12px; background: #e0e0e0; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    Annuler
                </button>
                <button type="submit" 
                        style="flex: 1; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajouter Récompense -->
<div id="addRewardModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #2a2a3e; border-radius: 20px; padding: 30px; max-width: 450px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="margin: 0 0 20px 0; font-size: 1.3em; color: #f59e0b;"><i class="fas fa-gift"></i> Nouvelle Récompense</h3>
        <form id="addRewardForm" onsubmit="submitAddReward(event)">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Nom de la récompense *</label>
                <input type="text" id="rewardName" placeholder="Ex: Menu offert" required
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Description</label>
                <textarea id="rewardDescription" placeholder="Description optionnelle..." rows="2"
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white;"></textarea>
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Points requis *</label>
                <input type="number" id="rewardPoints" value="100" min="1" required
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Type de récompense *</label>
                <select id="rewardType" onchange="toggleRewardValue()" required
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white;">
                    <option value="discount_percent">Réduction en %</option>
                    <option value="discount_amount">Réduction en DA</option>
                    <option value="free_product">Produit gratuit</option>
                    <option value="free_delivery">Livraison gratuite</option>
                </select>
            </div>
            <div style="margin-bottom: 20px;" id="rewardValueContainer">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Valeur de la réduction</label>
                <input type="number" id="rewardValue" value="10" min="1"
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeAddRewardModal()"
                        style="flex: 1; padding: 12px; background: #374151; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    Annuler
                </button>
                <button type="submit"
                        style="flex: 1; padding: 12px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-plus"></i> Créer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ajouter Points -->
<div id="addPointsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: #2a2a3e; border-radius: 20px; padding: 30px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="margin: 0 0 20px 0; font-size: 1.3em; color: #f59e0b;"><i class="fas fa-star"></i> Ajouter des Points</h3>
        <p style="color: #9ca3af; margin-bottom: 15px;">Client : <strong id="pointsCustomerName" style="color: white;"></strong></p>
        <input type="hidden" id="pointsCustomerId">
        <form id="addPointsForm" onsubmit="submitAddPoints(event)">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #d1d5db;">Nombre de points</label>
                <input type="number" id="pointsAmount" value="10" required
                       style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; font-size: 1em; box-sizing: border-box; background: #1e293b; color: white; text-align: center; font-size: 24px;">
                <p style="color: #6b7280; font-size: 12px; margin-top: 8px;"><i class="fas fa-info-circle"></i> Utilisez des valeurs négatives pour retirer des points</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" onclick="closeAddPointsModal()"
                        style="flex: 1; padding: 12px; background: #374151; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    Annuler
                </button>
                <button type="submit"
                        style="flex: 1; padding: 12px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Utiliser Points (Redeem) -->
<div id="redeemModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center; overflow-y: auto; padding: 20px;">
    <div style="background: #2a2a3e; border-radius: 20px; padding: 25px; max-width: 500px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); margin: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #10b981;"><i class="fas fa-exchange-alt"></i> Utiliser des points</h3>
            <button onclick="closeRedeemModal()" style="background: none; border: none; color: #9ca3af; font-size: 20px; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>

        <!-- Recherche client -->
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #d1d5db;">Rechercher le client</label>
            <input type="text" id="redeemSearchInput" placeholder="Nom, téléphone ou code fidélité..."
                   oninput="searchCustomerForRedeem(this.value)"
                   style="width: 100%; padding: 12px; border: 1px solid #374151; border-radius: 10px; background: #1e293b; color: white; font-size: 15px;">
            <div id="redeemSearchResults" style="margin-top: 10px; max-height: 200px; overflow-y: auto;"></div>
        </div>

        <!-- Client sélectionné -->
        <div id="redeemSelectedCustomer" style="display: none; background: #1e293b; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 2px solid #10b981;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div>
                    <div style="font-weight: bold; font-size: 16px;" id="redeemCustomerName"></div>
                    <div style="color: #9ca3af; font-size: 12px;" id="redeemCustomerPhone"></div>
                    <div style="color: #f59e0b; font-size: 11px; margin-top: 2px;" id="redeemCustomerCode"></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 28px; font-weight: bold; color: #f59e0b;" id="redeemCustomerPoints">0</div>
                    <div style="color: #9ca3af; font-size: 11px;">points</div>
                </div>
            </div>
            <input type="hidden" id="redeemCustomerId">
        </div>

        <!-- Récompenses disponibles -->
        <div id="redeemRewardsSection" style="display: none;">
            <label style="display: block; margin-bottom: 10px; font-weight: 600; color: #d1d5db;">Choisir une récompense</label>
            <div id="redeemRewardsList"></div>
        </div>

        <!-- Message si pas assez de points -->
        <div id="redeemNoRewards" style="display: none; text-align: center; padding: 20px; color: #6b7280;">
            <i class="fas fa-info-circle" style="font-size: 24px; margin-bottom: 10px;"></i>
            <p>Pas assez de points pour les récompenses disponibles</p>
        </div>
    </div>
</div>

        <!-- ARCHIVES -->
        <div id="section-archives" class="section">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <h2><i class="fas fa-archive" style="color: #8b5cf6;"></i> Archives</h2>
                    <?php if (!empty($archivedOrders)): ?>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #9ca3af; font-size: 14px;">
                            <input type="checkbox" id="selectAllArchives" onclick="toggleAllArchives(this)" style="width: 18px; height: 18px; cursor: pointer;">
                            <span>Tout sélectionner</span>
                        </label>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <?php if (!empty($archivedOrders)): ?>
                    <!-- Bouton supprimer sélection (caché par défaut) -->
                    <button id="deleteSelectedArchives" onclick="deleteSelectedArchives()"
                            style="display: none; padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;"
                            onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <i class="fas fa-trash"></i> Supprimer (<span id="selectedArchivesCount">0</span>)
                    </button>
                    <?php endif; ?>
                    <select id="archiveMonthFilter" class="select" style="width: auto; padding: 8px 12px; font-size: 13px;">
                        <option value="">Tous les mois</option>
                        <?php
                        // Générer les 12 derniers mois
                        for ($i = 0; $i < 12; $i++) {
                            $monthDate = strtotime("-$i months");
                            $monthVal = date('Y-m', $monthDate);
                            $monthLabel = ucfirst(strftime('%B %Y', $monthDate));
                            echo "<option value=\"$monthVal\">$monthLabel</option>";
                        }
                        ?>
                    </select>
                    <button onclick="filterArchivesByMonth()" class="btn btn-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);"><i class="fas fa-filter"></i> Filtrer</button>
                    <button onclick="exportArchives()" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> Export CSV</button>
                </div>
            </div>

            <?php if (empty($archivedOrders)): ?>
                <div class="card" style="text-align: center; padding: 60px;">
                    <i class="fas fa-archive" style="font-size: 48px; color: #555; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af; font-size: 16px;">Aucune commande archivée</p>
                    <p style="color: #6b7280; font-size: 13px;">Les commandes terminées seront archivées ici</p>
                </div>
            <?php else: ?>
                <?php
                // Calculer les stats des archives
                $archiveTotal = array_sum(array_column($archivedOrders, 'total'));
                $archiveCount = count($archivedOrders);

                // Grouper par date
                $groupedByDate = [];
                foreach (array_reverse($archivedOrders) as $order) {
                    $date = date('Y-m-d', strtotime($order['created_at'] ?? 'now'));
                    if (!isset($groupedByDate[$date])) {
                        $groupedByDate[$date] = [];
                    }
                    $groupedByDate[$date][] = $order;
                }
                // Limiter à 30 jours
                $groupedByDate = array_slice($groupedByDate, 0, 30, true);
                ?>

                <!-- Stats archives -->
                <div class="stats" style="margin-bottom: 20px;">
                    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                        <div style="color: #8b5cf6;"><i class="fas fa-receipt"></i></div>
                        <div class="stat-number" style="color: #8b5cf6;"><?php echo $archiveCount; ?></div>
                        <div style="color: #9ca3af; font-size: 11px;">Commandes</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid <?php echo $primaryColor; ?>;">
                        <div style="color: <?php echo $primaryColor; ?>;"><i class="fas fa-coins"></i></div>
                        <div class="stat-number" style="color: <?php echo $primaryColor; ?>;"><?php echo number_format($archiveTotal, 0); ?> DA</div>
                        <div style="color: #9ca3af; font-size: 11px;">CA Total</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #10b981;">
                        <div style="color: #10b981;"><i class="fas fa-shopping-basket"></i></div>
                        <div class="stat-number" style="color: #10b981;"><?php echo $archiveCount > 0 ? number_format($archiveTotal / $archiveCount, 0) : 0; ?> DA</div>
                        <div style="color: #9ca3af; font-size: 11px;">Panier moyen</div>
                    </div>
                </div>

                <!-- Liste groupée par date -->
                <?php foreach ($groupedByDate as $date => $dateOrders):
                    $dateLabel = date('d/m/Y', strtotime($date));
                    $dayName = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][date('w', strtotime($date))];
                    $dayTotal = array_sum(array_column($dateOrders, 'total'));

                    // Date relative
                    $daysAgo = floor((time() - strtotime($date)) / 86400);
                    if ($daysAgo === 0) {
                        $dateLabel = "Aujourd'hui";
                    } elseif ($daysAgo === 1) {
                        $dateLabel = "Hier";
                    } else {
                        $dateLabel = $dayName . ' ' . date('d/m', strtotime($date));
                    }
                ?>
                <div class="card archive-date-card" data-date="<?php echo $date; ?>" data-month="<?php echo date('Y-m', strtotime($date)); ?>" style="margin-bottom: 15px; border-left: 4px solid #8b5cf6;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #374151; margin-bottom: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; background: #8b5cf622; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calendar-day" style="color: #8b5cf6;"></i>
                            </div>
                            <div>
                                <strong style="font-size: 14px;"><?php echo $dateLabel; ?></strong>
                                <div style="color: #9ca3af; font-size: 11px;"><?php echo count($dateOrders); ?> commande<?php echo count($dateOrders) > 1 ? 's' : ''; ?></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: bold; color: <?php echo $primaryColor; ?>;"><?php echo number_format($dayTotal, 0); ?> DA</div>
                        </div>
                    </div>

                    <div style="background: #1e293b; border-radius: 8px; overflow: hidden;">
                    <?php foreach (array_slice($dateOrders, 0, 10) as $idx => $order): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; <?php echo $idx > 0 ? 'border-top: 1px solid #374151;' : ''; ?>">
                        <div style="flex: 1; min-width: 0; display: flex; align-items: center; gap: 10px;">
                            <!-- Checkbox pour sélection -->
                            <input type="checkbox" class="archive-checkbox" data-order-id="<?php echo htmlspecialchars($order['id'], ENT_QUOTES); ?>" onclick="updateArchivesSelection();" style="width: 18px; height: 18px; cursor: pointer; flex-shrink: 0;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, <?php echo $primaryColor; ?>, #d97706); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: white;">
                                <?php echo strtoupper(substr($order['customer_name'] ?? 'C', 0, 1)); ?>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <strong style="font-size: 13px;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Client'); ?></strong>
                                    <span style="color: #6b7280; font-size: 10px;">#<?php echo htmlspecialchars(substr($order['id'], -6)); ?></span>
                                </div>
                                <div style="color: #6b7280; font-size: 11px;">
                                    <i class="fas fa-clock" style="font-size: 9px;"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                    <?php if (!empty($order['items'])): ?>
                                    • <?php echo count($order['items']); ?> article<?php echo count($order['items']) > 1 ? 's' : ''; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: bold; color: <?php echo $primaryColor; ?>; font-size: 14px;"><?php echo number_format($order['total'] ?? 0, 0); ?> DA</div>
                            <span style="background: #10b98122; color: #10b981; padding: 2px 8px; border-radius: 10px; font-size: 9px;"><i class="fas fa-check"></i> Terminée</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>

                    <?php if (count($dateOrders) > 10): ?>
                    <div style="text-align: center; padding-top: 12px; color: #6b7280; font-size: 12px;">
                        <i class="fas fa-ellipsis-h"></i> et <?php echo count($dateOrders) - 10; ?> autre<?php echo (count($dateOrders) - 10) > 1 ? 's' : ''; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- RÉGLAGES -->
        <div id="section-settings" class="section">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-cog" style="color: #6b7280;"></i> Réglages</h2>
            </div>

            <?php if (($_SESSION['admin_role'] ?? 'staff') === 'owner'): ?>
            <!-- Gestion des Admins (seulement pour owner) -->
            <div class="card" style="border-left: 4px solid #dc2626; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="margin-bottom: 8px;"><i class="fas fa-user-shield" style="color: #dc2626;"></i> Utilisateurs Administrateurs</h3>
                        <p style="color: #9ca3af; font-size: 12px;">Gérer les comptes admin (créer, modifier, supprimer)</p>
                    </div>
                    <a href="admin-users.php" class="btn" style="background: #dc2626; color: white; text-decoration: none;">
                        <i class="fas fa-users-cog"></i> Gérer les admins
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gestion de l'Imprimante -->
            <div class="card" style="border-left: 4px solid #10b981; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><i class="fas fa-print" style="color: #10b981;"></i> Imprimante de Tickets</h3>
                    <div class="printer-help-tooltip" style="position: relative; display: inline-block;">
                        <i class="fas fa-question-circle" style="color: #6b7280; font-size: 18px; cursor: help;"></i>
                        <!-- Overlay sombre -->
                        <div class="printer-help-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 99998;"></div>
                        <!-- Tooltip modal -->
                        <div class="printer-help-content" style="display: none; position: fixed; left: 50%; top: 50%; transform: translate(-50%, -50%); background: #1e293b; border: 2px solid #10b981; border-radius: 10px; padding: 12px; width: 400px; max-width: 90vw; max-height: 30vh; overflow-y: auto; z-index: 99999; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                            <div style="font-size: 13px; font-weight: 700; color: #10b981; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-info-circle"></i> Utiliser l'impression
                            </div>

                            <div style="margin-bottom: 8px;">
                                <div style="font-weight: 600; color: white; margin-bottom: 3px; font-size: 11px;">📱 PRÉREQUIS</div>
                                <ul style="margin: 0; padding-left: 18px; font-size: 10px; color: #d1d5db; line-height: 1.4;">
                                    <li>Tablette/PC <strong>Android</strong> (Windows/Mac)</li>
                                    <li>Navigateur <strong>Chrome</strong> ou <strong>Edge</strong></li>
                                    <li>Imprimante Bluetooth <strong>ESC/POS</strong></li>
                                </ul>
                            </div>

                            <div style="margin-bottom: 8px;">
                                <div style="font-weight: 600; color: white; margin-bottom: 3px; font-size: 11px;">🔗 CONNEXION</div>
                                <ol style="margin: 0; padding-left: 18px; font-size: 10px; color: #d1d5db; line-height: 1.4;">
                                    <li>Allumez l'imprimante Bluetooth</li>
                                    <li>Activez Bluetooth sur la tablette</li>
                                    <li>Cliquez "Connecter une imprimante"</li>
                                    <li>Sélectionnez votre imprimante</li>
                                    <li>Testez avec "Imprimer un test"</li>
                                </ol>
                            </div>

                            <div style="margin-bottom: 8px;">
                                <div style="font-weight: 600; color: white; margin-bottom: 3px; font-size: 11px;">🖨️ IMPRESSION</div>
                                <ul style="margin: 0; padding-left: 18px; font-size: 10px; color: #d1d5db; line-height: 1.4;">
                                    <li>Bouton "Imprimer" apparaît sur chaque commande</li>
                                    <li>Clic = impression instantanée</li>
                                    <li>Ticket complet automatique</li>
                                </ul>
                            </div>

                            <div style="padding: 6px; background: rgba(239, 68, 68, 0.15); border-left: 2px solid #ef4444; border-radius: 4px; font-size: 9px; color: #fca5a5; line-height: 1.3;">
                                <strong>⚠️</strong> iOS non supporté. Utilisez Android.
                            </div>
                        </div>
                    </div>
                </div>
                <p style="color: #9ca3af; font-size: 12px; margin-bottom: 15px;">
                    Connectez une imprimante thermique Bluetooth (ESC/POS) pour imprimer les tickets de commandes
                </p>

                <div id="printer-status" style="padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-circle" id="printer-status-icon" style="color: #dc2626; font-size: 10px;"></i>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 4px;" id="printer-status-text">Aucune imprimante connectée</div>
                            <div style="font-size: 11px; color: #9ca3af;" id="printer-name"></div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button id="btn-connect-printer" class="btn" style="background: #10b981; color: white;">
                        <i class="fas fa-bluetooth"></i> Connecter une imprimante
                    </button>
                    <button id="btn-disconnect-printer" class="btn" style="background: #dc2626; color: white; display: none;">
                        <i class="fas fa-times"></i> Déconnecter
                    </button>
                    <button id="btn-test-print" class="btn" style="background: #6b7280; color: white; display: none;">
                        <i class="fas fa-file-invoice"></i> Imprimer un test
                    </button>
                </div>
            </div>

            <!-- Horaires -->
            <div class="card" style="border-left: 4px solid #3b82f6;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-clock" style="color: #3b82f6;"></i> Horaires d'ouverture</h3>
                <p style="color: #9ca3af; font-size: 12px; margin-bottom: 15px;">Ajoutez plusieurs créneaux par jour (midi + soir)</p>
                <form method="POST" id="hours-form">
                    <input type="hidden" name="action" value="save_settings">
                    <?php
                    $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    $hours = $restaurantSettings['openingHours'] ?? [];
                    foreach ($days as $i => $day):
                        $dayHours = $hours[$i] ?? ['opens' => '18:30', 'closes' => '23:30'];
                        // Support ancien format (single slot) et nouveau format (multiple slots)
                        $slots = isset($dayHours['slots']) ? $dayHours['slots'] : [['opens' => $dayHours['opens'] ?? '18:30', 'closes' => $dayHours['closes'] ?? '23:30']];
                    ?>
                    <div class="hours-day" data-day="<?php echo $i; ?>" style="margin-bottom: 12px; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <strong style="font-size: 14px;"><?php echo $day; ?></strong>
                            <button type="button" class="btn btn-sm btn-gray" onclick="addSlot(<?php echo $i; ?>)" style="padding: 4px 10px; font-size: 11px;">
                                <i class="fas fa-plus"></i> Créneau
                            </button>
                        </div>
                        <div class="slots-container" id="slots-<?php echo $i; ?>">
                            <?php foreach ($slots as $s => $slot): ?>
                            <div class="slot-row" style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                                <input type="time" name="hours[<?php echo $i; ?>][slots][<?php echo $s; ?>][opens]" value="<?php echo $slot['opens']; ?>" style="flex: 1;">
                                <span style="color: #6b7280;">→</span>
                                <input type="time" name="hours[<?php echo $i; ?>][slots][<?php echo $s; ?>][closes]" value="<?php echo $slot['closes']; ?>" style="flex: 1;">
                                <?php if ($s > 0): ?>
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 4px 8px;" onclick="this.parentNode.remove()">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn" style="margin-top: 15px;"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>

            <!-- Livraison & Plateformes -->
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-truck" style="color: #f59e0b;"></i> Livraison & Plateformes</h3>

                <!-- Toggle Livraison -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px; margin-bottom: 15px;">
                    <div>
                        <strong style="font-size: 14px;">Livraison activée</strong>
                        <p style="color: #9ca3af; font-size: 12px; margin: 4px 0 0;">Afficher les options de livraison sur le site</p>
                    </div>
                    <div id="delivery-toggle" onclick="toggleDelivery()" style="cursor: pointer;">
                        <div id="delivery-toggle-bg" style="width: 50px; height: 26px; border-radius: 13px; background: <?php echo ($restaurantSettings['delivery']['enabled'] ?? false) ? '#10b981' : '#4b5563'; ?>; position: relative; transition: background 0.3s;">
                            <div id="delivery-toggle-knob" style="width: 22px; height: 22px; border-radius: 50%; background: white; position: absolute; top: 2px; left: <?php echo ($restaurantSettings['delivery']['enabled'] ?? false) ? '26px' : '2px'; ?>; transition: left 0.3s;"></div>
                        </div>
                    </div>
                </div>

                <!-- Plateformes de livraison -->
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong style="font-size: 14px;">Plateformes de livraison</strong>
                        <button type="button" class="btn btn-sm btn-gray" onclick="openAddPlatformModal()"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                    <div id="platforms-list">
                        <?php foreach (($restaurantSettings['platforms'] ?? []) as $platform): ?>
                        <div class="platform-row" data-id="<?php echo htmlspecialchars($platform['id']); ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(0,0,0,0.2); border-radius: 10px; margin-bottom: 8px;">
                            <div style="flex: 1;">
                                <strong style="font-size: 13px;"><?php echo htmlspecialchars($platform['name']); ?></strong>
                                <a href="<?php echo htmlspecialchars($platform['url']); ?>" target="_blank" style="display: block; color: #60a5fa; font-size: 11px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;"><?php echo htmlspecialchars($platform['url']); ?></a>
                            </div>
                            <div onclick="togglePlatform('<?php echo htmlspecialchars($platform['id']); ?>')" style="cursor: pointer;">
                                <div class="platform-toggle" style="width: 40px; height: 22px; border-radius: 11px; background: <?php echo ($platform['enabled'] ?? true) ? '#10b981' : '#4b5563'; ?>; position: relative; transition: background 0.3s;">
                                    <div style="width: 18px; height: 18px; border-radius: 50%; background: white; position: absolute; top: 2px; left: <?php echo ($platform['enabled'] ?? true) ? '20px' : '2px'; ?>; transition: left 0.3s;"></div>
                                </div>
                            </div>
                            <button onclick="deletePlatform('<?php echo htmlspecialchars($platform['id']); ?>')" class="btn btn-sm" style="background: #dc2626; padding: 6px 10px;"><i class="fas fa-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Modal Ajouter Plateforme -->
            <div id="add-platform-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: #1f2937; border-radius: 16px; padding: 24px; max-width: 400px; width: 90%;">
                    <h3 style="margin: 0 0 20px;"><i class="fas fa-plus"></i> Ajouter une plateforme</h3>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Nom de la plateforme</label>
                        <input type="text" id="new-platform-name" placeholder="Ex: Just Eat" style="width: 100%;">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label>URL de votre page</label>
                        <input type="url" id="new-platform-url" placeholder="https://..." style="width: 100%;">
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button onclick="closeAddPlatformModal()" class="btn btn-gray">Annuler</button>
                        <button onclick="addPlatform()" class="btn"><i class="fas fa-plus"></i> Ajouter</button>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card" style="border-left: 4px solid #10b981;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-phone" style="color: #10b981;"></i> Contact & Réseaux</h3>
                <form method="POST" id="contact-form">
                    <input type="hidden" name="action" value="save_settings">

                    <!-- Téléphones -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Téléphones</label>
                        <div id="phones-container">
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($restaurantSettings['contact']['phone'] ?? ''); ?>" placeholder="Téléphone principal" style="flex: 1;">
                            </div>
                            <?php
                            $extraPhones = $restaurantSettings['contact']['extra_phones'] ?? [];
                            foreach ($extraPhones as $i => $ep): ?>
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" name="extra_phones[]" value="<?php echo htmlspecialchars($ep); ?>" placeholder="Numéro supplémentaire" style="flex: 1;">
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-gray" onclick="addPhone()" style="margin-top: 8px;"><i class="fas fa-plus"></i> Ajouter un numéro</button>
                    </div>

                    <div class="form-group">
                        <label>WhatsApp (numéro sans +)</label>
                        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($restaurantSettings['contact']['whatsappOrdersNumber'] ?? ''); ?>">
                    </div>

                    <!-- Réseaux sociaux -->
                    <div style="margin-bottom: 20px; margin-top: 20px; border-top: 1px solid #374151; padding-top: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600;">Réseaux sociaux</label>
                        <div id="socials-container">
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                                    <option value="instagram" selected>Instagram</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="snapchat">Snapchat</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="other">Autre</option>
                                </select>
                                <input type="text" name="social_value[]" value="<?php echo htmlspecialchars($restaurantSettings['social']['instagram'] ?? ''); ?>" placeholder="Lien ou @pseudo" style="flex: 1;">
                            </div>
                            <?php if (!empty($restaurantSettings['social']['facebook'])): ?>
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                                    <option value="instagram">Instagram</option>
                                    <option value="facebook" selected>Facebook</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="snapchat">Snapchat</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="other">Autre</option>
                                </select>
                                <input type="text" name="social_value[]" value="<?php echo htmlspecialchars($restaurantSettings['social']['facebook']); ?>" placeholder="Lien ou @pseudo" style="flex: 1;">
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($restaurantSettings['social']['tiktok'])): ?>
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                                    <option value="instagram">Instagram</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="tiktok" selected>TikTok</option>
                                    <option value="snapchat">Snapchat</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="other">Autre</option>
                                </select>
                                <input type="text" name="social_value[]" value="<?php echo htmlspecialchars($restaurantSettings['social']['tiktok']); ?>" placeholder="Lien ou @pseudo" style="flex: 1;">
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($restaurantSettings['social']['snapchat'])): ?>
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                                    <option value="instagram">Instagram</option>
                                    <option value="facebook">Facebook</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="snapchat" selected>Snapchat</option>
                                    <option value="twitter">Twitter/X</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="other">Autre</option>
                                </select>
                                <input type="text" name="social_value[]" value="<?php echo htmlspecialchars($restaurantSettings['social']['snapchat']); ?>" placeholder="Lien ou @pseudo" style="flex: 1;">
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endif; ?>
                            <?php
                            $extraSocials = $restaurantSettings['social']['extra'] ?? [];
                            foreach ($extraSocials as $es): ?>
                            <div class="form-group dynamic-row" style="display: flex; gap: 8px; align-items: center;">
                                <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                                    <option value="instagram" <?php echo ($es['type'] ?? '') === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                                    <option value="facebook" <?php echo ($es['type'] ?? '') === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                                    <option value="tiktok" <?php echo ($es['type'] ?? '') === 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
                                    <option value="snapchat" <?php echo ($es['type'] ?? '') === 'snapchat' ? 'selected' : ''; ?>>Snapchat</option>
                                    <option value="twitter" <?php echo ($es['type'] ?? '') === 'twitter' ? 'selected' : ''; ?>>Twitter/X</option>
                                    <option value="youtube" <?php echo ($es['type'] ?? '') === 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                                    <option value="other" <?php echo ($es['type'] ?? '') === 'other' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                                <input type="text" name="social_value[]" value="<?php echo htmlspecialchars($es['value'] ?? ''); ?>" placeholder="Lien ou @pseudo" style="flex: 1;">
                                <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-gray" onclick="addSocial()" style="margin-top: 8px;"><i class="fas fa-plus"></i> Ajouter un réseau</button>
                    </div>

                    <button type="submit" class="btn"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>

            <script>
            function addPhone() {
                const container = document.getElementById('phones-container');
                const div = document.createElement('div');
                div.className = 'form-group dynamic-row';
                div.style.cssText = 'display: flex; gap: 8px; align-items: center;';
                div.innerHTML = `
                    <input type="text" name="extra_phones[]" placeholder="Numéro supplémentaire" style="flex: 1;">
                    <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(div);
            }

            function addSlot(dayIndex) {
                const container = document.getElementById('slots-' + dayIndex);
                const slotCount = container.querySelectorAll('.slot-row').length;
                const div = document.createElement('div');
                div.className = 'slot-row';
                div.style.cssText = 'display: flex; gap: 8px; align-items: center; margin-bottom: 6px;';
                div.innerHTML = `
                    <input type="time" name="hours[${dayIndex}][slots][${slotCount}][opens]" value="12:00" style="flex: 1;">
                    <span style="color: #6b7280;">→</span>
                    <input type="time" name="hours[${dayIndex}][slots][${slotCount}][closes]" value="14:00" style="flex: 1;">
                    <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 4px 8px;" onclick="this.parentNode.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(div);
            }

            function addSocial() {
                const container = document.getElementById('socials-container');
                const div = document.createElement('div');
                div.className = 'form-group dynamic-row';
                div.style.cssText = 'display: flex; gap: 8px; align-items: center;';
                div.innerHTML = `
                    <select name="social_type[]" style="width: 130px; padding: 10px; background: #1a1a2e; border: 1px solid #374151; border-radius: 8px; color: #fff;">
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="tiktok">TikTok</option>
                        <option value="snapchat">Snapchat</option>
                        <option value="twitter">Twitter/X</option>
                        <option value="youtube">YouTube</option>
                        <option value="other">Autre</option>
                    </select>
                    <input type="text" name="social_value[]" placeholder="Lien ou @pseudo" style="flex: 1;">
                    <button type="button" class="btn btn-sm" style="background: #dc2626; padding: 8px 12px;" onclick="this.parentNode.remove()"><i class="fas fa-times"></i></button>
                `;
                container.appendChild(div);
            }
            </script>
            <!-- WhatsApp Business API -->
            <div class="card" style="border-left: 4px solid #25D366;">
                <h3 style="margin-bottom: 15px;"><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp Business API</h3>
                <p style="color: #9ca3af; font-size: 12px; margin-bottom: 15px;">
                    Pour envoyer des messages automatiques via l'API WhatsApp Business, vous devez configurer votre token d'accès.
                    <a href="https://developers.facebook.com/docs/whatsapp/business-management-api" target="_blank" style="color: #60a5fa;">En savoir plus →</a>
                </p>
                <form method="POST">
                    <input type="hidden" name="action" value="save_whatsapp_config">
                    <div class="form-group">
                        <label>Token d'accès WhatsApp Business API</label>
                        <input type="password" name="whatsapp_token" id="whatsapp_token"
                               value="<?php echo htmlspecialchars($restaurantSettings['whatsapp']['token'] ?? ''); ?>"
                               placeholder="Entrez votre token WhatsApp Business API"
                               style="font-family: monospace;">
                        <button type="button" onclick="toggleTokenVisibility()" class="btn btn-sm btn-gray" style="margin-top: 8px;">
                            <i class="fas fa-eye" id="toggle-eye"></i> Afficher/Masquer
                        </button>
                    </div>
                    <div class="form-group">
                        <label>ID du numéro de téléphone WhatsApp</label>
                        <input type="text" name="whatsapp_phone_id"
                               value="<?php echo htmlspecialchars($restaurantSettings['whatsapp']['phoneNumberId'] ?? ''); ?>"
                               placeholder="Ex: 123456789012345">
                    </div>
                    <div class="form-group">
                        <label>ID du Business Account</label>
                        <input type="text" name="whatsapp_business_id"
                               value="<?php echo htmlspecialchars($restaurantSettings['whatsapp']['businessAccountId'] ?? ''); ?>"
                               placeholder="Ex: 123456789012345">
                    </div>
                    <div style="background: #1e293b; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                        <p style="color: #f59e0b; font-size: 12px; margin: 0;">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Le token doit être gardé secret.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Enregistrer la configuration</button>
                </form>
            </div>


            <!-- FAQ -->
            <div class="card" style="border-left: 4px solid #8b5cf6;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-question-circle" style="color: #8b5cf6;"></i> FAQ</h3>
                <form method="POST" id="faq-form">
                    <input type="hidden" name="action" value="save_faq">
                    <div id="faq-items">
                    <?php foreach ($restaurantSettings['faq']['items'] ?? [] as $i => $faq): ?>
                        <div class="faq-item" style="background: #1e293b; padding: 12px; border-radius: 8px; margin-bottom: 10px;">
                            <input type="text" name="faq_q[]" value="<?php echo htmlspecialchars($faq['question']); ?>" placeholder="Question" style="width: 100%; padding: 8px; background: #2a2a3e; border: 1px solid #444; border-radius: 6px; color: white; margin-bottom: 8px;">
                            <textarea name="faq_a[]" placeholder="Réponse" style="width: 100%; padding: 8px; background: #2a2a3e; border: 1px solid #444; border-radius: 6px; color: white; min-height: 60px;"><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <button type="button" onclick="addFaqItem()" class="btn btn-sm btn-gray" style="margin-bottom: 15px;"><i class="fas fa-plus"></i> Ajouter une question</button>
                    <br>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Enregistrer FAQ</button>
                </form>
            </div>

            <!-- Produits -->
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-utensils" style="color: #f59e0b;"></i> Gestion des produits</h3>
                <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">Gérez votre menu, ajoutez des produits, modifiez les prix et les catégories.</p>
                <a href="products-manager.php" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-burger"></i> Gérer le menu</a>
            </div>

            <!-- Codes Promo -->
            <div class="card" style="border-left: 4px solid #22c55e;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-tags" style="color: #22c55e;"></i> Codes Promo</h3>
                <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">Créez et gérez vos codes de réduction pour fidéliser vos clients.</p>
                <a href="promo-manager.php" class="btn" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"><i class="fas fa-tag"></i> Gérer les codes promo</a>
            </div>

            <!-- Gestion des Livreurs -->
            <div class="card" style="border-left: 4px solid #3b82f6;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-motorcycle" style="color: #3b82f6;"></i> Gestion des livreurs</h3>
                <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">Gérez vos livreurs, ajoutez leurs contacts WhatsApp et envoyez-leur des notifications de commandes.</p>
                <a href="livreurs-manager.php" class="btn" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);"><i class="fas fa-users-cog"></i> Gérer les livreurs</a>
            </div>

            <!-- Sécurité PIN -->
            <div class="card" style="border-left: 4px solid #ef4444;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-lock" style="color: #ef4444;"></i> Code PIN (Stats & Archives)</h3>
                <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">Modifiez le code PIN pour accéder aux statistiques et archives. Nécessite l'ancien PIN.</p>
                <div id="pinChangeForm">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div>
                            <label style="color: #9ca3af; font-size: 11px; display: block; margin-bottom: 5px;">Ancien PIN</label>
                            <input type="password" id="oldPinInput" maxlength="8" placeholder="••••" style="width: 100%; padding: 10px; background: #2a2a3e; border: 1px solid #444; border-radius: 8px; color: #fff;">
                        </div>
                        <div>
                            <label style="color: #9ca3af; font-size: 11px; display: block; margin-bottom: 5px;">Nouveau PIN (4-8 chiffres)</label>
                            <input type="password" id="newPinInput" maxlength="8" placeholder="••••" style="width: 100%; padding: 10px; background: #2a2a3e; border: 1px solid #444; border-radius: 8px; color: #fff;">
                        </div>
                    </div>
                    <button type="button" onclick="changePin()" class="btn" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);"><i class="fas fa-key"></i> Changer le PIN</button>
                    <p id="pinChangeResult" style="margin-top: 10px; font-size: 12px; display: none;"></p>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div id="section-stats" class="section">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2><i class="fas fa-chart-line" style="color: #3b82f6;"></i> Statistiques</h2>
                <div style="display: flex; gap: 8px;">
                    <a href="?export=stats&period=week" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> Semaine</a>
                    <a href="?export=stats&period=month" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> Mois</a>
                </div>
            </div>

            <!-- CA Cards -->
            <div class="stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
                <div class="stat-card" style="border-left: 4px solid #10b981;">
                    <div style="color: #10b981;"><i class="fas fa-calendar-day"></i></div>
                    <div class="stat-number" style="color: #10b981;"><?php echo number_format($stats['revenue'] ?? 0, 0); ?> DA</div>
                    <div style="color: #9ca3af; font-size: 11px;">Aujourd'hui</div>
                    <div style="color: #6b7280; font-size: 10px; margin-top: 4px;"><?php echo $stats['today'] ?? 0; ?> commandes</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #3b82f6;">
                    <div style="color: #3b82f6;"><i class="fas fa-calendar-week"></i></div>
                    <div class="stat-number" style="color: #3b82f6;"><?php echo number_format($weekStats['revenue'] ?? 0, 0); ?> DA</div>
                    <div style="color: #9ca3af; font-size: 11px;">Semaine</div>
                    <div style="color: #6b7280; font-size: 10px; margin-top: 4px;"><?php echo $weekStats['orders'] ?? 0; ?> commandes</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
                    <div style="color: #8b5cf6;"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-number" style="color: #8b5cf6;"><?php echo number_format($monthStats['revenue'] ?? 0, 0); ?> DA</div>
                    <div style="color: #9ca3af; font-size: 11px;">Mois</div>
                    <div style="color: #6b7280; font-size: 10px; margin-top: 4px;"><?php echo $monthStats['orders'] ?? 0; ?> commandes</div>
                </div>
            </div>

            <!-- Panier moyen & Heure de pic -->
            <div class="stats" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
                <div class="stat-card" style="border-left: 4px solid <?php echo $primaryColor; ?>;">
                    <div style="color: <?php echo $primaryColor; ?>;"><i class="fas fa-shopping-basket"></i></div>
                    <div class="stat-number" style="color: <?php echo $primaryColor; ?>;"><?php echo number_format($monthStats['avg_order'] ?? 0, 0); ?> DA</div>
                    <div style="color: #9ca3af; font-size: 11px;">Panier moyen</div>
                </div>
                <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                    <div style="color: #f59e0b;"><i class="fas fa-fire"></i></div>
                    <div class="stat-number" style="color: #f59e0b;"><?php echo $stats['peak_hour'] ?? '--:--'; ?></div>
                    <div style="color: #9ca3af; font-size: 11px;">Heure de pic</div>
                </div>
            </div>

            <!-- Top Produits -->
            <div class="card" style="border-left: 4px solid #f59e0b;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-trophy" style="color: #f59e0b;"></i> Top 5 Produits (30j)</h3>
                <?php if (empty($topProducts)): ?>
                    <div style="text-align: center; padding: 30px; color: #6b7280;">
                        <i class="fas fa-chart-pie" style="font-size: 32px; margin-bottom: 10px; color: #444;"></i>
                        <p>Aucune donnée disponible</p>
                    </div>
                <?php else: ?>
                    <div style="background: #1e293b; border-radius: 8px; overflow: hidden;">
                    <?php foreach ($topProducts as $i => $product):
                        $medalColors = ['#f59e0b', '#9ca3af', '#cd7f32', '#374151', '#374151'];
                        $medalColor = $medalColors[$i] ?? '#374151';
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; <?php echo $i > 0 ? 'border-top: 1px solid #374151;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span style="width: 28px; height: 28px; background: <?php echo $medalColor; ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: white;"><?php echo $i + 1; ?></span>
                            <span style="font-size: 14px; font-weight: 500;"><?php echo htmlspecialchars($product['name']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <div style="color: <?php echo $primaryColor; ?>; font-weight: bold; font-size: 14px;"><?php echo $product['qty']; ?> vendus</div>
                            <div style="color: #10b981; font-size: 12px;"><?php echo number_format($product['revenue'], 0); ?> DA</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Heures de pic -->
            <div class="card" style="margin-top: 15px; border-left: 4px solid #3b82f6;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-chart-bar" style="color: #3b82f6;"></i> Répartition par heure (30j)</h3>
                <?php
                $maxCount = max(array_column($peakHours, 'count') ?: [1]);
                ?>
                <div style="background: #1e293b; padding: 15px; border-radius: 8px;">
                    <div style="display: flex; align-items: flex-end; gap: 4px; height: 100px;">
                        <?php for ($h = 11; $h <= 23; $h++):
                            $hourData = array_filter($peakHours, fn($p) => (int)$p['hour'] === $h);
                            $count = !empty($hourData) ? array_values($hourData)[0]['count'] : 0;
                            $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                            $isPeak = $count === $maxCount && $count > 0;
                        ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                            <?php if ($count > 0): ?>
                            <span style="font-size: 8px; color: <?php echo $isPeak ? '#f59e0b' : '#6b7280'; ?>; margin-bottom: 2px;"><?php echo $count; ?></span>
                            <?php endif; ?>
                            <div style="width: 100%; background: <?php echo $isPeak ? '#f59e0b' : '#3b82f6'; ?>; height: <?php echo max($height, 2); ?>px; border-radius: 4px 4px 0 0; min-height: 2px;"></div>
                            <span style="font-size: 9px; color: <?php echo $isPeak ? '#f59e0b' : '#6b7280'; ?>; margin-top: 4px; font-weight: <?php echo $isPeak ? 'bold' : 'normal'; ?>;"><?php echo $h; ?>h</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- CA 7 derniers jours -->
            <div class="card" style="margin-top: 15px; border-left: 4px solid #10b981;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-chart-area" style="color: #10b981;"></i> CA des 7 derniers jours</h3>
                <?php
                $maxRevenue = max(array_column($dailyRevenue, 'revenue') ?: [1]);
                $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                ?>
                <div style="background: #1e293b; padding: 15px; border-radius: 8px;">
                    <div style="display: flex; align-items: flex-end; gap: 8px; height: 120px;">
                        <?php foreach ($dailyRevenue as $day):
                            $height = $maxRevenue > 0 ? ($day['revenue'] / $maxRevenue) * 100 : 0;
                            $dayName = $days[date('w', strtotime($day['date']))];
                            $isToday = $day['date'] === date('Y-m-d');
                        ?>
                        <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                            <span style="font-size: 10px; color: <?php echo $isToday ? '#10b981' : '#9ca3af'; ?>; margin-bottom: 4px; font-weight: <?php echo $isToday ? 'bold' : 'normal'; ?>;"><?php echo number_format($day['revenue'], 0); ?> DA</span>
                            <div style="width: 100%; background: <?php echo $isToday ? 'linear-gradient(180deg, #10b981, #059669)' : '#374151'; ?>; height: <?php echo max($height, 4); ?>px; border-radius: 4px 4px 0 0;"></div>
                            <span style="font-size: 10px; color: <?php echo $isToday ? '#10b981' : '#6b7280'; ?>; margin-top: 4px; font-weight: <?php echo $isToday ? 'bold' : 'normal'; ?>;"><?php echo $dayName; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- FIDÉLITÉ -->
        <div id="section-loyalty" class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2><i class="fas fa-gift" style="color: #f59e0b;"></i> Programme Fidélité</h2>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button onclick="openRedeemModal()" class="btn btn-sm" style="background: #10b981;"><i class="fas fa-exchange-alt"></i> Utiliser points</button>
                    <button onclick="openAddRewardModal()" class="btn btn-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-plus"></i> Récompense</button>
                </div>
            </div>

            <!-- Configuration -->
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-cog"></i> Configuration</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="color: #9ca3af;">Programme actif :</label>
                        <label class="switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                            <input type="checkbox" id="loyaltyEnabled" <?= ($loyaltyConfig['enabled'] ?? true) ? 'checked' : '' ?> onchange="updateLoyaltyConfig()" style="opacity: 0; width: 0; height: 0;">
                            <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #374151; transition: .4s; border-radius: 26px;"></span>
                        </label>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="color: #9ca3af;">Points par 100 DA :</label>
                        <input type="number" id="pointsPerEuro" value="<?= $loyaltyConfig['points_per_euro'] ?? 1 ?>" min="1" max="100" onchange="updateLoyaltyConfig()" style="width: 70px; padding: 8px; background: #1e293b; border: 1px solid #374151; border-radius: 8px; color: white; text-align: center;">
                    </div>
                </div>
                <p style="color: #6b7280; font-size: 12px; margin-top: 10px;"><i class="fas fa-info-circle"></i> Les clients gagnent des points à chaque commande qu'ils peuvent échanger contre des récompenses.</p>
            </div>

            <!-- QR Code pour les clients -->
            <div class="card" style="margin-bottom: 20px; border: 2px dashed #f59e0b;">
                <h3 style="margin-bottom: 15px; color: #f59e0b;"><i class="fas fa-qrcode"></i> Carte de Fidélité Virtuelle</h3>
                <p style="color: #9ca3af; font-size: 13px; margin-bottom: 15px;">Les clients peuvent scanner ce QR code pour accéder à leur carte de fidélité virtuelle et voir leurs points.</p>
                <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <div id="loyalty-qrcode" style="background: white; padding: 15px; border-radius: 12px; display: inline-block;">
                        <?php $loyaltyUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'votresite.com') . '/loyalty-card.php'; ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?= urlencode($loyaltyUrl) ?>" alt="QR Code Fidélité" width="150" height="150">
                    </div>
                    <div>
                        <p style="color: #d1d5db; font-size: 14px; margin-bottom: 10px;"><strong>Lien de la carte fidélité :</strong></p>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="loyalty-url" value="<?= 'https://' . ($_SERVER['HTTP_HOST'] ?? 'votresite.com') . '/loyalty-card.php' ?>" readonly style="flex: 1; padding: 10px; background: #1e293b; border: 1px solid #374151; border-radius: 8px; color: white; font-size: 12px;">
                            <button onclick="copyLoyaltyUrl()" class="btn btn-sm btn-gray"><i class="fas fa-copy"></i></button>
                        </div>
                        <button onclick="printQRCode()" class="btn btn-sm" style="margin-top: 10px; background: #f59e0b;"><i class="fas fa-print"></i> Imprimer le QR Code</button>
                    </div>
                </div>
            </div>

            <!-- Récompenses disponibles -->
            <div class="card" style="margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-trophy" style="color: #f59e0b;"></i> Récompenses disponibles</h3>
                <?php if (empty($loyaltyRewards)): ?>
                    <div style="text-align: center; padding: 40px; color: #6b7280;">
                        <i class="fas fa-gift" style="font-size: 48px; margin-bottom: 15px; color: #444;"></i>
                        <p>Aucune récompense configurée</p>
                        <button onclick="openAddRewardModal()" class="btn btn-sm" style="margin-top: 10px; background: #f59e0b;"><i class="fas fa-plus"></i> Créer une récompense</button>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 12px;">
                        <?php foreach ($loyaltyRewards as $reward):
                            $typeLabels = [
                                'discount_percent' => 'Réduction %',
                                'discount_amount' => 'Réduction DA',
                                'free_product' => 'Produit gratuit',
                                'free_delivery' => 'Livraison gratuite'
                            ];
                            $typeLabel = $typeLabels[$reward['reward_type'] ?? 'discount_percent'] ?? $reward['reward_type'];
                        ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #1e293b; border-radius: 10px; border-left: 4px solid #f59e0b;">
                            <div>
                                <h4 style="margin: 0 0 5px 0; font-size: 15px;"><?= htmlspecialchars($reward['name']) ?></h4>
                                <p style="margin: 0; color: #9ca3af; font-size: 12px;"><?= htmlspecialchars($reward['description'] ?? '') ?></p>
                                <div style="margin-top: 8px;">
                                    <span style="background: #f59e0b; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold;">
                                        <i class="fas fa-star"></i> <?= $reward['points_required'] ?> points
                                    </span>
                                    <span style="background: #374151; color: #d1d5db; padding: 3px 10px; border-radius: 20px; font-size: 11px; margin-left: 5px;">
                                        <?= $typeLabel ?>
                                        <?php if ($reward['reward_type'] !== 'free_product' && $reward['reward_type'] !== 'free_delivery'): ?>
                                            : <?= $reward['reward_value'] ?><?= $reward['reward_type'] === 'discount_percent' ? '%' : ' DA' ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <button onclick="deleteReward(<?= $reward['id'] ?>)" class="btn btn-sm" style="background: #ef4444;"><i class="fas fa-trash"></i></button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Leaderboard -->
            <div class="card">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-medal" style="color: #f59e0b;"></i> Top 10 Clients Fidèles</h3>
                <?php if (empty($loyaltyLeaderboard)): ?>
                    <p style="color: #6b7280; text-align: center; padding: 20px;">Aucune donnée de fidélité</p>
                <?php else: ?>
                    <div style="display: grid; gap: 8px;">
                        <?php foreach ($loyaltyLeaderboard as $i => $member):
                            $medalColors = ['#f59e0b', '#9ca3af', '#cd7f32'];
                            $medalColor = $medalColors[$i] ?? '#374151';
                            $memberName = $member['name'] ?? $member['customer_name'] ?? 'Client';
                            $memberPhone = $member['phone'] ?? $member['customer_phone'] ?? '';
                            $memberPoints = $member['loyalty_points'] ?? $member['total_points'] ?? 0;
                            $memberOrders = $member['orders_count'] ?? $member['rewards_redeemed'] ?? 0;
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #1e293b; border-radius: 8px;">
                            <div style="width: 32px; height: 32px; background: <?= $medalColor ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                <?= $i + 1 ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($memberName) ?></div>
                                <div style="color: #6b7280; font-size: 11px;"><?= htmlspecialchars($memberPhone) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: bold; color: #f59e0b;"><?= number_format($memberPoints) ?> pts</div>
                                <div style="font-size: 11px; color: #6b7280;"><?= $memberOrders ?> commandes</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- FIN FIDÉLITÉ -->

        <!-- PRODUITS (simplifié) -->
        <div id="section-products" class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Produits</h2>
                <div style="display: flex; gap: 10px;">
                    <button onclick="syncMenu()" class="btn btn-primary" id="syncBtn">
                        <i class="fas fa-sync-alt"></i> Synchroniser
                    </button>
                    <a href="products-manager.php" class="btn btn-green"><i class="fas fa-cog"></i> Gérer</a>
                </div>
            </div>
            <?php foreach ($products as $category): ?>
            <div class="card">
                <h3 style="margin-bottom: 10px; color: <?php echo $primaryColor; ?>;"><?php echo htmlspecialchars($category['name'] ?? ''); ?></h3>
                <?php foreach ($category['items'] ?? [] as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #374151;">
                    <span><?php echo htmlspecialchars($item['name'] ?? ''); ?></span>
                    <span style="color: <?php echo $primaryColor; ?>;"><?php echo number_format($item['priceSolo'] ?? $item['price'] ?? 0, 0); ?> DA</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Navigation -->
    <div class="bottom-nav">
        <button class="nav-btn active" data-section="orders"><i class="fas fa-receipt"></i><div>Commandes</div></button>
        <button class="nav-btn" data-section="customers"><i class="fas fa-users"></i><div>Clients</div></button>
        <button class="nav-btn" data-section="loyalty"><i class="fas fa-gift"></i><div>Fidélité</div></button>
        <button class="nav-btn" data-section="stats"><i class="fas fa-chart-line"></i><div>Stats</div></button>
        <button class="nav-btn" data-section="archives"><i class="fas fa-archive"></i><div>Archives</div></button>
        <button class="nav-btn" data-section="settings"><i class="fas fa-cog"></i><div>Réglages</div></button>
    </div>

    <!-- Modal PIN -->
    <div id="pinModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#1a1a2e; border-radius:16px; padding:30px; max-width:320px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
            <div style="font-size:48px; margin-bottom:15px;">🔒</div>
            <h3 style="margin:0 0 10px; color:#fff;">Accès restreint</h3>
            <p style="color:#9ca3af; font-size:13px; margin-bottom:20px;">Entrez le code PIN pour accéder à cette section</p>
            <div style="display:flex; gap:8px; justify-content:center; margin-bottom:20px;">
                <input type="password" id="pinDigit1" maxlength="1" pattern="[0-9]" inputmode="numeric" style="width:50px; height:60px; text-align:center; font-size:24px; background:#2a2a3e; border:2px solid #444; border-radius:12px; color:#fff;" oninput="pinInputHandler(this, 1)" onkeydown="pinKeyHandler(event, 1)" autocomplete="off">
                <input type="password" id="pinDigit2" maxlength="1" pattern="[0-9]" inputmode="numeric" style="width:50px; height:60px; text-align:center; font-size:24px; background:#2a2a3e; border:2px solid #444; border-radius:12px; color:#fff;" oninput="pinInputHandler(this, 2)" onkeydown="pinKeyHandler(event, 2)" autocomplete="off">
                <input type="password" id="pinDigit3" maxlength="1" pattern="[0-9]" inputmode="numeric" style="width:50px; height:60px; text-align:center; font-size:24px; background:#2a2a3e; border:2px solid #444; border-radius:12px; color:#fff;" oninput="pinInputHandler(this, 3)" onkeydown="pinKeyHandler(event, 3)" autocomplete="off">
                <input type="password" id="pinDigit4" maxlength="1" pattern="[0-9]" inputmode="numeric" style="width:50px; height:60px; text-align:center; font-size:24px; background:#2a2a3e; border:2px solid #444; border-radius:12px; color:#fff;" oninput="pinInputHandler(this, 4)" onkeydown="pinKeyHandler(event, 4)" autocomplete="off">
            </div>
            <p id="pinError" style="color:#ef4444; font-size:12px; margin-bottom:15px; display:none;">PIN incorrect</p>
            <div style="display:flex; gap:10px;">
                <button onclick="closePinModal()" style="flex:1; padding:12px; background:#333; border:none; border-radius:10px; color:#fff; cursor:pointer;">Annuler</button>
                <button onclick="validatePin()" style="flex:1; padding:12px; background:linear-gradient(135deg,#3b82f6,#2563eb); border:none; border-radius:10px; color:#fff; cursor:pointer; font-weight:600;">Valider</button>
            </div>
        </div>
    </div>

    <script src="notification-sound.js"></script>
    <script>
        // PIN Protection - demande le PIN à chaque accès
        const PROTECTED_SECTIONS = ['stats', 'archives'];
        let pendingSection = null;
        let pendingDeleteAction = null; // Pour stocker l'action de suppression

        function showPinModal(section) {
            pendingSection = section;
            pendingDeleteAction = null; // Reset
            document.getElementById('pinModal').style.display = 'flex';
            document.getElementById('pinError').style.display = 'none';

            // Réinitialiser les inputs
            ['pinDigit1','pinDigit2','pinDigit3','pinDigit4'].forEach(id => {
                const input = document.getElementById(id);
                input.value = '';
                input.style.borderColor = '#444';
            });

            // Focus sur le premier input
            setTimeout(() => document.getElementById('pinDigit1').focus(), 100);

            console.log('[PIN] Modal ouvert pour section:', section);
        }

        function closePinModal() {
            document.getElementById('pinModal').style.display = 'none';
            pendingSection = null;
            pendingDeleteAction = null;
        }

        // 🔒 Afficher le modal PIN spécifiquement pour la suppression
        function showPinModalForDeletion(deleteCallback) {
            pendingDeleteAction = deleteCallback;
            pendingSection = null; // Pas de navigation
            document.getElementById('pinModal').style.display = 'flex';
            document.getElementById('pinError').style.display = 'none';

            // Réinitialiser les inputs
            ['pinDigit1','pinDigit2','pinDigit3','pinDigit4'].forEach(id => {
                const input = document.getElementById(id);
                input.value = '';
                input.style.borderColor = '#444';
            });

            setTimeout(() => document.getElementById('pinDigit1').focus(), 100);
        }

        function pinInputHandler(input, index) {
            // Nettoyer : ne garder que les chiffres
            const oldValue = input.value;
            input.value = input.value.replace(/[^0-9]/g, '');

            // Garder seulement le dernier chiffre si plusieurs sont collés
            if (input.value.length > 1) {
                input.value = input.value.charAt(input.value.length - 1);
            }

            console.log(`[PIN] Input ${index}: "${input.value}" (avant: "${oldValue}")`);

            // Si un chiffre est saisi, passer au suivant
            if (input.value.length === 1 && index < 4) {
                console.log(`[PIN] Auto-focus sur input ${index + 1}`);
                document.getElementById('pinDigit' + (index + 1)).focus();
            }

            // Si le 4ème chiffre est saisi, valider automatiquement
            if (input.value.length === 1 && index === 4) {
                console.log('[PIN] 4ème chiffre saisi, validation auto dans 200ms');
                setTimeout(() => validatePin(), 200);
            }

            // Si vide (backspace), revenir au précédent
            if (input.value.length === 0 && index > 1) {
                console.log(`[PIN] Backspace, retour sur input ${index - 1}`);
                setTimeout(() => document.getElementById('pinDigit' + (index - 1)).focus(), 50);
            }
        }

        // Handler pour touche Entrée
        function pinKeyHandler(event, index) {
            if (event.key === 'Enter') {
                console.log('[PIN] Touche Entrée détectée');
                event.preventDefault();
                validatePin();
            }
        }

        function validatePin() {
            const pin = ['pinDigit1','pinDigit2','pinDigit3','pinDigit4'].map(id => document.getElementById(id).value).join('');
            console.log('[PIN] Validation - PIN saisi:', pin, 'Longueur:', pin.length);

            if (pin.length !== 4) {
                console.warn('[PIN] PIN incomplet, longueur:', pin.length);
                return;
            }

            console.log('[PIN] Envoi requête vérification...');
            fetch('api/admin-pin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'verify', pin })
            })
            .then(r => {
                console.log('[PIN] Réponse reçue, status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('[PIN] Données:', data);
                if (data.success) {
                    console.log('[PIN] ✅ Accès autorisé');

                    // Cas 1: Action de suppression
                    if (pendingDeleteAction) {
                        console.log('[PIN] Exécution action de suppression');
                        const actionToExecute = pendingDeleteAction;
                        closePinModal();
                        actionToExecute(); // Appeler la fonction de suppression
                        return;
                    }

                    // Cas 2: Navigation vers section
                    if (pendingSection) {
                        console.log('[PIN] Navigation vers section:', pendingSection);
                        const sectionToOpen = pendingSection;
                        closePinModal();
                        actuallyNavigate(sectionToOpen);
                        return;
                    }

                    console.warn('[PIN] Aucune action ou section en attente !');
                    closePinModal();
                } else {
                    console.error('[PIN] ❌ PIN refusé:', data.message);
                    document.getElementById('pinError').textContent = data.message || 'PIN incorrect';
                    document.getElementById('pinError').style.display = 'block';
                    document.getElementById('pinDigit1').focus();
                    ['pinDigit1','pinDigit2','pinDigit3','pinDigit4'].forEach(id => {
                        document.getElementById(id).value = '';
                        document.getElementById(id).style.borderColor = '#ef4444';
                    });
                    setTimeout(() => {
                        ['pinDigit1','pinDigit2','pinDigit3','pinDigit4'].forEach(id => document.getElementById(id).style.borderColor = '#444');
                    }, 500);
                }
            })
            .catch(err => {
                console.error('[PIN] Erreur réseau:', err);
                document.getElementById('pinError').textContent = 'Erreur de connexion';
                document.getElementById('pinError').style.display = 'block';
            });
        }

        function actuallyNavigate(section) {
            console.log('[NAV] Navigation vers section:', section);

            // Retirer active de tous les boutons
            document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));

            // Activer le bouton de la section
            const navBtn = document.querySelector('[data-section="' + section + '"]');
            if (navBtn) {
                navBtn.classList.add('active');
                console.log('[NAV] Bouton activé:', section);
            } else {
                console.error('[NAV] Bouton non trouvé pour section:', section);
            }

            // Retirer active de toutes les sections
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));

            // Activer la section demandée
            const sectionElement = document.getElementById('section-' + section);
            if (sectionElement) {
                sectionElement.classList.add('active');
                console.log('[NAV] Section affichée:', section);
            } else {
                console.error('[NAV] Section non trouvée:', 'section-' + section);
            }

            window.location.hash = section;
            window.scrollTo(0, 0);
        }

        // Navigation avec protection PIN - toujours demander
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const section = btn.dataset.section;
                if (PROTECTED_SECTIONS.includes(section)) {
                    showPinModal(section);
                    return;
                }
                actuallyNavigate(section);
            });
        });

        // Hash navigation - supporte toutes les sections
        function navigateToSection(section) {
            if (PROTECTED_SECTIONS.includes(section)) {
                showPinModal(section);
                return;
            }
            const btn = document.querySelector('[data-section="' + section + '"]');
            if (btn) actuallyNavigate(section);
        }

        // Charger la section depuis le hash au démarrage
        const hash = window.location.hash.replace('#', '');
        if (hash && document.querySelector('[data-section="' + hash + '"]')) {
            setTimeout(() => navigateToSection(hash), 100);
        }

        // Change PIN
        function changePin() {
            const oldPin = document.getElementById('oldPinInput').value;
            const newPin = document.getElementById('newPinInput').value;
            const result = document.getElementById('pinChangeResult');

            if (!oldPin || oldPin.length < 4) {
                result.textContent = 'Entrez l\'ancien PIN (4 chiffres minimum)';
                result.style.color = '#ef4444';
                result.style.display = 'block';
                return;
            }
            if (!newPin || newPin.length < 4 || !/^\d+$/.test(newPin)) {
                result.textContent = 'Le nouveau PIN doit contenir 4-8 chiffres';
                result.style.color = '#ef4444';
                result.style.display = 'block';
                return;
            }

            fetch('api/admin-pin.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'change', old_pin: oldPin, new_pin: newPin })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    result.textContent = '✅ PIN modifié avec succès !';
                    result.style.color = '#10b981';
                    document.getElementById('oldPinInput').value = '';
                    document.getElementById('newPinInput').value = '';
                } else {
                    result.textContent = '❌ ' + (data.message || 'Erreur');
                    result.style.color = '#ef4444';
                }
                result.style.display = 'block';
            }).catch(() => {
                result.textContent = '❌ Erreur de connexion';
                result.style.color = '#ef4444';
                result.style.display = 'block';
            });
        }

        // Restaurant status
        loadRestaurantStatus();
        function loadRestaurantStatus() {
            fetch('api/restaurant-status.php?action=get').then(r => r.json()).then(data => {
                if (data.success) updateStatusUI(data.status.accepting_orders);
            }).catch(() => {});
        }
        function toggleRestaurantStatus() {
            fetch('api/restaurant-status.php?action=toggle', { method: 'POST' }).then(r => r.json()).then(data => {
                if (data.success) { updateStatusUI(data.status.accepting_orders); alert(data.message); }
            });
        }
        function updateStatusUI(isOpen) {
            const card = document.getElementById('restaurant-status-toggle');
            document.getElementById('status-text').textContent = isOpen ? '✅ OUVERT' : '🔒 FERMÉ';
            document.getElementById('status-subtitle').textContent = isOpen ? 'Les clients peuvent commander' : 'Commandes désactivées';
            document.getElementById('status-indicator').innerHTML = isOpen ? '<i class="fas fa-check-circle" style="color:#10b981;"></i>' : '<i class="fas fa-times-circle" style="color:#ef4444;"></i>';
            card.style.borderColor = isOpen ? '#10b981' : '#ef4444';
        }

        // WhatsApp groupé
        function sendGroupWhatsApp() {
            document.getElementById('whatsapp-panel').style.display = document.getElementById('whatsapp-panel').style.display === 'none' ? 'block' : 'none';
        }
        function openWhatsAppLinks() {
            const message = document.getElementById('wa-message').value;
            if (!message) { alert('Entrez un message'); return; }
            const phones = Array.from(document.querySelectorAll('.customer-checkbox:checked')).map(c => c.value);
            if (phones.length === 0) { alert('Sélectionnez au moins un client'); return; }
            phones.forEach((phone, i) => {
                setTimeout(() => {
                    window.open('https://wa.me/' + phone.replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(message), '_blank');
                }, i * 500);
            });
        }

        // Toggle token visibility
        function toggleTokenVisibility() {
            const input = document.getElementById('whatsapp_token');
            const icon = document.getElementById('toggle-eye');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Filtrage des clients
        function filterCustomers(category) {
            const rows = document.querySelectorAll('.customer-row');
            const filters = document.querySelectorAll('.customer-filter');
            const searchTerm = document.getElementById('customerSearch')?.value.toLowerCase() || '';

            // Mettre à jour les boutons de filtre
            filters.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.filter === category) {
                    btn.classList.add('active');
                }
            });

            // Filtrer les clients (combiné avec recherche)
            rows.forEach(row => {
                const matchesCategory = category === 'all' || row.dataset.category === category;
                const matchesSearch = !searchTerm || row.dataset.search.includes(searchTerm);
                row.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
            });
        }

        // Recherche de clients
        function searchCustomers() {
            const searchTerm = document.getElementById('customerSearch').value.toLowerCase();
            const rows = document.querySelectorAll('.customer-row');
            const activeFilter = document.querySelector('.customer-filter.active')?.dataset.filter || 'all';

            rows.forEach(row => {
                const matchesSearch = !searchTerm || row.dataset.search.includes(searchTerm);
                const matchesCategory = activeFilter === 'all' || row.dataset.category === activeFilter;
                row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }

        // FAQ
        function addFaqItem() {
            const container = document.getElementById('faq-items');
            const div = document.createElement('div');
            div.className = 'faq-item';
            div.style.cssText = 'background:#1e293b;padding:12px;border-radius:8px;margin-bottom:10px;';
            div.innerHTML = '<input type="text" name="faq_q[]" placeholder="Question" style="width:100%;padding:8px;background:#2a2a3e;border:1px solid #444;border-radius:6px;color:white;margin-bottom:8px;"><textarea name="faq_a[]" placeholder="Réponse" style="width:100%;padding:8px;background:#2a2a3e;border:1px solid #444;border-radius:6px;color:white;min-height:60px;"></textarea>';
            container.appendChild(div);
        }



        // Notifications (polling toutes les 10 secondes pour être réactif)
        if (window.orderNotificationSystem) orderNotificationSystem.start(10);

// === GESTION CLIENTS ===
function openAddCustomerModal() {
    document.getElementById('addCustomerModal').style.display = 'flex';
    document.getElementById('newCustomerName').value = '';
    document.getElementById('newCustomerPhone').value = '';
}

function closeAddCustomerModal() {
    document.getElementById('addCustomerModal').style.display = 'none';
}

function submitAddCustomer(event) {
    event.preventDefault();
    
    const name = document.getElementById('newCustomerName').value.trim();
    const phone = document.getElementById('newCustomerPhone').value.trim();
    
    if (!phone) {
        alert('Le numéro de téléphone est requis');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'add_customer');
    formData.append('customer_name', name);
    formData.append('customer_phone', phone);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddCustomerModal();
            window.location.href = 'index.php#customers';
            location.reload();
        } else {
            alert('Erreur: ' + (data.error || 'Impossible d\'ajouter le client'));
        }
    })
    .catch(error => {
        alert('Erreur de connexion');
        console.error(error);
    });
}

function deleteCustomer(customerId, customerName) {
    if (!confirm('Supprimer le client "' + customerName + '" ?\n\nCette action est irréversible.')) {
        return;
    }

    fetch('api/customers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete',
            customer_id: customerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Client supprimé avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible de supprimer le client'));
        }
    })
    .catch(error => {
        alert('Erreur de connexion: ' + error.message);
        console.error(error);
    });
}

// Fermer modal en cliquant à l'extérieur
document.getElementById('addCustomerModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddCustomerModal();
    }
});

// === GESTION FIDÉLITÉ ===
function updateLoyaltyConfig() {
    const enabled = document.getElementById('loyaltyEnabled').checked;
    const pointsPerEuro = document.getElementById('pointsPerEuro').value;
    
    const formData = new FormData();
    formData.append('action', 'update_loyalty_config');
    formData.append('loyalty_enabled', enabled ? '1' : '0');
    formData.append('points_per_euro', pointsPerEuro);
    
    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (!data.success) alert('Erreur lors de la sauvegarde');
        });
}

function openAddRewardModal() {
    document.getElementById('addRewardModal').style.display = 'flex';
    document.getElementById('rewardName').value = '';
    document.getElementById('rewardDescription').value = '';
    document.getElementById('rewardPoints').value = '100';
    document.getElementById('rewardType').value = 'discount_percent';
    document.getElementById('rewardValue').value = '10';
    toggleRewardValue();
}

function closeAddRewardModal() {
    document.getElementById('addRewardModal').style.display = 'none';
}

function toggleRewardValue() {
    const type = document.getElementById('rewardType').value;
    const container = document.getElementById('rewardValueContainer');
    container.style.display = type === 'free_product' ? 'none' : 'block';
}

function submitAddReward(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('action', 'add_reward');
    formData.append('reward_name', document.getElementById('rewardName').value);
    formData.append('reward_description', document.getElementById('rewardDescription').value);
    formData.append('points_required', document.getElementById('rewardPoints').value);
    formData.append('reward_type', document.getElementById('rewardType').value);
    formData.append('reward_value', document.getElementById('rewardValue').value);
    
    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeAddRewardModal();
                window.location.href = 'index.php#loyalty';
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Impossible de créer la récompense'));
            }
        });
}

function deleteReward(rewardId) {
    if (!confirm('Supprimer cette récompense ?')) return;

    const formData = new FormData();
    formData.append('action', 'delete_reward');
    formData.append('reward_id', rewardId);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'index.php#loyalty';
                location.reload();
            } else {
                alert('Erreur lors de la suppression');
            }
        });
}

// Fermer modal récompense en cliquant dehors
document.getElementById('addRewardModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddRewardModal();
});

// Fermer modal points en cliquant dehors
document.getElementById('addPointsModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeAddPointsModal();
});

// === GESTION DIFFUSION ===
function toggleBroadcastPanel() {
    const panel = document.getElementById('whatsapp-panel');
    const checkboxes = document.querySelectorAll('.broadcast-cb');

    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        checkboxes.forEach(cb => cb.style.display = 'inline-block');
    } else {
        panel.style.display = 'none';
        checkboxes.forEach(cb => cb.style.display = 'none');
        deselectAllCustomers();
    }
}

function selectAllCustomers() {
    document.querySelectorAll('.customer-checkbox').forEach(cb => {
        const row = cb.closest('.customer-row');
        if (row && row.style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateSelectedCount();
}

function deselectAllCustomers() {
    document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function toggleAllCustomers(masterCheckbox) {
    if (masterCheckbox.checked) {
        selectAllCustomers();
    } else {
        deselectAllCustomers();
    }
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.customer-checkbox:checked').length;
    document.getElementById('selected-count').textContent = count;
}

// === GESTION POINTS MANUELS ===
function openAddPointsModal(customerId, customerName) {
    document.getElementById('pointsCustomerId').value = customerId;
    document.getElementById('pointsCustomerName').textContent = customerName;
    document.getElementById('pointsAmount').value = 10;
    document.getElementById('addPointsModal').style.display = 'flex';
}

function closeAddPointsModal() {
    document.getElementById('addPointsModal').style.display = 'none';
}

function submitAddPoints(event) {
    event.preventDefault();

    const customerId = document.getElementById('pointsCustomerId').value;
    const points = document.getElementById('pointsAmount').value;

    const formData = new FormData();
    formData.append('action', 'add_loyalty_points');
    formData.append('customer_id', customerId);
    formData.append('points', points);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeAddPointsModal();
                window.location.href = 'index.php#customers';
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Impossible d\'ajouter les points'));
            }
        })
        .catch(error => {
            alert('Erreur de connexion');
            console.error(error);
        });
}

// === MODAL UTILISER POINTS (REDEEM) ===
let selectedRedeemCustomer = null;
const availableRewards = <?php echo json_encode($rewards ?? []); ?>;

function openRedeemModal() {
    document.getElementById('redeemModal').style.display = 'flex';
    document.getElementById('redeemSearchInput').value = '';
    document.getElementById('redeemSearchResults').innerHTML = '';
    document.getElementById('redeemSelectedCustomer').style.display = 'none';
    document.getElementById('redeemRewardsSection').style.display = 'none';
    document.getElementById('redeemNoRewards').style.display = 'none';
    selectedRedeemCustomer = null;
    setTimeout(() => document.getElementById('redeemSearchInput').focus(), 100);
}

function closeRedeemModal() {
    document.getElementById('redeemModal').style.display = 'none';
}

let searchTimeout = null;
function searchCustomerForRedeem(query) {
    clearTimeout(searchTimeout);
    const resultsDiv = document.getElementById('redeemSearchResults');

    if (query.length < 2) {
        resultsDiv.innerHTML = '<p style="color: #6b7280; font-size: 13px; padding: 10px;">Tapez au moins 2 caractères...</p>';
        return;
    }

    resultsDiv.innerHTML = '<p style="color: #9ca3af; font-size: 13px; padding: 10px;"><i class="fas fa-spinner fa-spin"></i> Recherche...</p>';

    searchTimeout = setTimeout(() => {
        const formData = new FormData();
        formData.append('action', 'search_customer');
        formData.append('query', query);

        fetch('', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.customers.length > 0) {
                    resultsDiv.innerHTML = data.customers.map(c => `
                        <div onclick="selectCustomerForRedeem(${c.id}, '${c.name.replace(/'/g, "\\'")}', '${c.phone}', '${c.loyalty_code || ''}', ${c.loyalty_points || 0})"
                             style="padding: 12px; background: #1e293b; border-radius: 8px; margin-bottom: 8px; cursor: pointer; border: 1px solid #374151; transition: all 0.2s;"
                             onmouseover="this.style.borderColor='#10b981'" onmouseout="this.style.borderColor='#374151'">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="font-weight: 600;">${c.name}</div>
                                    <div style="color: #9ca3af; font-size: 12px;">${c.phone}</div>
                                    <div style="color: #f59e0b; font-size: 11px;">${c.loyalty_code || 'N/A'}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: bold; color: #f59e0b;">${c.loyalty_points || 0}</div>
                                    <div style="color: #6b7280; font-size: 10px;">points</div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    resultsDiv.innerHTML = '<p style="color: #6b7280; font-size: 13px; padding: 10px;">Aucun client trouvé</p>';
                }
            })
            .catch(err => {
                resultsDiv.innerHTML = '<p style="color: #ef4444; font-size: 13px; padding: 10px;">Erreur de recherche</p>';
                console.error(err);
            });
    }, 300);
}

function selectCustomerForRedeem(id, name, phone, code, points) {
    selectedRedeemCustomer = { id, name, phone, code, points };

    // Masquer recherche
    document.getElementById('redeemSearchResults').innerHTML = '';
    document.getElementById('redeemSearchInput').value = '';

    // Afficher client sélectionné
    document.getElementById('redeemSelectedCustomer').style.display = 'block';
    document.getElementById('redeemCustomerName').textContent = name;
    document.getElementById('redeemCustomerPhone').textContent = phone;
    document.getElementById('redeemCustomerCode').textContent = code || 'N/A';
    document.getElementById('redeemCustomerPoints').textContent = points;
    document.getElementById('redeemCustomerId').value = id;

    // Afficher récompenses disponibles
    const rewardsList = document.getElementById('redeemRewardsList');
    const rewardsSection = document.getElementById('redeemRewardsSection');
    const noRewards = document.getElementById('redeemNoRewards');

    const affordable = availableRewards.filter(r => points >= r.points_required);

    if (affordable.length > 0) {
        rewardsSection.style.display = 'block';
        noRewards.style.display = 'none';

        rewardsList.innerHTML = affordable.map(r => `
            <div style="padding: 15px; background: #1e293b; border-radius: 10px; margin-bottom: 10px; border-left: 4px solid #10b981;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; margin-bottom: 2px;">${r.name}</div>
                        <div style="color: #9ca3af; font-size: 12px;">${r.description || ''}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #f59e0b; font-weight: bold;">${r.points_required} pts</div>
                        <button onclick="redeemReward(${r.id}, '${r.name.replace(/'/g, "\\'")}', ${r.points_required})"
                                style="margin-top: 5px; padding: 6px 12px; background: #10b981; border: none; border-radius: 6px; color: white; font-size: 12px; cursor: pointer;">
                            <i class="fas fa-gift"></i> Utiliser
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        rewardsSection.style.display = 'none';
        noRewards.style.display = 'block';
    }
}

function redeemReward(rewardId, rewardName, pointsRequired) {
    if (!selectedRedeemCustomer) {
        alert('Aucun client sélectionné');
        return;
    }

    if (!confirm(`Utiliser ${pointsRequired} points pour "${rewardName}" ?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'redeem_reward');
    formData.append('customer_id', selectedRedeemCustomer.id);
    formData.append('reward_id', rewardId);

    fetch('', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(`✓ Récompense utilisée !\n${data.reward_name}\nPoints restants: ${data.new_points}`);
                closeRedeemModal();
                window.location.href = 'index.php#loyalty';
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Impossible d\'utiliser la récompense'));
            }
        })
        .catch(err => {
            alert('Erreur de connexion');
            console.error(err);
        });
}

// === GESTION QR CODE FIDÉLITÉ ===
function copyLoyaltyUrl() {
    const url = document.getElementById('loyalty-url').value;
    navigator.clipboard.writeText(url).then(() => {
        alert('Lien copié !');
    }).catch(() => {
        // Fallback
        const input = document.getElementById('loyalty-url');
        input.select();
        document.execCommand('copy');
        alert('Lien copié !');
    });
}

function printQRCode() {
    const qrDiv = document.getElementById('loyalty-qrcode');
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>QR Code Fidélité</title>
            <style>
                body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: Arial, sans-serif; }
                .container { text-align: center; }
                .qr { margin: 20px auto; }
                h2 { color: #f59e0b; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>Carte de Fidélité</h2>
                <div class="qr">${qrDiv.innerHTML}</div>
                <p>Scannez pour accéder à votre carte fidélité</p>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Amélioration du style du switch pour la fidélité
document.querySelectorAll('.switch input[type="checkbox"]').forEach(input => {
    const slider = input.nextElementSibling;
    if (slider) {
        const updateSlider = () => {
            slider.style.backgroundColor = input.checked ? '#f59e0b' : '#374151';
            slider.innerHTML = input.checked
                ? '<span style="position:absolute;left:4px;top:3px;width:20px;height:20px;background:white;border-radius:50%;transition:.4s;transform:translateX(24px);"></span>'
                : '<span style="position:absolute;left:4px;top:3px;width:20px;height:20px;background:white;border-radius:50%;transition:.4s;"></span>';
        };
        updateSlider();
        input.addEventListener('change', updateSlider);
    }
});

// ===== Gestion Livraison & Plateformes =====
function toggleDelivery() {
    fetch('api/restaurant-status.php?action=toggle_delivery', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const bg = document.getElementById('delivery-toggle-bg');
                const knob = document.getElementById('delivery-toggle-knob');
                if (data.delivery_enabled) {
                    bg.style.background = '#10b981';
                    knob.style.left = '26px';
                } else {
                    bg.style.background = '#4b5563';
                    knob.style.left = '2px';
                }
                showToast(data.message || 'Livraison mise à jour');
            } else {
                showToast(data.error || 'Erreur', 'error');
            }
        })
        .catch(() => showToast('Erreur réseau', 'error'));
}

function togglePlatform(platformId) {
    fetch('api/restaurant-status.php?action=update_platform', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ platform_id: platformId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`.platform-row[data-id="${platformId}"]`);
            if (row) {
                const toggle = row.querySelector('.platform-toggle');
                const platform = data.platforms.find(p => p.id === platformId);
                if (platform && toggle) {
                    toggle.style.background = platform.enabled ? '#10b981' : '#4b5563';
                    toggle.querySelector('div').style.left = platform.enabled ? '20px' : '2px';
                }
            }
            showToast('Plateforme mise à jour');
        } else {
            showToast(data.error || 'Erreur', 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
}

function openAddPlatformModal() {
    document.getElementById('add-platform-modal').style.display = 'flex';
    document.getElementById('new-platform-name').value = '';
    document.getElementById('new-platform-url').value = '';
}

function closeAddPlatformModal() {
    document.getElementById('add-platform-modal').style.display = 'none';
}

function addPlatform() {
    const name = document.getElementById('new-platform-name').value.trim();
    const url = document.getElementById('new-platform-url').value.trim();

    if (!name || !url) {
        showToast('Remplissez tous les champs', 'error');
        return;
    }

    fetch('api/restaurant-status.php?action=add_platform', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, url })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeAddPlatformModal();
            location.reload(); // Reload to show new platform
        } else {
            showToast(data.error || 'Erreur', 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
}

function deletePlatform(platformId) {
    if (!confirm('Supprimer cette plateforme ?')) return;

    fetch('api/restaurant-status.php?action=delete_platform', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ platform_id: platformId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`.platform-row[data-id="${platformId}"]`);
            if (row) row.remove();
            showToast('Plateforme supprimée');
        } else {
            showToast(data.error || 'Erreur', 'error');
        }
    })
    .catch(() => showToast('Erreur réseau', 'error'));
}

// ===== Filtrer archives par mois (affichage) =====
function filterArchivesByMonth() {
    const selectedMonth = document.getElementById('archiveMonthFilter').value;
    const dateCards = document.querySelectorAll('.archive-date-card');

    let visibleCount = 0;

    dateCards.forEach(card => {
        const cardMonth = card.dataset.month;

        // Si "Tous les mois" ou si le mois correspond
        if (selectedMonth === '' || cardMonth === selectedMonth) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Message si aucun résultat
    const archivesSection = document.getElementById('section-archives');
    let noResultMsg = archivesSection.querySelector('.no-archive-result');

    if (visibleCount === 0 && selectedMonth !== '') {
        if (!noResultMsg) {
            noResultMsg = document.createElement('div');
            noResultMsg.className = 'no-archive-result';
            noResultMsg.style.cssText = 'text-align: center; padding: 40px; color: #9ca3af;';
            noResultMsg.innerHTML = `
                <i class="fas fa-calendar-times" style="font-size: 48px; color: #555; margin-bottom: 15px; display: block;"></i>
                <p style="font-size: 16px;">Aucune commande pour ce mois</p>
                <button onclick="document.getElementById('archiveMonthFilter').value = ''; filterArchivesByMonth();"
                        class="btn btn-sm" style="margin-top: 15px; background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                    <i class="fas fa-redo"></i> Afficher tout
                </button>
            `;
            // Insérer après les stats
            const statsDiv = archivesSection.querySelector('.stats');
            if (statsDiv) {
                statsDiv.parentNode.insertBefore(noResultMsg, statsDiv.nextSibling);
            }
        }
        noResultMsg.style.display = 'block';
    } else if (noResultMsg) {
        noResultMsg.style.display = 'none';
    }
}

// ===== Export CSV par mois =====
function exportArchives() {
    const month = document.getElementById('archiveMonthFilter').value;
    let url = '?export=archives';
    if (month) {
        url += '&month=' + encodeURIComponent(month);
    }
    window.location.href = url;
}

// ===== Synchroniser menu =====
async function syncMenu() {
    const btn = document.getElementById('syncBtn');
    const originalHtml = btn.innerHTML;

    // Désactiver le bouton et afficher le chargement
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Synchronisation...';

    try {
        const response = await fetch('sync-menu.php?sync');
        const result = await response.json();

        if (result.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Synchronisé !';
            btn.style.background = '#10b981';

            // Afficher notification
            alert(`✅ Menu synchronisé avec succès !\n${result.updated} élément(s) mis à jour.`);

            // Recharger la page après 1 seconde
            setTimeout(() => location.reload(), 1000);
        } else {
            throw new Error(result.error || 'Erreur de synchronisation');
        }
    } catch (error) {
        console.error('Erreur sync:', error);
        btn.innerHTML = '<i class="fas fa-times"></i> Erreur';
        btn.style.background = '#ef4444';
        alert('❌ Erreur lors de la synchronisation : ' + error.message);

        // Restaurer le bouton après 2 secondes
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.style.background = '';
            btn.disabled = false;
        }, 2000);
    }
}


// ========== SÉLECTION MULTIPLE COMMANDES ==========

function toggleAllOrders(checkbox) {
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    orderCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateOrdersSelection();
}

function updateOrdersSelection() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const count = checked.length;
    const deleteBtn = document.getElementById('deleteSelectedOrders');
    const countSpan = document.getElementById('selectedOrdersCount');
    const selectAllCheckbox = document.getElementById('selectAllOrders');

    // Vérifier que les éléments existent (peut être sur une autre page)
    if (!deleteBtn || !countSpan || !selectAllCheckbox) {
        return;
    }

    if (count > 0) {
        deleteBtn.style.display = 'inline-block';
        countSpan.textContent = count;
    } else {
        deleteBtn.style.display = 'none';
    }

    // Mettre à jour "tout sélectionner"
    const total = document.querySelectorAll('.order-checkbox').length;
    selectAllCheckbox.checked = (count === total && total > 0);
}

function deleteSelectedOrders() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const count = checked.length;

    if (count === 0) return;

    // 🔒 ÉTAPE 1: Demander le PIN admin via modal
    showPinModalForDeletion(() => {
        // Cette fonction sera appelée après validation du PIN
        performDeleteOrders(checked, count);
    });
}

async function performDeleteOrders(checked, count) {
    // 🔒 ÉTAPE 2: Double confirmation
    const confirmation = confirm(
        "⚠️ ATTENTION: Action irréversible!\n\n" +
        "Vous êtes sur le point de SUPPRIMER DÉFINITIVEMENT " + count + " commande(s).\n\n" +
        "Cette action est IRRÉVERSIBLE et les données seront perdues à jamais.\n\n" +
        "Voulez-vous vraiment continuer?"
    );

    if (!confirmation) return;

    const doubleCheck = confirm(
        "Dernière confirmation:\n\n" +
        "Êtes-vous ABSOLUMENT SÛR de vouloir supprimer " + count + " commande(s)?"
    );

    if (!doubleCheck) return;

    // Récupérer les IDs
    const orderIds = Array.from(checked).map(cb => cb.dataset.orderId);

    // Désactiver le bouton pendant la suppression
    const deleteBtn = document.getElementById('deleteSelectedOrders');
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';

    // Envoyer la requête de suppression
    fetch('api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete_multiple',
            order_ids: orderIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ " + count + " commande(s) supprimée(s) avec succès");
            location.reload();
        } else {
            alert("❌ Erreur: " + (data.error || 'Impossible de supprimer les commandes'));
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
        }
    })
    .catch(error => {
        alert("❌ Erreur de connexion: " + error.message);
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
    });
}

// ========== SÉLECTION MULTIPLE CLIENTS ==========

function toggleAllClients(checkbox) {
    const clientCheckboxes = document.querySelectorAll('.client-checkbox');
    clientCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateClientsSelection();
}

function updateClientsSelection() {
    const checked = document.querySelectorAll('.client-checkbox:checked');
    const count = checked.length;
    const deleteBtn = document.getElementById('deleteSelectedClients');
    const countSpan = document.getElementById('selectedClientsCount');
    const selectAllCheckbox = document.getElementById('selectAllClients');

    // Vérifier que les éléments existent
    if (!deleteBtn || !countSpan || !selectAllCheckbox) {
        return;
    }

    if (count > 0) {
        deleteBtn.style.display = 'inline-block';
        countSpan.textContent = count;
    } else {
        deleteBtn.style.display = 'none';
    }

    // Mettre à jour "tout sélectionner"
    const total = document.querySelectorAll('.client-checkbox').length;
    selectAllCheckbox.checked = (count === total && total > 0);
}

function deleteSelectedClients() {
    const checked = document.querySelectorAll('.client-checkbox:checked');
    const count = checked.length;

    if (count === 0) return;

    // 🔒 ÉTAPE 1: Demander le PIN admin via modal
    showPinModalForDeletion(() => {
        // Cette fonction sera appelée après validation du PIN
        performDeleteClients(checked, count);
    });
}

async function performDeleteClients(checked, count) {
    // 🔒 ÉTAPE 2: Double confirmation
    const confirmation = confirm(
        "⚠️ ATTENTION: Action irréversible!\n\n" +
        "Vous êtes sur le point de SUPPRIMER DÉFINITIVEMENT " + count + " client(s).\n\n" +
        "Cette action est IRRÉVERSIBLE et les données seront perdues à jamais.\n\n" +
        "Voulez-vous vraiment continuer?"
    );

    if (!confirmation) return;

    const doubleCheck = confirm(
        "Dernière confirmation:\n\n" +
        "Êtes-vous ABSOLUMENT SÛR de vouloir supprimer " + count + " client(s)?"
    );

    if (!doubleCheck) return;

    // Récupérer les IDs
    const customerIds = Array.from(checked).map(cb => cb.dataset.customerId);

    // Désactiver le bouton pendant la suppression
    const deleteBtn = document.getElementById('deleteSelectedClients');
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';

    // Envoyer la requête de suppression
    fetch('api/customers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete_multiple',
            customer_ids: customerIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ " + count + " client(s) supprimé(s) avec succès");
            location.reload();
        } else {
            alert("❌ Erreur: " + (data.error || 'Impossible de supprimer les clients'));
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
        }
    })
    .catch(error => {
        alert("❌ Erreur de connexion: " + error.message);
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
    });
}

// ========== SÉLECTION MULTIPLE ARCHIVES ==========

function toggleAllArchives(checkbox) {
    const archiveCheckboxes = document.querySelectorAll('.archive-checkbox');
    archiveCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateArchivesSelection();
}

function updateArchivesSelection() {
    const checked = document.querySelectorAll('.archive-checkbox:checked');
    const count = checked.length;
    const deleteBtn = document.getElementById('deleteSelectedArchives');
    const countSpan = document.getElementById('selectedArchivesCount');
    const selectAllCheckbox = document.getElementById('selectAllArchives');

    // Vérifier que les éléments existent
    if (!deleteBtn || !countSpan || !selectAllCheckbox) {
        return;
    }

    if (count > 0) {
        deleteBtn.style.display = 'inline-block';
        countSpan.textContent = count;
    } else {
        deleteBtn.style.display = 'none';
    }

    // Mettre à jour "tout sélectionner"
    const total = document.querySelectorAll('.archive-checkbox').length;
    selectAllCheckbox.checked = (count === total && total > 0);
}

function deleteSelectedArchives() {
    const checked = document.querySelectorAll('.archive-checkbox:checked');
    const count = checked.length;

    if (count === 0) return;

    // 🔒 ÉTAPE 1: Demander le PIN admin via modal
    showPinModalForDeletion(() => {
        // Cette fonction sera appelée après validation du PIN
        performDeleteArchives(checked, count);
    });
}

async function performDeleteArchives(checked, count) {
    // 🔒 ÉTAPE 2: Double confirmation
    const confirmation = confirm(
        "⚠️ ATTENTION: Action irréversible!\n\n" +
        "Vous êtes sur le point de SUPPRIMER DÉFINITIVEMENT " + count + " commande(s) archivée(s).\n\n" +
        "Cette action est IRRÉVERSIBLE et les données seront perdues à jamais.\n\n" +
        "Voulez-vous vraiment continuer?"
    );

    if (!confirmation) return;

    const doubleCheck = confirm(
        "Dernière confirmation:\n\n" +
        "Êtes-vous ABSOLUMENT SÛR de vouloir supprimer " + count + " commande(s) archivée(s)?"
    );

    if (!doubleCheck) return;

    // Récupérer les IDs
    const orderIds = Array.from(checked).map(cb => cb.dataset.orderId);

    // Désactiver le bouton pendant la suppression
    const deleteBtn = document.getElementById('deleteSelectedArchives');
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';

    // Envoyer la requête de suppression (utilise le même endpoint que les commandes)
    fetch('api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete_multiple',
            order_ids: orderIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ " + count + " commande(s) archivée(s) supprimée(s) avec succès");
            location.reload();
        } else {
            alert("❌ Erreur: " + (data.error || 'Impossible de supprimer les commandes archivées'));
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
        }
    })
    .catch(error => {
        alert("❌ Erreur de connexion: " + error.message);
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Supprimer (' + count + ')';
    });
}

// ========================================
// 🖨️ SYSTÈME D'IMPRESSION BLUETOOTH
// ========================================

// Variables globales pour l'imprimante
let printerDevice = null;
let printerCharacteristic = null;
let isPrinterConnected = false;

// UUID pour imprimantes ESC/POS (Generic Access Profile)
const PRINTER_SERVICE_UUID = 0x18F0;
const PRINTER_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';

// Fonction pour sauvegarder l'état de connexion dans localStorage
function savePrinterState(deviceName) {
    localStorage.setItem('printerConnected', 'true');
    localStorage.setItem('printerName', deviceName);
}

function clearPrinterState() {
    localStorage.removeItem('printerConnected');
    localStorage.removeItem('printerName');
}

// Fonction pour mettre à jour l'UI du statut de l'imprimante
function updatePrinterUI(connected, deviceName = '') {
    const statusIcon = document.getElementById('printer-status-icon');
    const statusText = document.getElementById('printer-status-text');
    const printerName = document.getElementById('printer-name');
    const btnConnect = document.getElementById('btn-connect-printer');
    const btnDisconnect = document.getElementById('btn-disconnect-printer');
    const btnTest = document.getElementById('btn-test-print');
    const printButtons = document.querySelectorAll('.print-button-container');

    if (connected) {
        statusIcon.style.color = '#10b981';
        statusText.textContent = 'Imprimante connectée';
        printerName.textContent = deviceName;
        btnConnect.style.display = 'none';
        btnDisconnect.style.display = 'inline-block';
        btnTest.style.display = 'inline-block';

        // Afficher tous les boutons d'impression sur les cartes
        printButtons.forEach(btn => btn.style.display = 'block');
    } else {
        statusIcon.style.color = '#dc2626';
        statusText.textContent = 'Aucune imprimante connectée';
        printerName.textContent = '';
        btnConnect.style.display = 'inline-block';
        btnDisconnect.style.display = 'none';
        btnTest.style.display = 'none';

        // Masquer tous les boutons d'impression
        printButtons.forEach(btn => btn.style.display = 'none');
    }
}

// Connexion à l'imprimante Bluetooth
async function connectPrinter() {
    try {
        // Vérifier si Web Bluetooth est disponible
        if (!navigator.bluetooth) {
            alert('❌ Web Bluetooth n\'est pas disponible sur ce navigateur.\n\nUtilisez Chrome ou Edge sur Windows/Mac/Android.');
            return;
        }

        // Demander à l'utilisateur de sélectionner une imprimante
        console.log('Recherche d\'imprimantes Bluetooth...');

        printerDevice = await navigator.bluetooth.requestDevice({
            filters: [
                { services: [PRINTER_SERVICE_UUID] }
            ],
            optionalServices: [PRINTER_SERVICE_UUID]
        });

        console.log('Imprimante sélectionnée:', printerDevice.name);

        // Connexion au serveur GATT
        const server = await printerDevice.gatt.connect();
        console.log('Connecté au serveur GATT');

        // Récupérer le service d'impression
        const service = await server.getPrimaryService(PRINTER_SERVICE_UUID);
        console.log('Service d\'impression récupéré');

        // Récupérer la caractéristique d'écriture
        printerCharacteristic = await service.getCharacteristic(PRINTER_CHARACTERISTIC_UUID);
        console.log('Caractéristique d\'impression récupérée');

        // Succès!
        isPrinterConnected = true;
        updatePrinterUI(true, printerDevice.name);
        savePrinterState(printerDevice.name);

        alert('✅ Imprimante connectée avec succès!\n\n' + printerDevice.name);

        // Gérer la déconnexion
        printerDevice.addEventListener('gattserverdisconnected', onPrinterDisconnected);

    } catch (error) {
        console.error('Erreur connexion imprimante:', error);

        if (error.name === 'NotFoundError') {
            alert('❌ Aucune imprimante trouvée.\n\nAssurez-vous que:\n- L\'imprimante est allumée\n- Le Bluetooth est activé\n- L\'imprimante est en mode appairage');
        } else {
            alert('❌ Erreur de connexion:\n\n' + error.message);
        }

        isPrinterConnected = false;
        updatePrinterUI(false);
    }
}

// Déconnexion de l'imprimante
function disconnectPrinter() {
    if (printerDevice && printerDevice.gatt.connected) {
        printerDevice.gatt.disconnect();
    }

    printerDevice = null;
    printerCharacteristic = null;
    isPrinterConnected = false;
    clearPrinterState();
    updatePrinterUI(false);

    alert('✅ Imprimante déconnectée');
}

// Gestion de la déconnexion automatique
function onPrinterDisconnected() {
    console.log('Imprimante déconnectée');
    isPrinterConnected = false;
    clearPrinterState();
    updatePrinterUI(false);
}

// Fonction pour envoyer des données à l'imprimante (ESC/POS)
async function sendToPrinter(data) {
    if (!isPrinterConnected || !printerCharacteristic) {
        throw new Error('Imprimante non connectée');
    }

    try {
        // Convertir les données en Uint8Array si nécessaire
        const buffer = typeof data === 'string' ?
            new TextEncoder().encode(data) :
            new Uint8Array(data);

        // Envoyer par chunks de 512 octets (limitation Bluetooth)
        const chunkSize = 512;
        for (let i = 0; i < buffer.length; i += chunkSize) {
            const chunk = buffer.slice(i, i + chunkSize);
            await printerCharacteristic.writeValue(chunk);
            // Petit délai pour éviter de saturer la connexion
            await new Promise(resolve => setTimeout(resolve, 50));
        }

        return true;
    } catch (error) {
        console.error('Erreur envoi imprimante:', error);
        throw error;
    }
}

// Commandes ESC/POS de base
const ESC = 0x1B;
const GS = 0x1D;

const ESC_POS = {
    INIT: [ESC, 0x40], // Initialiser l'imprimante
    ALIGN_CENTER: [ESC, 0x61, 0x01], // Centrer
    ALIGN_LEFT: [ESC, 0x61, 0x00], // Aligner à gauche
    BOLD_ON: [ESC, 0x45, 0x01], // Gras ON
    BOLD_OFF: [ESC, 0x45, 0x00], // Gras OFF
    SIZE_NORMAL: [GS, 0x21, 0x00], // Taille normale
    SIZE_DOUBLE: [GS, 0x21, 0x11], // Double taille
    SIZE_LARGE: [GS, 0x21, 0x22], // Grande taille
    CUT: [GS, 0x56, 0x00], // Couper le papier
    FEED: [ESC, 0x64, 0x03], // Avancer papier
    LINE: '--------------------------------\n'
};

// Fonction pour créer le ticket de commande
function createTicketData(order) {
    const data = [];
    const encoder = new TextEncoder();

    // Fonction helper pour ajouter du texte
    function addText(text) {
        data.push(...encoder.encode(text));
    }

    function addCommand(...commands) {
        commands.forEach(cmd => {
            if (Array.isArray(cmd)) {
                data.push(...cmd);
            }
        });
    }

    // === EN-TÊTE ===
    addCommand(ESC_POS.INIT);
    addCommand(ESC_POS.ALIGN_CENTER);
    addCommand(ESC_POS.SIZE_LARGE, ESC_POS.BOLD_ON);
    addText('<?php echo addslashes($restaurantName ?? 'Restaurant'); ?>\n');
    addCommand(ESC_POS.SIZE_NORMAL, ESC_POS.BOLD_OFF);
    addText('\n');

    // === INFO COMMANDE ===
    addCommand(ESC_POS.ALIGN_CENTER, ESC_POS.SIZE_DOUBLE, ESC_POS.BOLD_ON);
    addText('COMMANDE #' + order.id + '\n');
    addCommand(ESC_POS.SIZE_NORMAL, ESC_POS.BOLD_OFF);
    addText(new Date(order.created_at).toLocaleString('fr-FR') + '\n');
    addText('\n');
    addCommand(ESC_POS.ALIGN_LEFT);
    addText(ESC_POS.LINE);

    // === CLIENT ===
    addCommand(ESC_POS.BOLD_ON);
    addText('CLIENT:\n');
    addCommand(ESC_POS.BOLD_OFF);
    addText(order.customer_name + '\n');
    if (order.customer_phone) {
        addText('Tel: ' + order.customer_phone + '\n');
    }
    addText('\n');

    // === TYPE DE COMMANDE ===
    addText(ESC_POS.LINE);
    addCommand(ESC_POS.BOLD_ON);
    addText('TYPE: ');
    addCommand(ESC_POS.BOLD_OFF);

    if (order.notes && order.notes.includes('LIVRAISON')) {
        addText('LIVRAISON\n');
        if (order.delivery_address) {
            addText('Adresse: ' + order.delivery_address + '\n');
        }
    } else if (order.notes && order.notes.includes('SUR PLACE')) {
        addText('SUR PLACE\n');
        // Extraire salle et table des notes
        const salleMatch = order.notes.match(/Salle (Famille|Femme)/);
        const tableMatch = order.notes.match(/Table ([A-Z0-9]+)/i);
        if (salleMatch) {
            addText('Salle: ' + salleMatch[1] + '\n');
        }
        if (tableMatch) {
            addText('Table: ' + tableMatch[1] + '\n');
        }
    } else {
        addText('A EMPORTER\n');
    }

    // === ARTICLES ===
    addText('\n');
    addText(ESC_POS.LINE);
    addCommand(ESC_POS.BOLD_ON);
    addText('ARTICLES:\n');
    addCommand(ESC_POS.BOLD_OFF);
    addText('\n');

    order.items.forEach(item => {
        const qty = item.quantity || 1;
        const name = item.name;
        const price = (item.price || 0);
        const total = qty * price;

        addCommand(ESC_POS.BOLD_ON);
        addText(qty + 'x ' + name + '\n');
        addCommand(ESC_POS.BOLD_OFF);
        addText('   ' + price.toFixed(0) + ' DA x ' + qty + ' = ' + total.toFixed(0) + ' DA\n');

        // Options
        if (item.options && item.options.length > 0) {
            item.options.forEach(opt => {
                addText('   + ' + opt + '\n');
            });
        }
        addText('\n');
    });

    // === TOTAL ===
    addText(ESC_POS.LINE);
    addCommand(ESC_POS.SIZE_DOUBLE, ESC_POS.BOLD_ON);
    addText('TOTAL: ' + (order.total || 0).toFixed(0) + ' DA\n');
    addCommand(ESC_POS.SIZE_NORMAL, ESC_POS.BOLD_OFF);
    addText(ESC_POS.LINE);

    // === PAIEMENT ===
    const paymentMethod = !order.payment_method || order.payment_method === 'cash' ? 'Espèces' : 'Carte';
    addText('\n');
    addCommand(ESC_POS.BOLD_ON);
    addText('PAIEMENT: ');
    addCommand(ESC_POS.BOLD_OFF);
    addText(paymentMethod + '\n');

    // Info appoint/monnaie
    if (order.delivery_instructions && paymentMethod === 'Espèces') {
        if (order.delivery_instructions.includes('monnaie exacte')) {
            addText('Client a l\'appoint\n');
        } else {
            const changeMatch = order.delivery_instructions.match(/Monnaie pour (\d+) DA/);
            if (changeMatch) {
                addText('Monnaie pour: ' + changeMatch[1] + ' DA\n');
            }
        }
    }

    // === NOTES ===
    if (order.notes && !order.notes.includes('LIVRAISON') && !order.notes.includes('SUR PLACE')) {
        addText('\n');
        addText(ESC_POS.LINE);
        addCommand(ESC_POS.BOLD_ON);
        addText('NOTES:\n');
        addCommand(ESC_POS.BOLD_OFF);
        addText(order.notes + '\n');
    }

    // === FOOTER ===
    addText('\n');
    addCommand(ESC_POS.ALIGN_CENTER);
    addText('Merci de votre visite!\n');
    addText('\n');

    // Couper le papier
    addCommand(ESC_POS.FEED);
    addCommand(ESC_POS.CUT);

    return new Uint8Array(data);
}

// Fonction pour imprimer une commande
async function printOrder(orderId) {
    if (!isPrinterConnected) {
        alert('❌ Aucune imprimante connectée.\n\nConnectez d\'abord une imprimante dans les Réglages.');
        return;
    }

    try {
        // Récupérer les détails de la commande
        const order = allOrders.find(o => o.id === orderId);
        if (!order) {
            alert('❌ Commande non trouvée');
            return;
        }

        console.log('Impression de la commande:', orderId);

        // Créer les données du ticket
        const ticketData = createTicketData(order);

        // Envoyer à l'imprimante
        await sendToPrinter(ticketData);

        // Notification succès
        showNotification('✅ Ticket imprimé avec succès!', 'success');

    } catch (error) {
        console.error('Erreur impression:', error);
        alert('❌ Erreur lors de l\'impression:\n\n' + error.message);
    }
}

// Test d'impression
async function testPrint() {
    if (!isPrinterConnected) {
        alert('❌ Aucune imprimante connectée');
        return;
    }

    try {
        const data = [];
        const encoder = new TextEncoder();

        function addText(text) {
            data.push(...encoder.encode(text));
        }

        function addCommand(...commands) {
            commands.forEach(cmd => {
                if (Array.isArray(cmd)) {
                    data.push(...cmd);
                }
            });
        }

        addCommand(ESC_POS.INIT);
        addCommand(ESC_POS.ALIGN_CENTER);
        addCommand(ESC_POS.SIZE_DOUBLE, ESC_POS.BOLD_ON);
        addText('TEST D\'IMPRESSION\n');
        addCommand(ESC_POS.SIZE_NORMAL, ESC_POS.BOLD_OFF);
        addText('\n');
        addText('Imprimante connectee!\n');
        addText('\n');
        addText('<?php echo addslashes($restaurantName ?? 'Restaurant'); ?>\n');
        addText('\n');
        addText(new Date().toLocaleString('fr-FR') + '\n');
        addText('\n');
        addCommand(ESC_POS.FEED);
        addCommand(ESC_POS.CUT);

        await sendToPrinter(new Uint8Array(data));

        alert('✅ Test d\'impression envoyé!');

    } catch (error) {
        console.error('Erreur test impression:', error);
        alert('❌ Erreur lors du test:\n\n' + error.message);
    }
}

// Event listeners pour les boutons de gestion imprimante
document.getElementById('btn-connect-printer').addEventListener('click', connectPrinter);
document.getElementById('btn-disconnect-printer').addEventListener('click', disconnectPrinter);
document.getElementById('btn-test-print').addEventListener('click', testPrint);

// Restaurer l'état de connexion au chargement (optionnel)
window.addEventListener('load', () => {
    const wasConnected = localStorage.getItem('printerConnected') === 'true';
    const printerName = localStorage.getItem('printerName');

    if (wasConnected && printerName) {
        // Afficher l'état mais ne pas se reconnecter automatiquement
        // (la reconnexion auto nécessiterait de stocker l'ID du device, complexe)
        updatePrinterUI(false);
    }
});

// Helper pour notifications
function showNotification(message, type = 'info') {
    // Utiliser l'alert existant ou implémenter un toast si disponible
    console.log(message);
}

// ========================================
// 📖 TOOLTIP D'AIDE IMPRIMANTE
// ========================================

// Gérer l'affichage du tooltip d'aide
const printerHelpTooltip = document.querySelector('.printer-help-tooltip');
const printerHelpIcon = printerHelpTooltip?.querySelector('.fa-question-circle');
const printerHelpContent = printerHelpTooltip?.querySelector('.printer-help-content');
const printerHelpOverlay = printerHelpTooltip?.querySelector('.printer-help-overlay');

if (printerHelpIcon && printerHelpContent && printerHelpOverlay) {
    let helpVisible = false;

    function showHelp() {
        helpVisible = true;
        printerHelpOverlay.style.display = 'block';
        printerHelpContent.style.display = 'block';
    }

    function hideHelp() {
        helpVisible = false;
        printerHelpOverlay.style.display = 'none';
        printerHelpContent.style.display = 'none';
    }

    // Ouvrir au clic sur l'icône
    printerHelpIcon.addEventListener('click', (e) => {
        e.stopPropagation();
        showHelp();
    });

    // Fermer au clic sur l'overlay
    printerHelpOverlay.addEventListener('click', hideHelp);

    // Fermer au clic ailleurs
    document.addEventListener('click', (e) => {
        if (helpVisible && !printerHelpTooltip.contains(e.target) && !printerHelpContent.contains(e.target)) {
            hideHelp();
        }
    });

    // Empêcher la fermeture si on clique dans le tooltip
    printerHelpContent.addEventListener('click', (e) => {
        e.stopPropagation();
    });

    // Fermer avec la touche Escape
    document.addEventListener('keydown', (e) => {
        if (helpVisible && e.key === 'Escape') {
            hideHelp();
        }
    });
}

    </script>

</body>
</html>
