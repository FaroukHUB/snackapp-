<?php
/**
 * Import restaurant.json vers MySQL
 */

require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/repositories/RestaurantRepository.php';

$config = require __DIR__ . '/database/config.php';
Database::init($config['database']);

$jsonPath = __DIR__ . '/config/restaurant.json';
if (!file_exists($jsonPath)) {
    die("Fichier restaurant.json non trouve\n");
}

$data = json_decode(file_get_contents($jsonPath), true);
if (!$data) {
    die("Erreur de parsing JSON\n");
}

echo "Import de restaurant.json...\n\n";

$restaurantId = 1;

echo "Restaurant: {$data['name']}\n";

try {
    Database::update('restaurants', [
        'name' => $data['name'],
        'slug' => $data['slug']
    ], ['id' => $restaurantId]);
    echo "Info restaurant mis a jour\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

$settings = [
    'phone' => $data['contact']['phone'] ?? '',
    'whatsapp_number' => $data['contact']['whatsappOrdersNumber'] ?? '',
    'address' => $data['location']['addressLine1'] ?? '',
    'city' => $data['location']['city'] ?? '',
    'postal_code' => $data['location']['postalCode'] ?? '',
    'instagram' => $data['social']['instagram'] ?? '',
    'facebook' => $data['social']['facebook'] ?? '',
    'tiktok' => $data['social']['tiktok'] ?? '',
    'snapchat' => $data['social']['snapchat'] ?? ''
];

try {
    RestaurantRepository::updateSettings($restaurantId, $settings);
    echo "Parametres mis a jour\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

$dayMap = ['lundi' => 0, 'mardi' => 1, 'mercredi' => 2, 'jeudi' => 3, 'vendredi' => 4, 'samedi' => 5, 'dimanche' => 6];
$hours = [];

if (!empty($data['openingHours'])) {
    foreach ($data['openingHours'] as $h) {
        $dayIndex = $dayMap[strtolower($h['day'])] ?? 0;
        $hours[$dayIndex] = [
            'opens' => $h['opens'],
            'closes' => $h['closes'],
            'is_closed' => 0
        ];
    }
    ksort($hours);
    RestaurantRepository::updateOpeningHours($restaurantId, $hours);
    echo count($hours) . " horaires importes\n";
}

if (!empty($data['faq']['items'])) {
    RestaurantRepository::updateFaq($restaurantId, $data['faq']['items']);
    echo count($data['faq']['items']) . " questions FAQ importees\n";
}

echo "\nImport termine!\n";
