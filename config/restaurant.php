<?php
/**
 * API publique : Retourne les infos du restaurant depuis la base de données
 * Détecte automatiquement l'instance selon le domaine
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Détecter quelle instance utiliser selon le domaine
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

if (strpos($host, 'atelierpizza') !== false) {
    $instanceName = 'atelier-pizza';
} elseif (strpos($host, 'marvelous') !== false || strpos($host, 'fabrik') !== false) {
    $instanceName = 'marvelous';
} else {
    // Fallback : essayer de détecter depuis le chemin
    $instanceName = 'marvelous'; // Par défaut
}

// Charger la configuration de l'instance
$instanceConfigPath = __DIR__ . '/../instances/' . $instanceName . '/backend-config.php';

if (!file_exists($instanceConfigPath)) {
    echo json_encode([
        'error' => 'Instance configuration not found',
        'instance' => $instanceName,
        'host' => $host
    ]);
    exit;
}

$instanceConfig = require $instanceConfigPath;

// Charger la base de données
require_once __DIR__ . '/../snackup/backend/Database.php';
Database::init($instanceConfig['database']);

require_once __DIR__ . '/../snackup/backend/repositories/RestaurantRepository.php';

// Récupérer les données du restaurant depuis la base
$restaurantId = $instanceConfig['app']['restaurant_id'];
$restaurant = RestaurantRepository::getById($restaurantId);
$settings = RestaurantRepository::getSettings($restaurantId);

if (!$restaurant) {
    echo json_encode([
        'error' => 'Restaurant not found',
        'restaurantId' => $restaurantId
    ]);
    exit;
}

// Construire la réponse JSON avec les données DB + config instance
$response = [
    'id' => $instanceConfig['app']['instance_id'],
    'name' => $restaurant['name'],
    'legalName' => $restaurant['legal_name'] ?? $restaurant['name'],
    'slug' => $restaurant['slug'],
    'brandTagline' => $settings['brand_tagline'] ?? '',
    'priceRange' => $instanceConfig['app']['currency'],
    'isHalal' => (bool)($settings['is_halal'] ?? true),

    'theme' => [
        'primary' => $instanceConfig['theme']['primary'] ?? '#e63946',
        'primaryDark' => $instanceConfig['theme']['primary_dark'] ?? '#d62839',
        'secondary' => $instanceConfig['theme']['secondary'] ?? '#1a1a2e',
        'accent' => $instanceConfig['theme']['accent'] ?? '#ff6fae',
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
        'primaryColor' => $instanceConfig['theme']['primary'] ?? '#e63946'
    ],

    'social' => json_decode($settings['social_links'] ?? '{}', true)
];

// Injecter la config JavaScript pour le frontend
$response['_jsConfig'] = [
    'currency' => $instanceConfig['app']['currency'],
    'restaurantId' => $restaurantId,
    'instanceId' => $instanceConfig['app']['instance_id']
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
