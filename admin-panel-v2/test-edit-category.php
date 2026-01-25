<?php
session_start();
$_SESSION['admin_logged_in'] = true; // Simuler authentification

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== TEST ÉDITION CATÉGORIE ===\n\n";

require_once __DIR__ . '/bootstrap.php';

echo "SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";
echo "MenuRepository::\$restaurantId: " . MenuRepository::$restaurantId . "\n\n";

// Tester avec la catégorie ID 9
$categoryId = 9;

echo "Test édition catégorie ID $categoryId\n\n";

try {
    $result = MenuRepository::editCategory($categoryId, 'Pizzas Sauce Tomate MODIF', 'Test depuis web', '🍕', 'sale');
    echo "✅ Édition réussie: " . ($result ? "OUI" : "NON") . "\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "</pre>";
