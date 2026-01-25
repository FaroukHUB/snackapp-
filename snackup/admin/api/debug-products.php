<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../bootstrap.php';

echo "=== DEBUG API PRODUCTS - MODE MYSQL ===\n\n";
echo "SNACK_USE_JSON: " . (SNACK_USE_JSON ? 'true' : 'false') . "\n";
echo "SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";
echo "SNACK_ROOT: " . (defined('SNACK_ROOT') ? SNACK_ROOT : 'NOT DEFINED') . "\n";
echo "MenuRepository::\$restaurantId: " . MenuRepository::$restaurantId . "\n\n";

try {
    // Simuler exactement products.php
    $menuJsonPath = SNACK_ROOT . '/config/menu.json';
    echo "Chemin menu.json: $menuJsonPath\n";
    echo "Fichier existe? " . (file_exists($menuJsonPath) ? 'OUI' : 'NON') . "\n\n";
    
    // Récupérer les données
    echo "Récupération getAllCategories()...\n";
    $categories = MenuRepository::getAllCategories();
    echo "✅ Catégories: " . count($categories) . "\n";
    
    echo "\nRécupération getAllSupplements()...\n";
    $supplements = MenuRepository::getAllSupplements();
    echo "✅ Suppléments: " . count($supplements) . "\n";
    
    echo "\nRécupération getCategorySupplements()...\n";
    $categorySupplements = MenuRepository::getCategorySupplements();
    echo "✅ Category Supplements: " . count($categorySupplements) . "\n";
    
    // Charger formules depuis menu.json
    $formules = [];
    $featured = [
        'enabled' => true,
        'title' => 'Sélection pour vous',
        'subtitle' => 'Nos produits les plus appréciés',
        'items' => []
    ];
    $categoryIcons = [];
    
    if (file_exists($menuJsonPath)) {
        echo "\nChargement menu.json...\n";
        $menuData = json_decode(file_get_contents($menuJsonPath), true);
        if ($menuData) {
            $formules = $menuData['formules'] ?? [];
            $featured = $menuData['featured'] ?? $featured;
            $categoryIcons = $menuData['categoryIcons'] ?? [];
            echo "✅ Menu.json chargé - Formules: " . count($formules) . "\n";
        } else {
            echo "⚠️  Menu.json existe mais JSON invalide\n";
        }
    } else {
        echo "⚠️  Menu.json n'existe pas (normal si pas encore créé)\n";
    }
    
    echo "\n✅ TOUT FONCTIONNE!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR ATTRAPÉE:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n❌ ERROR PHP ATTRAPÉE:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
?>
