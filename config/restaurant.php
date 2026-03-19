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

    // Pour l'instance demo, utiliser des données statiques (pas de DB)
    if ($instanceName === 'demo') {
        $restaurant = [
            'name' => $instanceConfig['app']['name'] ?? 'Restaurant Demo',
            'legal_name' => $instanceConfig['app']['name'] ?? 'Restaurant Demo',
            'slug' => 'demo',
            'phone' => $instanceConfig['contact']['phone'] ?? '',
            'email' => $instanceConfig['contact']['email'] ?? '',
            'address' => $instanceConfig['location']['address'] ?? '',
            'city' => $instanceConfig['location']['city'] ?? '',
            'postal_code' => $instanceConfig['location']['postalCode'] ?? '',
            'latitude' => $instanceConfig['location']['coordinates']['lat'] ?? 0,
            'longitude' => $instanceConfig['location']['coordinates']['lng'] ?? 0,
            'logo_url' => ''
        ];
        $settings = [
            'brand_tagline' => 'Commandez en ligne',
            'is_halal' => true,
            'whatsapp_orders_number' => $instanceConfig['contact']['whatsappOrdersNumber'] ?? '',
            'social_links' => '{}'
        ];
    } else {
        // Pour les autres instances, charger depuis la base de données
        require_once __DIR__ . '/../snackup/backend/Database.php';
        Database::init(InstanceManager::getDatabaseConfig());

        require_once __DIR__ . '/../snackup/backend/repositories/RestaurantRepository.php';

        // Récupérer les données du restaurant depuis la base
        $restaurant = RestaurantRepository::getById($restaurantId);
        $settings = RestaurantRepository::getSettings($restaurantId);
    }

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
        'phone' => $instanceConfig['contact']['phone'] ?? $restaurant['phone'],
        'phoneDisplay' => $instanceConfig['contact']['phoneDisplay'] ?? $restaurant['phone'],
        'allowWhatsAppOrders' => !empty($instanceConfig['contact']['whatsappOrdersNumber']) || !empty($settings['whatsapp_orders_number']),
        'whatsappOrdersNumber' => $instanceConfig['contact']['whatsappOrdersNumber'] ?? $settings['whatsapp_orders_number'] ?? '',
        'email' => $instanceConfig['contact']['email'] ?? $restaurant['email'] ?? ''
    ],

    'location' => [
        'address' => $restaurant['address'],
        'city' => $restaurant['city'] ?? '',
        'postalCode' => $restaurant['postal_code'] ?? '',
        'latitude' => (float)($restaurant['latitude'] ?? 0),
        'longitude' => (float)($restaurant['longitude'] ?? 0),
        'googleMapsUrl' => $instanceConfig['location']['googleMapsUrl'] ?? '',
        'googleMapsEmbed' => $instanceConfig['location']['googleMapsEmbed'] ?? ''
    ],

    'branding' => [
        'logo' => $restaurant['logo_url'] ?? '',
        'primaryColor' => $themeConfig['primary'] ?? '#e63946'
    ],

    'social' => json_decode($settings['social_links'] ?? '{}', true)
];

// Ajouter les horaires d'ouverture
if ($instanceName !== 'demo') {
    require_once __DIR__ . '/../snackup/backend/repositories/RestaurantRepository.php';
    require_once __DIR__ . '/../snackup/backend/repositories/LoyaltyRepository.php';

    $openingHoursRaw = RestaurantRepository::getOpeningHours($restaurantId);
    $days = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
    $openingHours = [];

    foreach ($openingHoursRaw as $h) {
        $dayIndex = (int)$h['day_of_week'];
        if ($h['is_closed']) {
            $openingHours[] = [
                'day' => $days[$dayIndex] ?? 'Jour',
                'isClosed' => true
            ];
        } else {
            $openingHours[] = [
                'day' => $days[$dayIndex] ?? 'Jour',
                'opens' => substr($h['opens'], 0, 5),
                'closes' => substr($h['closes'], 0, 5),
                'isClosed' => false
            ];
        }
    }

    $response['openingHours'] = $openingHours;

    // Ajouter les paramètres de fidélité
    $response['loyalty'] = [
        'enabled' => (bool)($settings['loyalty_enabled'] ?? true),
        'pointsPerCurrencyUnit' => (int)($settings['loyalty_points_per_euro'] ?? 1),
        'currencyUnit' => (int)($settings['loyalty_currency_unit'] ?? 100) // 100 DA par défaut
    ];

    // Ajouter les récompenses de fidélité
    $rewards = LoyaltyRepository::getRewards($restaurantId);
    $response['loyalty']['rewards'] = array_map(function($r) {
        return [
            'id' => $r['id'],
            'name' => $r['name'],
            'description' => $r['description'] ?? '',
            'pointsRequired' => (int)$r['points_required'],
            'icon' => $r['icon'] ?? 'fa-gift'
        ];
    }, $rewards);
} else {
    // Données par défaut pour la démo
    $response['openingHours'] = [
        ['day' => 'Lun', 'opens' => '18:30', 'closes' => '23:30', 'isClosed' => false],
        ['day' => 'Mar', 'opens' => '18:30', 'closes' => '23:30', 'isClosed' => false],
        ['day' => 'Mer', 'opens' => '18:30', 'closes' => '23:30', 'isClosed' => false],
        ['day' => 'Jeu', 'opens' => '18:30', 'closes' => '23:30', 'isClosed' => false],
        ['day' => 'Ven', 'opens' => '18:30', 'closes' => '01:00', 'isClosed' => false],
        ['day' => 'Sam', 'opens' => '18:30', 'closes' => '01:00', 'isClosed' => false],
        ['day' => 'Dim', 'opens' => '18:30', 'closes' => '00:00', 'isClosed' => false]
    ];

    $response['loyalty'] = [
        'enabled' => true,
        'pointsPerCurrencyUnit' => 1,
        'currencyUnit' => 100,
        'rewards' => [
            ['id' => 1, 'name' => 'Cookie offert', 'description' => 'Un délicieux cookie maison', 'pointsRequired' => 500, 'icon' => 'fa-cookie'],
            ['id' => 2, 'name' => 'Boisson offerte', 'description' => 'Boisson au choix', 'pointsRequired' => 800, 'icon' => 'fa-glass-water'],
            ['id' => 3, 'name' => 'Burger offert', 'description' => 'Burger au choix', 'pointsRequired' => 1500, 'icon' => 'fa-burger'],
            ['id' => 4, 'name' => 'Menu complet', 'description' => 'Burger + Frites + Boisson', 'pointsRequired' => 2500, 'icon' => 'fa-box']
        ]
    ];
}

// Injecter la config JavaScript pour le frontend
$response['_jsConfig'] = [
    'currency' => InstanceManager::getCurrency(),
    'restaurantId' => $restaurantId,
    'instanceId' => InstanceManager::getInstanceId(),
    'instanceName' => $instanceName
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
