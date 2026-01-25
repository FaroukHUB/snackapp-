<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== TEST BOOTSTRAP ===\n";

try {
    require_once __DIR__ . '/bootstrap.php';
    echo "✅ Bootstrap chargé\n";
    echo "Restaurant ID: " . SNACK_RESTAURANT_ID . "\n";
    echo "MenuRepository restaurantId: " . MenuRepository::$restaurantId . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
