<?php
/**
 * Génère menu.json depuis MySQL
 * À appeler après chaque modification (add/edit/delete category/product)
 */

define('SNACK_ROOT', __DIR__ . '/..');
define('SNACK_RESTAURANT_ID', 2);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/repositories/MenuRepository.php';

// Charger config DB
$dbConfig = require __DIR__ . '/config.php';
Database::init($dbConfig['database']);

try {
    $pdo = Database::getInstance();

    // Récupérer toutes les données depuis MySQL
    $categories = MenuRepository::getAllCategories();
    $supplements = MenuRepository::getAllSupplements();
    $categorySupplements = MenuRepository::getCategorySupplements();

    // Récupérer les icônes des catégories
    $categoryIcons = [];
    foreach ($categories as $cat) {
        if (isset($cat['icon']) && isset($cat['slug'])) {
            $categoryIcons[$cat['slug']] = $cat['icon'];
        }
    }

    // Formater pour le frontend
    $menuData = [
        'version' => 1,
        'lastUpdated' => date('c'),
        'menu' => [
            'categories' => $categories
        ],
        'supplements' => [
            'catalog' => $supplements,
            'defaultForCategories' => $categorySupplements
        ],
        'categoryIcons' => $categoryIcons,
        'formules' => [], // À implémenter si nécessaire
        'featured' => [
            'enabled' => true,
            'title' => 'Sélection pour vous',
            'subtitle' => 'Nos produits les plus appréciés',
            'items' => []
        ]
    ];

    // Écrire menu.json
    $menuJsonPath = SNACK_ROOT . '/config/menu.json';
    $json = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        throw new Exception('Erreur encodage JSON: ' . json_last_error_msg());
    }

    if (file_put_contents($menuJsonPath, $json) === false) {
        throw new Exception('Impossible d\'écrire menu.json');
    }

    echo "✅ menu.json généré avec succès (" . strlen($json) . " octets)\n";
    echo "   - " . count($categories) . " catégories\n";
    echo "   - " . count($supplements) . " suppléments\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
