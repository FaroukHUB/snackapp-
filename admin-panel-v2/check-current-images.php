<?php
/**
 * Vérifier les chemins d'images actuels dans la BDD
 */
require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== ÉTAT ACTUEL DES IMAGES DANS LA BDD ===\n\n";

try {
    $products = Database::fetchAll(
        'SELECT id, name, image FROM products WHERE restaurant_id = ? ORDER BY id',
        [SNACK_RESTAURANT_ID]
    );

    echo "Total produits: " . count($products) . "\n\n";

    $stats = [
        'webp' => 0,
        'png' => 0,
        'jpg' => 0,
        'jpeg' => 0,
        'empty' => 0
    ];

    foreach ($products as $p) {
        $image = $p['image'] ?? '';

        if (empty($image)) {
            echo "#{$p['id']} {$p['name']}: [PAS D'IMAGE]\n";
            $stats['empty']++;
        } else {
            $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            echo "#{$p['id']} {$p['name']}: {$image}\n";

            if (isset($stats[$ext])) {
                $stats[$ext]++;
            }
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STATISTIQUES:\n";
    echo "  WebP: {$stats['webp']}\n";
    echo "  PNG: {$stats['png']}\n";
    echo "  JPG: {$stats['jpg']}\n";
    echo "  JPEG: {$stats['jpeg']}\n";
    echo "  Sans image: {$stats['empty']}\n";

} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}
