<?php
/**
 * API publique : Retourne les infos du restaurant depuis la base de données
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

    require_once __DIR__ . '/../snackup/backend/repositories/RestaurantRepository.php';

    // Récupérer les données du restaurant depuis la base
    $restaurant = RestaurantRepository::getById($restaurantId);
    $settings = RestaurantRepository::getSettings($restaurantId);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Instance initialization failed',
        'message' => $e->getMessage(),
        'debug' => InstanceManager::getDebugInfo()
    ]);
    exit;
}

if (!$restaurant) {
    echo json_encode([
        'error' => 'Restaurant not found',
        'restaurantId' => $restaurantId
    ]);
    exit;
}

// Construire la réponse JSON avec les données DB + config instance
$themeConfig = InstanceManager::getThemeConfig() ?? [];

$response = [
    'id' => InstanceManager::getInstanceId(),
    'name' => $restaurant['name'],
    'legalName' => $restaurant['legal_name'] ?? $restaurant['name'],
    'slug' => $restaurant['slug'],
    'brandTagline' => $settings['brand_tagline'] ?? '',
    'priceRange' => InstanceManager::getCurrency(),
    'isHalal' => (bool)($settings['is_halal'] ?? true),

    'theme' => [
        'primary' => $themeConfig['primary'] ?? '#e63946',
        'primaryDark' => $themeConfig['primary_dark'] ?? '#d62839',
        'secondary' => $themeConfig['secondary'] ?? '#1a1a2e',
        'accent' => $themeConfig['accent'] ?? '#ff6fae',
        'background' => '#f5f5f5',
        'cardBackground' => '#ffffff',
        'textPrimary' => '#111111',
        'textSecondary' => '#666666',
        'success' => '#27ae60',
        'error' => '#e74c3c',
        'buttonRadius' => '12px',
        'cardRadius' => '16px'
    ],

    'contact' => [
        'phone' => $restaurant['phone'],
        'phoneDisplay' => $restaurant['phone'],
        'allowWhatsAppOrders' => !empty($settings['whatsapp_orders_number']),
        'whatsappOrdersNumber' => $settings['whatsapp_orders_number'] ?? '',
        'email' => $restaurant['email'] ?? ''
    ],

    'location' => [
        'address' => $restaurant['address'],
        'city' => $restaurant['city'] ?? '',
        'postalCode' => $restaurant['postal_code'] ?? '',
        'latitude' => (float)($restaurant['latitude'] ?? 0),
        'longitude' => (float)($restaurant['longitude'] ?? 0)
    ],

    'branding' => [
        'logo' => $restaurant['logo_url'] ?? '',
        'primaryColor' => $themeConfig['primary'] ?? '#e63946'
    ],

    'social' => json_decode($settings['social_links'] ?? '{}', true)
];

// Injecter la config JavaScript pour le frontend
$response['_jsConfig'] = [
    'currency' => InstanceManager::getCurrency(),
    'restaurantId' => $restaurantId,
    'instanceId' => InstanceManager::getInstanceId(),
    'instanceName' => $instanceName
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
