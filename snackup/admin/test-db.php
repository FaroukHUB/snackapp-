<?php
/**
 * Diagnostic script to check database connection
 */
header('Content-Type: application/json');

require_once __DIR__ . '/bootstrap.php';

$diagnostics = [
    'php_version' => phpversion(),
    'SNACK_USE_JSON' => defined('SNACK_USE_JSON') ? SNACK_USE_JSON : 'not defined',
    'SNACK_DB_ERROR' => defined('SNACK_DB_ERROR') ? SNACK_DB_ERROR : 'not defined',
    'SNACK_RESTAURANT_ID' => defined('SNACK_RESTAURANT_ID') ? SNACK_RESTAURANT_ID : 'not defined',
    'db_config_exists' => file_exists(SNACK_DB_PATH . '/config.php'),
];

// Try to get current restaurant
if (!SNACK_USE_JSON && !defined('SNACK_DB_ERROR')) {
    try {
        $restaurant = RestaurantRepository::getById(SNACK_RESTAURANT_ID);
        $diagnostics['mysql_mode'] = true;
        $diagnostics['restaurant'] = $restaurant ? $restaurant['name'] : 'not found';

        // Count orders
        $orders = OrderRepository::getActiveOrders(SNACK_RESTAURANT_ID, 5);
        $diagnostics['recent_orders'] = count($orders);
    } catch (Exception $e) {
        $diagnostics['mysql_error'] = $e->getMessage();
    }
} else {
    $diagnostics['mysql_mode'] = false;
    $diagnostics['reason'] = SNACK_USE_JSON ? 'SNACK_USE_JSON is true' : 'SNACK_DB_ERROR: ' . SNACK_DB_ERROR;
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
