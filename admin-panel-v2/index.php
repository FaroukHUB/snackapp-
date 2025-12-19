<?php
/**
 * SnackApp v1 - Admin Dashboard
 * Support MySQL avec fallback JSON
 */

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
            if (isset($_POST['instagram'])) $settingsData['instagram'] = $_POST['instagram'];
            if (isset($_POST['facebook'])) $settingsData['facebook'] = $_POST['facebook'];

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
            if (isset($_POST['instagram'])) $restaurantSettings['social']['instagram'] = $_POST['instagram'];
            if (isset($_POST['facebook'])) $restaurantSettings['social']['facebook'] = $_POST['facebook'];

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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2>Clients (<?php echo count($customers); ?>)</h2>
                <div style="display: flex; gap: 8px;">
                    <a href="?export=customers" class="btn btn-sm btn-gray"><i class="fas fa-download"></i> CSV</a>
                    <button onclick="sendGroupWhatsApp()" class="btn btn-sm btn-whatsapp"><i class="fab fa-whatsapp"></i> Diffusion</button>
                </div>
            </div>

            <!-- Filtres rapides (scrollable sur mobile) -->
            <div class="customer-filters-wrap" style="margin-bottom: 15px;">
                <div style="display: flex; gap: 8px; flex-wrap: nowrap;">
                    <button onclick="filterCustomers('all')" class="btn btn-sm customer-filter active" data-filter="all" style="white-space: nowrap;"><i class="fas fa-users"></i> Tous</button>
                    <button onclick="filterCustomers('vip')" class="btn btn-sm customer-filter" data-filter="vip" style="background: rgba(245,158,11,.2); border-color: #f59e0b; white-space: nowrap;"><i class="fas fa-crown"></i> VIP</button>
                    <button onclick="filterCustomers('regular')" class="btn btn-sm customer-filter" data-filter="regular" style="background: rgba(16,185,129,.2); border-color: #10b981; white-space: nowrap;"><i class="fas fa-star"></i> Réguliers</button>
                    <button onclick="filterCustomers('new')" class="btn btn-sm customer-filter" data-filter="new" style="background: rgba(59,130,246,.2); border-color: #3b82f6; white-space: nowrap;"><i class="fas fa-seedling"></i> Nouveaux</button>
                </div>
            </div>

            <div class="card" id="whatsapp-panel" style="display: none; margin-bottom: 15px; border: 1px solid #25D366;">
                <h4 style="color: #25D366; margin-bottom: 10px;"><i class="fab fa-whatsapp"></i> Envoi groupé WhatsApp</h4>
                <div class="form-group">
                    <label>Message à envoyer</label>
                    <textarea id="wa-message" placeholder="Ex: Nouvelle promo ! -20% sur tous les burgers ce weekend..."></textarea>
                </div>
                <p style="color: #9ca3af; font-size: 12px; margin-bottom: 10px;">Sélectionnez les clients ci-dessous puis cliquez sur Envoyer</p>
                <button onclick="openWhatsAppLinks()" class="btn btn-whatsapp"><i class="fab fa-whatsapp"></i> Ouvrir WhatsApp pour chaque client</button>
            </div>

            <?php if (empty($customers)): ?>
                <div class="card" style="text-align: center; padding: 40px;"><p style="color: #9ca3af;">Aucun client</p></div>
            <?php else: ?>
                <?php
                // Trier les clients par nombre de commandes (décroissant)
                usort($customers, function($a, $b) {
                    return ($b['orders_count'] ?? 0) - ($a['orders_count'] ?? 0);
                });

                // Catégoriser les clients
                $vipClients = array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) >= 10);
                $regularClients = array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) >= 3 && ($c['orders_count'] ?? 0) < 10);
                $newClients = array_filter($customers, fn($c) => ($c['orders_count'] ?? 0) < 3);

                // Stats rapides
                $totalSpent = array_sum(array_column($customers, 'total_spent'));
                ?>

                <!-- Stats clients -->
                <div class="stats" style="margin-bottom: 20px;">
                    <div class="stat-card">
                        <div style="color: #f59e0b;"><i class="fas fa-crown"></i></div>
                        <div class="stat-number" style="font-size: 20px; color: #f59e0b;"><?php echo count($vipClients); ?></div>
                        <div style="color: #9ca3af; font-size: 11px;">VIP</div>
                    </div>
                    <div class="stat-card">
                        <div style="color: #10b981;"><i class="fas fa-star"></i></div>
                        <div class="stat-number" style="font-size: 20px; color: #10b981;"><?php echo count($regularClients); ?></div>
                        <div style="color: #9ca3af; font-size: 11px;">Réguliers</div>
                    </div>
                    <div class="stat-card">
                        <div style="color: <?php echo $primaryColor; ?>;"><i class="fas fa-euro-sign"></i></div>
                        <div class="stat-number" style="font-size: 20px;"><?php echo number_format($totalSpent, 0); ?>€</div>
                        <div style="color: #9ca3af; font-size: 11px;">CA Total</div>
                    </div>
                </div>

                <form id="customers-form">
                <?php foreach ($customers as $i => $customer):
                    $ordersCount = $customer['orders_count'] ?? 0;
                    $category = 'new';
                    $badge = '';
                    $badgeColor = '#3b82f6';

                    if ($ordersCount >= 10) {
                        $category = 'vip';
                        $badge = '👑 VIP';
                        $badgeColor = '#f59e0b';
                    } elseif ($ordersCount >= 3) {
                        $category = 'regular';
                        $badge = '⭐ Régulier';
                        $badgeColor = '#10b981';
                    } else {
                        $badge = '🌱 Nouveau';
                        $badgeColor = '#3b82f6';
                    }
                ?>
                    <label class="checkbox-item customer-item" data-category="<?php echo $category; ?>" style="border-left: 3px solid <?php echo $badgeColor; ?>;">
                        <input type="checkbox" name="selected[]" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" class="customer-checkbox">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <strong style="font-size: 14px;"><?php echo htmlspecialchars($customer['name'] ?? 'Client'); ?></strong>
                                <span style="font-size: 10px; padding: 2px 6px; border-radius: 10px; background: <?php echo $badgeColor; ?>20; color: <?php echo $badgeColor; ?>;"><?php echo $badge; ?></span>
                            </div>
                            <div style="color: #9ca3af; font-size: 12px; margin-top: 4px;">
                                <i class="fas fa-phone" style="font-size: 10px;"></i> <?php echo htmlspecialchars($customer['phone'] ?? ''); ?>
                                <?php if (!empty($customer['last_order'])): ?>
                                • <i class="fas fa-clock" style="font-size: 10px;"></i> <?php echo date('d/m/Y', strtotime($customer['last_order'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right; white-space: nowrap;">
                            <div style="font-weight: bold; color: <?php echo $primaryColor; ?>;"><?php echo number_format($customer['total_spent'] ?? 0, 2); ?>€</div>
                            <div style="color: #9ca3af; font-size: 11px;"><?php echo $ordersCount; ?> commande<?php echo $ordersCount > 1 ? 's' : ''; ?></div>
                        </div>
                    </label>
                <?php endforeach; ?>
                </form>
            <?php endif; ?>
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
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($restaurantSettings['contact']['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp (numéro sans +)</label>
                        <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($restaurantSettings['contact']['whatsappOrdersNumber'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="text" name="instagram" value="<?php echo htmlspecialchars($restaurantSettings['social']['instagram'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Facebook</label>
                        <input type="text" name="facebook" value="<?php echo htmlspecialchars($restaurantSettings['social']['facebook'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn"><i class="fas fa-save"></i> Enregistrer</button>
                </form>
            </div>

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
                        <button type="button" onclick="toggleTokenVisibility()" class="btn btn-sm btn-ghost" style="margin-top: 8px;">
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
                            Ne le partagez jamais publiquement.
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
        <button class="nav-btn" data-section="archives"><i class="fas fa-archive"></i><div>Archives</div></button>
        <button class="nav-btn" onclick="window.location.href='products-manager.php'" data-section="products"><i class="fas fa-burger"></i><div>Menu</div></button>
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
    </script>
</body>
</html>
