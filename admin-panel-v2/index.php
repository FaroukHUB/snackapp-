<?php
/**
 * SnackApp v1 - Admin Dashboard
 * Support MySQL avec fallback JSON
 */


require_once __DIR__ . '/../database/repositories/LoyaltyRepository.php';
require_once __DIR__ . '/bootstrap.php';
requireAdmin();

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

// ============================================
// CHARGER LES DONNÉES
// ============================================

if ($useMySQL) {
    // Mode MySQL
    $restaurant = getCurrentRestaurant();
    $restaurantName = $restaurant['name'] ?? 'Restaurant';
    $primaryColor = $restaurant['primary_color'] ?? '#c58a3a';

    $restaurantSettings = RestaurantRepository::getPublicData(SNACK_RESTAURANT_ID);
    $orders = OrderRepository::getActiveOrders(SNACK_RESTAURANT_ID, 50);
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

    // Menu categories
    $menuData = MenuRepository::getFullMenu(SNACK_RESTAURANT_ID);
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
                OrderRepository::updateStatus($order['id'], $_POST['new_status']);
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
        // Parser les réseaux sociaux dynamiques
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
            // Horaires
            if (isset($_POST['hours'])) {
                $hours = [];
                foreach ($_POST['hours'] as $i => $h) {
                    $hours[] = [
                        'opens' => $h['opens'] ?? '18:30',
                        'closes' => $h['closes'] ?? '23:30'
                    ];
                }
                RestaurantRepository::updateOpeningHours(SNACK_RESTAURANT_ID, $hours);
            }

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
        } else {
            // JSON mode
            if (isset($_POST['hours'])) {
                $restaurantSettings['openingHours'] = [];
                $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                foreach ($days as $i => $day) {
                    $restaurantSettings['openingHours'][] = [
                        'day' => $day,
                        'opens' => $_POST['hours'][$i]['opens'] ?? '18:30',
                        'closes' => $_POST['hours'][$i]['closes'] ?? '23:30'
                    ];
                }
            }
            if (isset($_POST['phone'])) $restaurantSettings['contact']['phone'] = $_POST['phone'];
            if (isset($_POST['whatsapp'])) $restaurantSettings['contact']['whatsappOrdersNumber'] = $_POST['whatsapp'];
            $restaurantSettings['contact']['extra_phones'] = $extraPhones;
            $restaurantSettings['social']['instagram'] = $socials['instagram'];
            $restaurantSettings['social']['facebook'] = $socials['facebook'];
            $restaurantSettings['social']['tiktok'] = $socials['tiktok'];
            $restaurantSettings['social']['snapchat'] = $socials['snapchat'];
            $restaurantSettings['social']['extra'] = $socials['extra'];

            file_put_contents(__DIR__ . '/../config/restaurant.json', json_encode($restaurantSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
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
        } else {
            $restaurantSettings['faq']['items'] = $faqItemsNew;
            file_put_contents(__DIR__ . '/../config/restaurant.json', json_encode($restaurantSettings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
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
        header('Content-Disposition: attachment; filename=commandes_archivees_' . date('Y-m-d') . '.csv');
        echo "\xEF\xBB\xBF";
        echo "ID,Date,Client,Téléphone,Total,Statut,Produits\n";
        foreach ($archivedOrders as $o) {
            $items = array_map(fn($i) => ($i['quantity'] ?? 1) . 'x ' . ($i['name'] ?? $i['product_name'] ?? ''), $o['items'] ?? []);
            echo '"' . ($o['id'] ?? $o['order_number'] ?? '') . '","' . ($o['created_at'] ?? '') . '","' . ($o['customer_name'] ?? '') . '","' . ($o['customer_phone'] ?? '') . '",' . ($o['total'] ?? 0) . ',"' . ($o['status'] ?? '') . '","' . implode('; ', $items) . "\"\n";
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
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #1a1a2e; color: #fff; padding-bottom: 80px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: #2a2a3e; padding: 15px 20px; margin-bottom: 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .header h1 { font-size: 20px; }
        .btn { background: <?php echo $primaryColor; ?>; color: white; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 14px; }
        .btn:hover { opacity: 0.9; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-green { background: #10b981; }
        .btn-gray { background: #374151; }
        .btn-whatsapp { background: #25D366; }
        .card { background: #2a2a3e; padding: 20px; margin-bottom: 15px; border-radius: 12px; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-card { background: #2a2a3e; padding: 15px; text-align: center; border-radius: 12px; }
        .stat-number { font-size: 28px; font-weight: bold; margin-top: 5px; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: #2a2a3e; border-top: 1px solid #444; display: flex; justify-content: space-around; padding: 8px 0; z-index: 1000; }
        .nav-btn { flex: 1; text-align: center; padding: 6px; background: none; border: none; color: #999; cursor: pointer; font-size: 11px; }
        .nav-btn.active { color: <?php echo $primaryColor; ?>; }
        .nav-btn i { display: block; font-size: 18px; margin-bottom: 2px; }
        .section { display: none; }
        .section.active { display: block; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #9ca3af; font-size: 13px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #444; border-radius: 8px; background: #1e293b; color: white; font-size: 14px; }
        .form-group textarea { min-height: 80px; resize: vertical; }
        .hours-grid { display: grid; grid-template-columns: 100px 1fr 1fr; gap: 8px; align-items: center; margin-bottom: 8px; }
        .hours-grid span { color: #9ca3af; }
        .checkbox-item { display: flex; align-items: center; gap: 10px; padding: 10px; background: #1e293b; border-radius: 8px; margin-bottom: 8px; cursor: pointer; }
        .checkbox-item input { width: 18px; height: 18px; }
        /* ===== RESPONSIVE MOBILE ===== */
        @media (max-width: 768px) {
            .stats { grid-template-columns: repeat(2, 1fr); }
            .hours-grid { grid-template-columns: 1fr; gap: 6px; }
            .hours-grid span { font-weight: bold; margin-bottom: 4px; }
        }

        @media (max-width: 480px) {
            body { padding-bottom: 70px; }
            .container { padding: 10px; }

            /* Header mobile */
            .header {
                padding: 12px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .header h1 { font-size: 18px; }
            .header > div:last-child {
                width: 100%;
                justify-content: flex-end;
            }

            /* Boutons */
            .btn { padding: 10px 14px; font-size: 13px; }
            .btn-sm { padding: 8px 10px; font-size: 11px; }

            /* Stats */
            .stats { grid-template-columns: 1fr 1fr; gap: 8px; }
            .stat-card { padding: 12px 8px; }
            .stat-number { font-size: 22px; }

            /* Cards */
            .card { padding: 14px; margin-bottom: 12px; border-radius: 10px; }

            /* Navigation basse */
            .bottom-nav { padding: 6px 0 8px; }
            .nav-btn { padding: 4px 2px; font-size: 9px; }
            .nav-btn i { font-size: 16px; margin-bottom: 2px; }
            .nav-btn div { font-size: 9px; }

            /* Commandes */
            .card > div:first-child { flex-direction: column; gap: 8px; }
            .card > div:first-child > div:last-child { text-align: left !important; }

            /* Filtres clients - scroll horizontal */
            .customer-filters-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 8px;
                margin: 0 -10px;
                padding: 0 10px 8px;
            }
            .customer-filters-wrap > div {
                display: flex;
                gap: 6px;
                min-width: max-content;
            }

            /* Formulaires */
            .form-group input, .form-group textarea, .form-group select {
                padding: 12px;
                font-size: 16px; /* évite le zoom sur iOS */
            }

            /* Horaires */
            .hours-grid {
                background: #1e293b;
                padding: 10px;
                border-radius: 8px;
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
        <div id="section-orders" class="section active">
            <div class="stats">
                <div class="stat-card"><div style="color: #9ca3af; font-size: 12px;">En attente</div><div class="stat-number" style="color: #fdba74;"><?php echo $stats['pending']; ?></div></div>
                <div class="stat-card"><div style="color: #9ca3af; font-size: 12px;">Terminées</div><div class="stat-number" style="color: #86efac;"><?php echo $stats['completed']; ?></div></div>
                <div class="stat-card"><div style="color: #9ca3af; font-size: 12px;">Aujourd'hui</div><div class="stat-number"><?php echo $stats['today']; ?></div></div>
            </div>

            <?php if (!empty($orders)): ?>
            <form method="POST" style="margin-bottom: 15px;">
                <input type="hidden" name="action" value="clear_orders">
                <button type="submit" onclick="return confirm('Archiver toutes les commandes ?')" class="btn btn-gray" style="width: 100%;"><i class="fas fa-archive"></i> Archiver tout</button>
            </form>
            <?php endif; ?>

            <?php if (empty($orders)): ?>
                <div class="card" style="text-align: center; padding: 40px;"><i class="fas fa-inbox" style="font-size: 48px; color: #555;"></i><p style="color: #9ca3af; margin-top: 10px;">Aucune commande</p></div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                        <div>
                            <strong style="font-size: 16px;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Client'); ?></strong>
                            <br><span style="color: #9ca3af; font-size: 12px;"><?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?></span>
                            <br><span style="color: #6b7280; font-size: 11px;">#<?php echo htmlspecialchars($order['id']); ?></span>
                        </div>
                        <div style="text-align: right;"><strong style="color: <?php echo $primaryColor; ?>;"><?php echo number_format($order['total'] ?? 0, 2); ?>€</strong><br><span style="color: #9ca3af; font-size: 12px;"><?php echo date('d/m H:i', strtotime($order['created_at'])); ?></span></div>
                    </div>
                    <div style="margin-bottom: 12px; font-size: 13px; color: #d1d5db;">
                        <?php foreach ($order['items'] ?? [] as $item): ?>
                            <?php echo ($item['quantity'] ?? 1) . 'x ' . htmlspecialchars($item['name']); ?><?php if (!empty($item['supplements'])): ?> <span style="color:#9ca3af;">(+<?php echo implode(', ', array_column($item['supplements'], 'name')); ?>)</span><?php endif; ?><br>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($order['notes'])): ?><p style="color: #9ca3af; font-size: 12px; margin-bottom: 12px;"><i class="fas fa-comment"></i> <?php echo htmlspecialchars($order['notes']); ?></p><?php endif; ?>

                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php if (($order['status'] ?? '') !== 'completed'): ?>
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="action" value="change_status">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                            <button type="submit" name="new_status" value="completed" class="btn btn-green" style="width: 100%;"><i class="fas fa-check"></i> Terminée</button>
                        </form>
                        <?php else: ?>
                        <div style="flex: 1; text-align: center; padding: 10px; background: rgba(134,239,172,0.1); border-radius: 8px; color: #86efac;"><i class="fas fa-check-circle"></i> Terminée</div>
                        <?php endif; ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['customer_phone'] ?? ''); ?>?text=<?php echo urlencode('Bonjour ! Votre commande #' . $order['id'] . ' est prête !'); ?>" target="_blank" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

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
                <h2><i class="fas fa-users"></i> Clients (<?= $totalCustomers ?>)</h2>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
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

            <!-- Grille des clients -->
            <div class="customers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px;">
                <?php if (empty($customers)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #666;">
                        <div style="font-size: 4em; margin-bottom: 20px;"><i class="fas fa-users" style="color: #555;"></i></div>
                        <p style="font-size: 1.2em; color: #9ca3af;">Aucun client enregistré</p>
                        <p style="color: #6b7280;">Les clients apparaîtront ici après leur première commande</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($customers as $customer):
                        $ordersCount = $customer['orders_count'] ?? 0;
                        $totalSpent = $customer['total_spent'] ?? 0;

                        // Déterminer la catégorie
                        if ($ordersCount <= 1) {
                            $category = 'new';
                            $badge = '<span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px;"><i class="fas fa-seedling"></i> Nouveau</span>';
                        } elseif ($totalSpent >= 200 || $ordersCount >= 10) {
                            $category = 'vip';
                            $badge = '<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px;"><i class="fas fa-crown"></i> VIP</span>';
                        } else {
                            $category = 'regular';
                            $badge = '<span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 10px; font-size: 10px;"><i class="fas fa-star"></i> Régulier</span>';
                        }

                        // Points fidélité
                        $loyaltyPoints = $customer['loyalty_points'] ?? 0;
                    ?>
                    <div class="card customer-item" id="customer-<?= $customer['id'] ?>" data-category="<?= $category ?>" style="position: relative; padding: 15px;">
                        <!-- Checkbox pour diffusion -->
                        <label class="broadcast-checkbox" style="position: absolute; top: 10px; left: 10px; cursor: pointer; display: none;">
                            <input type="checkbox" class="customer-checkbox" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" data-customer-id="<?= $customer['id'] ?>" onchange="updateSelectedCount()" style="width: 18px; height: 18px;">
                        </label>

                        <!-- Bouton supprimer -->
                        <button onclick="deleteCustomer(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'] ?? 'Client', ENT_QUOTES) ?>')"
                                style="position: absolute; top: 10px; right: 10px; background: #ff4757; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;"
                                title="Supprimer ce client">
                            <i class="fas fa-times"></i>
                        </button>

                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; margin-top: 5px;">
                            <!-- Avatar -->
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, <?= $category === 'vip' ? '#f59e0b, #d97706' : ($category === 'regular' ? '#10b981, #059669' : '#3b82f6, #2563eb') ?>); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; color: white;">
                                <?= strtoupper(substr($customer['name'] ?? 'C', 0, 1)) ?>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 4px 0; font-size: 15px;"><?= htmlspecialchars($customer['name'] ?? 'Client') ?></h4>
                                <p style="margin: 0; color: #9ca3af; font-size: 12px;"><i class="fas fa-phone"></i> <?= htmlspecialchars($customer['phone'] ?? 'N/A') ?></p>
                            </div>
                        </div>

                        <!-- Badge catégorie -->
                        <div style="margin-bottom: 12px;"><?= $badge ?></div>

                        <!-- Stats -->
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; text-align: center; background: #1e293b; padding: 10px; border-radius: 8px;">
                            <div>
                                <div style="font-size: 16px; font-weight: bold; color: <?= $primaryColor ?>;"><?= $ordersCount ?></div>
                                <div style="font-size: 10px; color: #6b7280;">Commandes</div>
                            </div>
                            <div>
                                <div style="font-size: 16px; font-weight: bold; color: #10b981;"><?= number_format($totalSpent, 0) ?>€</div>
                                <div style="font-size: 10px; color: #6b7280;">Dépensé</div>
                            </div>
                            <div>
                                <div style="font-size: 16px; font-weight: bold; color: #f59e0b;"><?= $loyaltyPoints ?></div>
                                <div style="font-size: 10px; color: #6b7280;">Points</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div style="display: flex; gap: 8px; margin-top: 12px;">
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $customer['phone'] ?? '') ?>" target="_blank" class="btn btn-sm btn-whatsapp" style="flex: 1; justify-content: center;"><i class="fab fa-whatsapp"></i></a>
                            <button onclick="openAddPointsModal(<?= $customer['id'] ?>, '<?= htmlspecialchars($customer['name'] ?? 'Client', ENT_QUOTES) ?>')" class="btn btn-sm" style="flex: 1; background: #f59e0b;"><i class="fas fa-plus"></i> Points</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
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
                    <option value="discount_amount">Réduction en €</option>
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

        <!-- ARCHIVES -->
        <div id="section-archives" class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2>Archives (<?php echo count($archivedOrders); ?>)</h2>
                <a href="?export=archives" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> Export CSV</a>
            </div>

            <?php if (empty($archivedOrders)): ?>
                <div class="card" style="text-align: center; padding: 40px;">
                    <i class="fas fa-archive" style="font-size: 48px; color: #555; margin-bottom: 15px;"></i>
                    <p style="color: #9ca3af;">Aucune commande archivée</p>
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
                    <div class="stat-card">
                        <div style="color: #9ca3af;"><i class="fas fa-receipt"></i></div>
                        <div class="stat-number" style="font-size: 22px;"><?php echo $archiveCount; ?></div>
                        <div style="color: #9ca3af; font-size: 11px;">Commandes</div>
                    </div>
                    <div class="stat-card">
                        <div style="color: <?php echo $primaryColor; ?>;"><i class="fas fa-euro-sign"></i></div>
                        <div class="stat-number" style="font-size: 22px;"><?php echo number_format($archiveTotal, 0); ?>€</div>
                        <div style="color: #9ca3af; font-size: 11px;">CA Total</div>
                    </div>
                    <div class="stat-card">
                        <div style="color: #10b981;"><i class="fas fa-calculator"></i></div>
                        <div class="stat-number" style="font-size: 22px;"><?php echo $archiveCount > 0 ? number_format($archiveTotal / $archiveCount, 1) : 0; ?>€</div>
                        <div style="color: #9ca3af; font-size: 11px;">Panier moyen</div>
                    </div>
                </div>

                <!-- Liste groupée par date -->
                <?php foreach ($groupedByDate as $date => $orders):
                    $dateLabel = date('d/m/Y', strtotime($date));
                    $dayName = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'][date('w', strtotime($date))];
                    $dayTotal = array_sum(array_column($orders, 'total'));

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
                <div class="card" style="margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #374151; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 14px;"><?php echo $dateLabel; ?></strong>
                            <span style="color: #9ca3af; font-size: 12px; margin-left: 8px;"><?php echo count($orders); ?> commande<?php echo count($orders) > 1 ? 's' : ''; ?></span>
                        </div>
                        <span style="color: <?php echo $primaryColor; ?>; font-weight: bold;"><?php echo number_format($dayTotal, 2); ?>€</span>
                    </div>

                    <?php foreach (array_slice($orders, 0, 10) as $idx => $order): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; <?php echo $idx < count($orders) - 1 ? 'border-bottom: 1px solid #2a2a3e;' : ''; ?>">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <strong style="font-size: 13px;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Client'); ?></strong>
                                <span style="color: #6b7280; font-size: 10px;">#<?php echo htmlspecialchars(substr($order['id'], -6)); ?></span>
                            </div>
                            <div style="color: #9ca3af; font-size: 11px; margin-top: 2px;">
                                <i class="fas fa-clock" style="font-size: 9px;"></i> <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                <?php if (!empty($order['items'])): ?>
                                • <?php echo count($order['items']); ?> article<?php echo count($order['items']) > 1 ? 's' : ''; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-weight: bold; color: <?php echo $primaryColor; ?>;"><?php echo number_format($order['total'] ?? 0, 2); ?>€</div>
                            <div style="font-size: 10px; color: #10b981;"><i class="fas fa-check"></i> Terminée</div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (count($orders) > 10): ?>
                    <div style="text-align: center; padding-top: 10px; color: #9ca3af; font-size: 12px;">
                        ... et <?php echo count($orders) - 10; ?> autre<?php echo (count($orders) - 10) > 1 ? 's' : ''; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- RÉGLAGES -->
        <div id="section-settings" class="section">
            <h2 style="margin-bottom: 20px;">Réglages</h2>

            <!-- Horaires -->
            <div class="card">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-clock"></i> Horaires d'ouverture</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <?php
                    $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                    $hours = $restaurantSettings['openingHours'] ?? [];
                    foreach ($days as $i => $day):
                        $h = $hours[$i] ?? ['opens' => '18:30', 'closes' => '23:30'];
                    ?>
                    <div class="hours-grid">
                        <span><?php echo $day; ?></span>
                        <input type="time" name="hours[<?php echo $i; ?>][opens]" value="<?php echo $h['opens']; ?>">
                        <input type="time" name="hours[<?php echo $i; ?>][closes]" value="<?php echo $h['closes']; ?>">
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn" style="margin-top: 15px;"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>

                        <!-- Contact -->
            <div class="card">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-phone"></i> Contact & Réseaux</h3>
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
            <div class="card">
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
            <div class="card">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-question-circle"></i> FAQ</h3>
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
            <div class="card">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-utensils"></i> Gestion des produits</h3>
                <a href="products-manager.php" class="btn btn-green"><i class="fas fa-cog"></i> Gérer le menu</a>
            </div>
        </div>

        <!-- STATS -->
        <div id="section-stats" class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2><i class="fas fa-chart-line"></i> Statistiques</h2>
                <div style="display: flex; gap: 8px;">
                    <a href="?export=stats&period=week" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> CSV Semaine</a>
                    <a href="?export=stats&period=month" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> CSV Mois</a>
                </div>
            </div>

            <!-- CA Cards -->
            <div class="stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
                <div class="stat-card">
                    <div style="color: #9ca3af; font-size: 11px;"><i class="fas fa-calendar-day"></i> Aujourd'hui</div>
                    <div class="stat-number" style="color: #10b981; font-size: 24px;"><?php echo number_format($stats['revenue'] ?? 0, 0); ?>€</div>
                    <div style="color: #6b7280; font-size: 11px;"><?php echo $stats['today'] ?? 0; ?> cmd</div>
                </div>
                <div class="stat-card">
                    <div style="color: #9ca3af; font-size: 11px;"><i class="fas fa-calendar-week"></i> Semaine</div>
                    <div class="stat-number" style="color: #3b82f6; font-size: 24px;"><?php echo number_format($weekStats['revenue'] ?? 0, 0); ?>€</div>
                    <div style="color: #6b7280; font-size: 11px;"><?php echo $weekStats['orders'] ?? 0; ?> cmd</div>
                </div>
                <div class="stat-card">
                    <div style="color: #9ca3af; font-size: 11px;"><i class="fas fa-calendar-alt"></i> Mois</div>
                    <div class="stat-number" style="color: #8b5cf6; font-size: 24px;"><?php echo number_format($monthStats['revenue'] ?? 0, 0); ?>€</div>
                    <div style="color: #6b7280; font-size: 11px;"><?php echo $monthStats['orders'] ?? 0; ?> cmd</div>
                </div>
            </div>

            <!-- Panier moyen & Heure de pic -->
            <div class="stats" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 20px;">
                <div class="stat-card">
                    <div style="color: #9ca3af; font-size: 11px;"><i class="fas fa-shopping-basket"></i> Panier moyen</div>
                    <div class="stat-number" style="font-size: 22px;"><?php echo number_format($monthStats['avg_order'] ?? 0, 1); ?>€</div>
                </div>
                <div class="stat-card">
                    <div style="color: #9ca3af; font-size: 11px;"><i class="fas fa-clock"></i> Heure de pic</div>
                    <div class="stat-number" style="font-size: 22px;"><?php echo $stats['peak_hour'] ?? '--:--'; ?></div>
                </div>
            </div>

            <!-- Top Produits -->
            <div class="card">
                <h3 style="margin-bottom: 15px; font-size: 14px;"><i class="fas fa-trophy" style="color: #f59e0b;"></i> Top 5 Produits (30j)</h3>
                <?php if (empty($topProducts)): ?>
                    <p style="color: #6b7280; font-size: 13px;">Aucune donnée</p>
                <?php else: ?>
                    <?php foreach ($topProducts as $i => $product): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; <?php echo $i < count($topProducts) - 1 ? 'border-bottom: 1px solid #374151;' : ''; ?>">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="width: 24px; height: 24px; background: <?php echo $i === 0 ? '#f59e0b' : ($i === 1 ? '#9ca3af' : ($i === 2 ? '#cd7f32' : '#374151')); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;"><?php echo $i + 1; ?></span>
                            <span style="font-size: 13px;"><?php echo htmlspecialchars($product['name']); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="color: <?php echo $primaryColor; ?>; font-weight: bold;"><?php echo $product['qty']; ?> vendus</span>
                            <div style="color: #6b7280; font-size: 11px;"><?php echo number_format($product['revenue'], 0); ?>€</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Heures de pic -->
            <div class="card" style="margin-top: 15px;">
                <h3 style="margin-bottom: 15px; font-size: 14px;"><i class="fas fa-chart-bar" style="color: #3b82f6;"></i> Répartition par heure (30j)</h3>
                <?php
                $maxCount = max(array_column($peakHours, 'count') ?: [1]);
                ?>
                <div style="display: flex; align-items: flex-end; gap: 4px; height: 100px;">
                    <?php for ($h = 11; $h <= 23; $h++):
                        $hourData = array_filter($peakHours, fn($p) => (int)$p['hour'] === $h);
                        $count = !empty($hourData) ? array_values($hourData)[0]['count'] : 0;
                        $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                    ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 100%; background: <?php echo $count === $maxCount && $count > 0 ? '#f59e0b' : '#3b82f6'; ?>; height: <?php echo max($height, 2); ?>px; border-radius: 4px 4px 0 0; min-height: 2px;"></div>
                        <span style="font-size: 9px; color: #6b7280; margin-top: 4px;"><?php echo $h; ?>h</span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- CA 7 derniers jours -->
            <div class="card" style="margin-top: 15px;">
                <h3 style="margin-bottom: 15px; font-size: 14px;"><i class="fas fa-chart-area" style="color: #10b981;"></i> CA des 7 derniers jours</h3>
                <?php
                $maxRevenue = max(array_column($dailyRevenue, 'revenue') ?: [1]);
                $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
                ?>
                <div style="display: flex; align-items: flex-end; gap: 8px; height: 120px;">
                    <?php foreach ($dailyRevenue as $day):
                        $height = $maxRevenue > 0 ? ($day['revenue'] / $maxRevenue) * 100 : 0;
                        $dayName = $days[date('w', strtotime($day['date']))];
                        $isToday = $day['date'] === date('Y-m-d');
                    ?>
                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                        <span style="font-size: 10px; color: #9ca3af; margin-bottom: 4px;"><?php echo number_format($day['revenue'], 0); ?>€</span>
                        <div style="width: 100%; background: <?php echo $isToday ? '#10b981' : '#374151'; ?>; height: <?php echo max($height, 4); ?>px; border-radius: 4px 4px 0 0;"></div>
                        <span style="font-size: 10px; color: <?php echo $isToday ? '#10b981' : '#6b7280'; ?>; margin-top: 4px; font-weight: <?php echo $isToday ? 'bold' : 'normal'; ?>;"><?php echo $dayName; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- FIDÉLITÉ -->
        <div id="section-loyalty" class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2><i class="fas fa-gift" style="color: #f59e0b;"></i> Programme Fidélité</h2>
                <button onclick="openAddRewardModal()" class="btn" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);"><i class="fas fa-plus"></i> Nouvelle récompense</button>
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
                        <label style="color: #9ca3af;">Points par euro :</label>
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
                        <div style="width: 150px; height: 150px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            <i class="fas fa-qrcode" style="font-size: 48px;"></i>
                        </div>
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
                                'discount_amount' => 'Réduction €',
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
                                            : <?= $reward['reward_value'] ?><?= $reward['reward_type'] === 'discount_percent' ? '%' : '€' ?>
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
                        ?>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #1e293b; border-radius: 8px;">
                            <div style="width: 32px; height: 32px; background: <?= $medalColor ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                <?= $i + 1 ?>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($member['customer_name'] ?? 'Client') ?></div>
                                <div style="color: #6b7280; font-size: 11px;"><?= htmlspecialchars($member['customer_phone'] ?? '') ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: bold; color: #f59e0b;"><?= number_format($member['total_points'] ?? 0) ?> pts</div>
                                <div style="font-size: 11px; color: #6b7280;"><?= $member['rewards_redeemed'] ?? 0 ?> récompenses</div>
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
                <a href="products-manager.php" class="btn btn-green"><i class="fas fa-cog"></i> Gérer</a>
            </div>
            <?php foreach ($products as $category): ?>
            <div class="card">
                <h3 style="margin-bottom: 10px; color: <?php echo $primaryColor; ?>;"><?php echo htmlspecialchars($category['name'] ?? ''); ?></h3>
                <?php foreach ($category['items'] ?? [] as $item): ?>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #374151;">
                    <span><?php echo htmlspecialchars($item['name'] ?? ''); ?></span>
                    <span style="color: <?php echo $primaryColor; ?>;"><?php echo number_format($item['priceSolo'] ?? $item['price'] ?? 0, 2); ?>€</span>
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

    <script src="notification-sound.js"></script>
    <script>
        // Navigation
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.getElementById('section-' + btn.dataset.section).classList.add('active');
                window.scrollTo(0, 0);
            });
        });

        // Hash navigation
        if (window.location.hash === '#settings') {
            document.querySelector('[data-section="settings"]').click();
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
            const items = document.querySelectorAll('.customer-item');
            const filters = document.querySelectorAll('.customer-filter');

            // Mettre à jour les boutons de filtre
            filters.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.filter === category) {
                    btn.classList.add('active');
                }
            });

            // Filtrer les clients
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
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



        // Notifications
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
    
    const formData = new FormData();
    formData.append('action', 'delete_customer');
    formData.append('customer_id', customerId);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('customer-' + customerId);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.8)';
                setTimeout(() => card.remove(), 300);
            }
        } else {
            alert('Erreur: ' + (data.error || 'Impossible de supprimer le client'));
        }
    })
    .catch(error => {
        alert('Erreur de connexion');
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
    const checkboxes = document.querySelectorAll('.broadcast-checkbox');

    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        checkboxes.forEach(cb => cb.style.display = 'block');
    } else {
        panel.style.display = 'none';
        checkboxes.forEach(cb => cb.style.display = 'none');
        deselectAllCustomers();
    }
}

function selectAllCustomers() {
    document.querySelectorAll('.customer-checkbox').forEach(cb => {
        if (cb.closest('.customer-item').style.display !== 'none') {
            cb.checked = true;
        }
    });
    updateSelectedCount();
}

function deselectAllCustomers() {
    document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = false);
    updateSelectedCount();
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


    </script>
</body>
</html>
