<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';

echo "=== DEBUG PRODUCTS API ===\n\n";
echo "SNACK_USE_JSON: " . (SNACK_USE_JSON ? 'true' : 'false') . "\n";
echo "SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";
echo "MenuRepository restaurantId: " . MenuRepository::$restaurantId . "\n\n";

try {
    $categories = MenuRepository::getAllCategories();
    echo "✅ Catégories trouvées: " . count($categories) . "\n";
    print_r($categories);
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
