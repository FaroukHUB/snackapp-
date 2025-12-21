<?php
/**
 * Diagnostic webhook - à supprimer après test
 */
header('Content-Type: application/json');

$diagnostics = [
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'data_dir' => __DIR__ . '/data/',
    'data_dir_exists' => is_dir(__DIR__ . '/data/'),
    'data_dir_writable' => is_writable(__DIR__ . '/data/'),
    'orders_file' => __DIR__ . '/data/orders.json',
    'orders_file_exists' => file_exists(__DIR__ . '/data/orders.json'),
    'orders_file_writable' => is_writable(__DIR__ . '/data/orders.json'),
    'config_file_exists' => file_exists(__DIR__ . '/config.php'),
];

// Test d'écriture
$testFile = __DIR__ . '/data/test-write-' . time() . '.txt';
$writeResult = @file_put_contents($testFile, 'test');
$diagnostics['write_test'] = $writeResult !== false ? 'OK' : 'FAILED';
if ($writeResult !== false) {
    @unlink($testFile);
}

// Test de lecture orders.json
$ordersContent = @file_get_contents(__DIR__ . '/data/orders.json');
$diagnostics['orders_readable'] = $ordersContent !== false;
if ($ordersContent !== false) {
    $orders = json_decode($ordersContent, true);
    $diagnostics['orders_count'] = is_array($orders) ? count($orders) : 'invalid JSON';
    $diagnostics['last_order_id'] = is_array($orders) && count($orders) > 0 ? $orders[count($orders)-1]['id'] ?? 'N/A' : 'N/A';
}

// Simuler une commande test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $diagnostics['received_data'] = $input;
    $data = json_decode($input, true);
    $diagnostics['parsed_data'] = $data;

    // Essayer d'ajouter à orders.json
    if ($ordersContent !== false && is_array($orders)) {
        $testOrder = [
            'id' => 'TEST-' . date('YmdHis'),
            'customer_name' => $data['customer_name'] ?? 'Test',
            'customer_phone' => $data['customer_phone'] ?? '0000000000',
            'items' => $data['items'] ?? [],
            'total' => $data['total'] ?? 0,
            'status' => 'received',
            'created_at' => date('Y-m-d H:i:s'),
            'test' => true
        ];
        $orders[] = $testOrder;
        $saveResult = @file_put_contents(__DIR__ . '/data/orders.json', json_encode($orders, JSON_PRETTY_PRINT));
        $diagnostics['save_result'] = $saveResult !== false ? 'OK (' . $saveResult . ' bytes)' : 'FAILED';
        $diagnostics['new_order_id'] = $testOrder['id'];
    }
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
