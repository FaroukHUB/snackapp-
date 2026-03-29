<?php
/**
 * API de test : Retourne les infos restaurant depuis restaurant.json (sans BDD)
 * Utilisé pour le développement frontend
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    // Charger restaurant.json
    $restaurantPath = __DIR__ . '/restaurant.json';

    if (!file_exists($restaurantPath)) {
        throw new Exception("restaurant.json not found");
    }

    $restaurantData = json_decode(file_get_contents($restaurantPath), true);

    if (!$restaurantData) {
        throw new Exception("Invalid JSON in restaurant.json");
    }

    echo json_encode($restaurantData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors du chargement du restaurant',
        'message' => $e->getMessage()
    ]);
}
