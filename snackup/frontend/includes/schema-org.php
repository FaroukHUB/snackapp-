<?php
/**
 * Schema.org Structured Data dynamique
 * Génère le JSON-LD selon l'instance courante
 */

if (!isset($config) || !isset($restaurant) || !isset($location)) {
    throw new Exception('Configuration non chargée');
}

$instance = InstanceManager::getCurrentInstance();
$instancesConfig = InstanceManager::getInstancesConfig();
$domains = $instancesConfig['instances'][$instance]['domains'] ?? [];
$primaryDomain = $domains[0] ?? 'localhost';
$baseUrl = 'https://' . $primaryDomain;

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $restaurant['name'] ?? 'Restaurant',
    'image' => $baseUrl . '/images/hero.webp',
    'url' => $baseUrl . '/snackup/frontend/',
    'telephone' => $contact['phone'] ?? '',
    'priceRange' => $config['app']['currency'] === 'EUR' ? '€€' : 'DA',
    'servesCuisine' => $config['cuisine'] ?? ['Restaurant'],
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $location['address'] ?? '',
        'addressLocality' => $location['city'] ?? '',
        'postalCode' => $location['postalCode'] ?? '',
        'addressCountry' => $location['country'] ?? 'FR'
    ]
];

// Ajouter les coordonnées GPS si disponibles
if (isset($location['coordinates']['lat']) && isset($location['coordinates']['lng'])) {
    $schema['geo'] = [
        '@type' => 'GeoCoordinates',
        'latitude' => $location['coordinates']['lat'],
        'longitude' => $location['coordinates']['lng']
    ];
}

// Ajouter les horaires d'ouverture si disponibles
if (isset($config['openingHours'])) {
    $schema['openingHoursSpecification'] = $config['openingHours'];
}

echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
