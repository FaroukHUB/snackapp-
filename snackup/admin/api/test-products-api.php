<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG API PRODUCTS ===\n\n";

require_once __DIR__ . '/../bootstrap.php';

echo "✅ Bootstrap chargé\n";
echo "SNACK_USE_JSON: " . (SNACK_USE_JSON ? 'true' : 'false') . "\n";
echo "SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";
echo "MenuRepository::\$restaurantId: " . MenuRepository::$restaurantId . "\n\n";

// Simuler exactement ce que fait products.php
try {
    // Vérifier si GET action
    $action = $_GET['action'] ?? null;
    echo "Action: " . ($action ?? 'null') . "\n\n";
    
    // Test getAllCategories (ce que fait l'API par défaut)
    echo "Test MenuRepository::getAllCategories()...\n";
    $categories = MenuRepository::getAllCategories();
    
    echo "✅ Succès! Catégories: " . count($categories) . "\n";
    
    // Afficher le JSON comme l'API
    $response = [
        'success' => true,
        'categories' => $categories
    ];
    
    echo "\nRéponse JSON (premiers 500 caractères):\n";
    echo substr(json_encode($response), 0, 500) . "...\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
?>
