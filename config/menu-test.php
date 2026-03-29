<?php
/**
 * API de test : Retourne le menu depuis menu.json (sans BDD)
 * Utilisé pour le développement frontend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Charger menu.json
    $menuPath = __DIR__ . '/menu.json';

    if (!file_exists($menuPath)) {
        throw new Exception("menu.json not found");
    }

    $menuData = json_decode(file_get_contents($menuPath), true);

    if (!$menuData) {
        throw new Exception("Invalid JSON in menu.json");
    }

    // Formater la réponse comme l'API normale
    $response = [
        'menu' => [
            'categories' => $menuData['categories'] ?? []
        ],
        'supplements' => $menuData['supplements'] ?? [
            'catalog' => [],
            'defaultForCategories' => []
        ],
        'formules' => $menuData['formules'] ?? [],
        'featured' => $menuData['featured'] ?? [
            'enabled' => false,
            'title' => '',
            'subtitle' => '',
            'items' => []
        ],
        'categoryIcons' => $menuData['categoryIcons'] ?? [],
        '_meta' => [
            'currency' => 'DA',
            'loadedFrom' => 'menu.json (test mode)'
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
