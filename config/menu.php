<?php
/**
 * API publique : Retourne le menu depuis la base de données
 * Détecte automatiquement l'instance selon le domaine via InstanceManager
 *
 * @version 2.0.0 - Architecture scalable multi-instance
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Charger le gestionnaire d'instances
require_once __DIR__ . '/../snackup/backend/InstanceManager.php';

try {
    // Détecter et charger automatiquement la configuration de l'instance
    $instanceConfig = InstanceManager::loadConfig();
    $instanceName = InstanceManager::getCurrentInstance();
    $restaurantId = InstanceManager::getRestaurantId();

    // Charger la base de données
    require_once __DIR__ . '/../snackup/backend/Database.php';
    Database::init(InstanceManager::getDatabaseConfig());

    require_once __DIR__ . '/../snackup/backend/repositories/MenuRepository.php';
    require_once __DIR__ . '/../snackup/backend/repositories/PizzaBaseRepository.php';

    // Définir le restaurant ID
    MenuRepository::$restaurantId = $restaurantId;
    PizzaBaseRepository::$restaurantId = $restaurantId;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Instance initialization failed',
        'message' => $e->getMessage(),
        'debug' => InstanceManager::getDebugInfo()
    ]);
    exit;
}

try {
    // Récupérer les données du menu depuis MySQL
    $categories = MenuRepository::getAllCategories();
    $supplements = MenuRepository::getAllSupplements();
    $categorySupplements = MenuRepository::getCategorySupplements();
    $formules = MenuRepository::getAllFormules();

    // Formater le menu pour le frontend
    $menu = ['categories' => $categories];

    // Formater les suppléments
    $supplementsFormatted = [
        'catalog' => $supplements,
        'defaultForCategories' => $categorySupplements
    ];

    // Construire categoryIcons depuis la base de données (mapping slug → icon)
    $categoryIcons = [];
    foreach ($categories as $cat) {
        if (!empty($cat['slug']) && !empty($cat['icon'])) {
            $categoryIcons[$cat['slug']] = $cat['icon'];
        }
    }

    // Featured: charger depuis la DB
    $featuredSettings = MenuRepository::getFeaturedSettings();
    $featuredProductIds = MenuRepository::getFeaturedProductIds();
    $featured = [
        'enabled' => $featuredSettings['enabled'] ?? false,
        'title' => $featuredSettings['title'] ?? 'Sélection pour vous',
        'subtitle' => $featuredSettings['subtitle'] ?? 'Nos produits les plus appréciés',
        'items' => $featuredProductIds
    ];

    // Upsell Rules - chargé depuis la base de données
    $upsellRules = MenuRepository::getUpsellRules();

    // Pizza Bases - chargé depuis la base de données
    $pizzaBases = PizzaBaseRepository::getAll();

    // Construire la réponse complète
    $response = [
        'menu' => $menu,
        'supplements' => $supplementsFormatted,
        'formules' => $formules,
        'featured' => $featured,
        'categoryIcons' => $categoryIcons,
        'upsellRules' => $upsellRules,
        'pizzaBases' => $pizzaBases,
        '_meta' => [
            'currency' => InstanceManager::getCurrency(),
            'restaurantId' => $restaurantId,
            'instanceId' => InstanceManager::getInstanceId(),
            'instanceName' => $instanceName,
            'loadedFrom' => 'database'
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors du chargement du menu',
        'message' => $e->getMessage(),
        'instance' => InstanceManager::getCurrentInstance()
    ]);
}
