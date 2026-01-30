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

    // Définir le restaurant ID
    MenuRepository::$restaurantId = $restaurantId;

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

    // Upsell Rules - suggestions basées sur le panier
    // Récupère les slugs des catégories pour les règles
    $categorySlugs = array_column($categories, 'slug');

    $upsellRules = [];

    // Règles pour pizzeria
    if (in_array('pizzas', $categorySlugs) || in_array('pizza', $categorySlugs)) {
        // Si pizza dans le panier, suggérer boissons et desserts
        $upsellRules[] = [
            'when' => ['pizzas', 'pizza', 'pizzas-classiques', 'pizzas-speciales', 'pizzas-signature'],
            'suggest' => ['boissons', 'desserts', 'sodas', 'tiramisu', 'cookies'],
            'message' => 'Une boisson ou un dessert pour accompagner ?',
            'priority' => 1
        ];
    }

    // Si burgers
    if (in_array('burgers', $categorySlugs) || in_array('burger', $categorySlugs)) {
        $upsellRules[] = [
            'when' => ['burgers', 'burger'],
            'suggest' => ['boissons', 'desserts', 'frites', 'sodas'],
            'message' => 'Des frites ou une boisson avec votre burger ?',
            'priority' => 1
        ];
    }

    // Si tacos
    if (in_array('tacos', $categorySlugs) || in_array('taco', $categorySlugs)) {
        $upsellRules[] = [
            'when' => ['tacos', 'taco'],
            'suggest' => ['boissons', 'desserts', 'sodas'],
            'message' => 'Une boisson fraîche pour accompagner ?',
            'priority' => 1
        ];
    }

    // Règle générale : si dessert, suggérer boisson chaude
    if (in_array('desserts', $categorySlugs)) {
        $upsellRules[] = [
            'when' => ['desserts', 'dessert', 'patisseries'],
            'suggest' => ['boissons-chaudes', 'cafe', 'the', 'chocolat-chaud'],
            'message' => 'Un café ou une boisson chaude avec votre dessert ?',
            'priority' => 2
        ];
    }

    // Construire la réponse complète
    $response = [
        'menu' => $menu,
        'supplements' => $supplementsFormatted,
        'formules' => $formules,
        'featured' => $featured,
        'categoryIcons' => $categoryIcons,
        'upsellRules' => $upsellRules,
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
