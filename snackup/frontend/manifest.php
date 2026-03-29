<?php
/**
 * Manifest PWA dynamique
 * Génère le manifest.json selon l'instance courante
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../backend/InstanceManager.php';

try {
    InstanceManager::init();
    $config = InstanceManager::loadConfig();
    $restaurant = $config['app'] ?? [];

    $manifest = [
        'name' => ($restaurant['name'] ?? 'Restaurant') . ' - Commandez en ligne',
        'short_name' => $restaurant['name'] ?? 'Restaurant',
        'description' => 'Commandez en ligne chez ' . ($restaurant['name'] ?? 'notre restaurant'),
        'start_url' => '/snackup/frontend/index.html',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => $config['branding']['primaryColor'] ?? '#e63946',
        'orientation' => 'portrait',
        'icons' => [
            [
                'src' => '../images/logo.png',
                'sizes' => 'any',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ],
        'categories' => ['food', 'shopping'],
        'lang' => 'fr-FR'
    ];

    echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Impossible de générer le manifest',
        'message' => $e->getMessage()
    ]);
}
