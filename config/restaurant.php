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
    $openingHours = RestaurantRepository::getOpeningHours($restaurantId);
    $faqItems = RestaurantRepository::getFaq($restaurantId);

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
// Thème: priorité BD > InstanceManager > défaut
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
        'primary' => $settings['theme_primary'] ?? $themeConfig['primary'] ?? '#e63946',
        'primaryDark' => $settings['theme_primary_dark'] ?? $themeConfig['primary_dark'] ?? '#d62839',
        'secondary' => $settings['theme_secondary'] ?? $themeConfig['secondary'] ?? '#1a1a2e',
        'accent' => $settings['theme_accent'] ?? $themeConfig['accent'] ?? '#ff6fae',
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
        'phone' => $instanceConfig['contact']['phone'] ?? $restaurant['phone'],
        'phoneDisplay' => $instanceConfig['contact']['phoneDisplay'] ?? $restaurant['phone'],
        'allowWhatsAppOrders' => !empty($instanceConfig['contact']['whatsappOrdersNumber']) || !empty($settings['whatsapp_orders_number']),
        'whatsappOrdersNumber' => $instanceConfig['contact']['whatsappOrdersNumber'] ?? $settings['whatsapp_orders_number'] ?? '',
        'email' => $instanceConfig['contact']['email'] ?? $restaurant['email'] ?? ''
    ],

    'location' => [
        'address' => $settings['address'] ?? $restaurant['address'] ?? '',
        'city' => $settings['city'] ?? $restaurant['city'] ?? '',
        'postalCode' => $settings['postal_code'] ?? $restaurant['postal_code'] ?? '',
        'latitude' => (float)($settings['latitude'] ?? $restaurant['latitude'] ?? 0),
        'longitude' => (float)($settings['longitude'] ?? $restaurant['longitude'] ?? 0),
        'googleMapsUrl' => $instanceConfig['location']['googleMapsUrl'] ?? '',
        'googleMapsEmbed' => $instanceConfig['location']['googleMapsEmbed'] ?? ''
    ],

    'branding' => [
        'logo' => $restaurant['logo_url'] ?? '',
        'primaryColor' => $themeConfig['primary'] ?? '#e63946'
    ],

    // Réseaux sociaux depuis colonnes individuelles
    'social' => [
        'instagram' => $settings['instagram'] ?? '',
        'facebook' => $settings['facebook'] ?? '',
        'tiktok' => $settings['tiktok'] ?? '',
        'snapchat' => $settings['snapchat'] ?? ''
    ],

    // Horaires d'ouverture depuis table opening_hours
    'openingHours' => array_map(function($h) {
        $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        return [
            'day' => $days[$h['day_of_week']] ?? 'jour',
            'opens' => substr($h['opens'], 0, 5),
            'closes' => substr($h['closes'], 0, 5),
            'isClosed' => (bool)($h['is_closed'] ?? false)
        ];
    }, $openingHours),

    // FAQ depuis table faq
    'faq' => [
        'title' => 'Questions fréquentes',
        'items' => array_map(function($f) {
            return [
                'question' => $f['question'],
                'answer' => $f['answer']
            ];
        }, $faqItems)
    ],

    // Plateformes de livraison depuis table delivery_platforms
    'platforms' => array_map(function($p) {
        return [
            'id' => $p['slug'],
            'name' => $p['name'],
            'url' => $p['url'],
            'icon' => $p['icon'] ?? $p['slug'],
            'enabled' => (bool) $p['is_enabled']
        ];
    }, RestaurantRepository::getDeliveryPlatforms($restaurantId)),

    // Statut livraison
    'delivery' => [
        'enabled' => (bool) ($settings['delivery_enabled'] ?? true)
    ]
];

// Injecter la config JavaScript pour le frontend
$response['_jsConfig'] = [
    'currency' => InstanceManager::getCurrency(),
    'restaurantId' => $restaurantId,
    'instanceId' => InstanceManager::getInstanceId(),
    'instanceName' => $instanceName
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
