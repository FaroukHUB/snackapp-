<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'dashboard';

switch ($action) {
    case 'dashboard':
        getDashboardStats();
        break;
    case 'sales':
        getSalesStats();
        break;
    case 'products':
        getProductStats();
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Action invalide']);
}

function getDashboardStats() {
    $orders = loadData('orders.json');
    $customers = loadData('customers.json');

    $today = date('Y-m-d');
    $thisWeek = date('Y-m-d', strtotime('monday this week'));
    $thisMonth = date('Y-m');

    $todayOrders = array_filter($orders, fn($o) => substr($o['created_at'], 0, 10) === $today);
    $weekOrders = array_filter($orders, fn($o) => $o['created_at'] >= $thisWeek);
    $monthOrders = array_filter($orders, fn($o) => substr($o['created_at'], 0, 7) === $thisMonth);

    $todayRevenue = array_sum(array_column($todayOrders, 'total'));
    $weekRevenue = array_sum(array_column($weekOrders, 'total'));
    $monthRevenue = array_sum(array_column($monthOrders, 'total'));

    $newCustomersThisMonth = count(array_filter($customers, fn($c) => substr($c['registered_at'], 0, 7) === $thisMonth));

    echo json_encode([
        'success' => true,
        'stats' => [
            'today' => [
                'orders' => count($todayOrders),
                'revenue' => round($todayRevenue, 2)
            ],
            'week' => [
                'orders' => count($weekOrders),
                'revenue' => round($weekRevenue, 2)
            ],
            'month' => [
                'orders' => count($monthOrders),
                'revenue' => round($monthRevenue, 2)
            ],
            'customers' => [
                'total' => count($customers),
                'new_this_month' => $newCustomersThisMonth,
                'vip' => count(array_filter($customers, fn($c) => $c['orders_count'] >= 10))
            ],
            'current' => [
                'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'received')),
                'preparing' => count(array_filter($orders, fn($o) => $o['status'] === 'preparing')),
                'ready' => count(array_filter($orders, fn($o) => $o['status'] === 'ready'))
            ]
        ]
    ]);
}

function getSalesStats() {
    $orders = loadData('orders.json');
    $period = $_GET['period'] ?? 'week'; // week, month, year

    // Données pour graphique
    $salesByDay = [];

    if ($period === 'week') {
        // 7 derniers jours
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayOrders = array_filter($orders, fn($o) => substr($o['created_at'], 0, 10) === $date);
            $salesByDay[] = [
                'date' => $date,
                'label' => date('D', strtotime($date)),
                'orders' => count($dayOrders),
                'revenue' => round(array_sum(array_column($dayOrders, 'total')), 2)
            ];
        }
    } elseif ($period === 'month') {
        // 30 derniers jours
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayOrders = array_filter($orders, fn($o) => substr($o['created_at'], 0, 10) === $date);
            $salesByDay[] = [
                'date' => $date,
                'label' => date('d/m', strtotime($date)),
                'orders' => count($dayOrders),
                'revenue' => round(array_sum(array_column($dayOrders, 'total')), 2)
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'period' => $period,
        'data' => $salesByDay
    ]);
}

function getProductStats() {
    $orders = loadData('orders.json');

    // Compter les produits vendus
    $productCounts = [];
    foreach ($orders as $order) {
        foreach ($order['items'] as $item) {
            $name = $item['name'];
            if (!isset($productCounts[$name])) {
                $productCounts[$name] = ['count' => 0, 'revenue' => 0];
            }
            $productCounts[$name]['count'] += $item['quantity'];
            $productCounts[$name]['revenue'] += $item['price'] * $item['quantity'];
        }
    }

    // Trier par nombre de ventes
    uasort($productCounts, fn($a, $b) => $b['count'] - $a['count']);

    // Top 10
    $top10 = array_slice($productCounts, 0, 10, true);

    $result = [];
    foreach ($top10 as $name => $data) {
        $result[] = [
            'name' => $name,
            'count' => $data['count'],
            'revenue' => round($data['revenue'], 2)
        ];
    }

    echo json_encode([
        'success' => true,
        'top_products' => $result
    ]);
}
?>
