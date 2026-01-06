<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Récupérer le nom du restaurant depuis le config
$config = loadConfig();
$restaurantName = $config['restaurant']['name'] ?? 'Restaurant';

// Charger les commandes
$orders = loadData('orders.json') ?? [];

// Traiter les actions (changement de statut)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status' && isset($_POST['order_id']) && isset($_POST['new_status'])) {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['new_status'];

        foreach ($orders as &$order) {
            if ($order['id'] === $orderId) {
                $order['status'] = $newStatus;
                if ($newStatus === 'ready') {
                    $order['ready_at'] = date('Y-m-d H:i:s');
                }
                break;
            }
        }

        saveData('orders.json', $orders);

        // Rediriger pour éviter re-soumission du formulaire
        header('Location: index-zero.php');
        exit;
    }
}

// Trier par date décroissante
usort($orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Limiter à 20 commandes max pour la performance
$orders = array_slice($orders, 0, 20);

// Calculer les stats
$today = date('Y-m-d');
$stats = [
    'received' => 0,
    'preparing' => 0,
    'ready' => 0,
    'today' => 0
];

foreach ($orders as $order) {
    if (strpos($order['created_at'], $today) === 0) {
        $stats['today']++;
    }
    if (isset($stats[$order['status']])) {
        $stats[$order['status']]++;
    }
}

function getStatusLabel($status) {
    $labels = [
        'received' => 'Reçue',
        'preparing' => 'En préparation',
        'ready' => 'Prête',
        'delivered' => 'Livrée'
    ];
    return $labels[$status] ?? $status;
}

function getStatusColor($status) {
    $colors = [
        'received' => '#1e3a8a',
        'preparing' => '#7c2d12',
        'ready' => '#14532d',
        'delivered' => '#374151'
    ];
    return $colors[$status] ?? '#374151';
}

function getStatusTextColor($status) {
    $colors = [
        'received' => '#93c5fd',
        'preparing' => '#fdba74',
        'ready' => '#86efac',
        'delivered' => '#9ca3af'
    ];
    return $colors[$status] ?? '#9ca3af';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($restaurantName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: #2a2a3e;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            background: #c58a3a;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #d49a4a;
        }

        .card {
            background: #2a2a3e;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .order-id {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .order-price {
            font-size: 24px;
            color: #c58a3a;
            margin-bottom: 10px;
        }

        .status-btn {
            padding: 8px 15px;
            margin: 5px 5px 5px 0;
            border: 2px solid transparent;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #2a2a3e;
            padding: 15px;
            text-align: center;
            border-radius: 8px;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-top: 5px;
        }

        .stat-label {
            color: #9ca3af;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><?php echo htmlspecialchars($restaurantName); ?></h1>
                <p style="color: #9ca3af;">Administration - Version PHP Pure</p>
            </div>
            <div>
                <a href="index-zero.php" class="btn">Actualiser</a>
                <a href="logout.php" class="btn">Déconnexion</a>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Reçues</div>
                <div class="stat-number" style="color: #93c5fd;"><?php echo $stats['received']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">En préparation</div>
                <div class="stat-number" style="color: #fdba74;"><?php echo $stats['preparing']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Prêtes</div>
                <div class="stat-number" style="color: #86efac;"><?php echo $stats['ready']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Aujourd'hui</div>
                <div class="stat-number"><?php echo $stats['today']; ?></div>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <p style="color: #9ca3af;">Aucune commande</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                        <div>
                            <div class="order-id"><?php echo htmlspecialchars($order['id']); ?></div>
                            <div style="color: #9ca3af; font-size: 14px;">
                                <?php echo htmlspecialchars($order['customer_phone']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-price"><?php echo number_format($order['total'], 2); ?>€</div>
                            <div style="color: #9ca3af; font-size: 14px;">
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <?php foreach ($order['items'] as $item): ?>
                            <div style="color: #d1d5db; margin-bottom: 5px;">
                                <?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['name']); ?>
                                <?php if (!empty($item['supplements'])): ?>
                                    <span style="color: #9ca3af;">
                                        (+ <?php echo implode(', ', array_column($item['supplements'], 'name')); ?>)
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($order['notes'])): ?>
                        <div style="color: #9ca3af; margin-bottom: 15px; font-size: 14px;">
                            <strong>Note:</strong> <?php echo htmlspecialchars($order['notes']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="change_status">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">

                        <button type="submit" name="new_status" value="received"
                                class="status-btn"
                                style="background: <?php echo getStatusColor('received'); ?>;
                                       color: <?php echo getStatusTextColor('received'); ?>;
                                       <?php echo $order['status'] === 'received' ? 'border-color: currentColor;' : ''; ?>">
                            Reçue
                        </button>

                        <button type="submit" name="new_status" value="preparing"
                                class="status-btn"
                                style="background: <?php echo getStatusColor('preparing'); ?>;
                                       color: <?php echo getStatusTextColor('preparing'); ?>;
                                       <?php echo $order['status'] === 'preparing' ? 'border-color: currentColor;' : ''; ?>">
                            En préparation
                        </button>

                        <button type="submit" name="new_status" value="ready"
                                class="status-btn"
                                style="background: <?php echo getStatusColor('ready'); ?>;
                                       color: <?php echo getStatusTextColor('ready'); ?>;
                                       <?php echo $order['status'] === 'ready' ? 'border-color: currentColor;' : ''; ?>">
                            Prête
                        </button>

                        <button type="submit" name="new_status" value="delivered"
                                class="status-btn"
                                style="background: <?php echo getStatusColor('delivered'); ?>;
                                       color: <?php echo getStatusTextColor('delivered'); ?>;
                                       <?php echo $order['status'] === 'delivered' ? 'border-color: currentColor;' : ''; ?>">
                            Livrée
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; color: #9ca3af; font-size: 12px;">
            Affichage limité aux 20 dernières commandes
        </div>
    </div>
</body>
</html>
