<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== TEST BOOTSTRAP WEB ===\n\n";

echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'non défini') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'non défini') . "\n\n";

try {
    require_once __DIR__ . '/bootstrap.php';
    echo "✅ Bootstrap chargé\n";
    echo "Restaurant ID: " . SNACK_RESTAURANT_ID . "\n";
    echo "MenuRepository restaurantId: " . MenuRepository::$restaurantId . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
echo "</pre>";
