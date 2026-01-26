<?php
// Debug: Voir ce que l'API retourne
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $categories = MenuRepository::getAllCategories();

    echo json_encode([
        'success' => true,
        'count' => count($categories),
        'categories' => $categories,
        'first_category' => $categories[0] ?? null
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
