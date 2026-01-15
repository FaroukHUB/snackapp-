<?php
/**
 * API publique : Retourne le menu depuis la base de données
 * Détecte automatiquement l'instance selon le domaine
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Détecter quelle instance utiliser selon le domaine
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

if (strpos($host, 'marvelous') !== false || strpos($host, 'fabrik') !== false) {
    $instanceName = 'marvelous';
} elseif (strpos($host, 'atelierpizza') !== false) {
    $instanceName = 'atelier-pizza';
} else {
    // Fallback : Pour le développement local, utiliser atelier-pizza
    $instanceName = 'atelier-pizza'; // Par défaut
}

// Charger la configuration de l'instance
$instanceConfigPath = __DIR__ . '/../instances/' . $instanceName . '/backend-config.php';

if (!file_exists($instanceConfigPath)) {
    echo json_encode([
        'error' => 'Instance configuration not found',
        'instance' => $instanceName
    ]);
    exit;
}

$instanceConfig = require $instanceConfigPath;

// Charger la base de données et les repositories
require_once __DIR__ . '/../snackup/backend/Database.php';
Database::init($instanceConfig['database']);

require_once __DIR__ . '/../snackup/backend/repositories/MenuRepository.php';

// Définir le restaurant ID
$restaurantId = $instanceConfig['app']['restaurant_id'];
MenuRepository::$restaurantId = $restaurantId;

try {
    // Récupérer les données du menu depuis MySQL
    $categories = MenuRepository::getAllCategories();
    $supplements = MenuRepository::getAllSupplements();
    $categorySupplements = MenuRepository::getCategorySupplements();

    // Formater le menu pour le frontend
    $menu = ['categories' => $categories];

    // Formater les suppléments
    $supplementsFormatted = [
        'catalog' => $supplements,
        'defaultForCategories' => $categorySupplements
    ];

    // Charger formules depuis menu.json si existant (fallback temporaire)
    $menuJsonPath = __DIR__ . '/menu.json';
    $formules = [];
    $featured = [
        'enabled' => true,
        'title' => 'Sélection pour vous',
        'subtitle' => 'Nos produits les plus appréciés',
        'items' => []
    ];
    $categoryIcons = [];

    if (file_exists($menuJsonPath)) {
        $menuData = json_decode(file_get_contents($menuJsonPath), true);
        if ($menuData) {
            $formules = $menuData['formules'] ?? [];
            $featured = $menuData['featured'] ?? $featured;
            $categoryIcons = $menuData['categoryIcons'] ?? [];
        }
    }

    // Construire la réponse complète
    $response = [
        'menu' => $menu,
        'supplements' => $supplementsFormatted,
        'formules' => $formules,
        'featured' => $featured,
        'categoryIcons' => $categoryIcons,
        '_meta' => [
            'currency' => $instanceConfig['app']['currency'],
            'restaurantId' => $restaurantId,
            'loadedFrom' => 'database'
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors du chargement du menu',
        'message' => $e->getMessage()
    ]);
}
