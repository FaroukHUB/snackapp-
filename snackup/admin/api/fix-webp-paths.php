<?php
/**
 * API: Corriger les chemins WebP dans la base de données
 * Usage: GET /admin-panel-v2/api/fix-webp-paths.php
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== CORRECTION CHEMINS DATABASE WEBP ===\n\n";

if (SNACK_USE_JSON) {
    echo "❌ Mode JSON activé - base de données non disponible\n";
    exit(1);
}

$uploadsDir = SNACK_ROOT . '/images/uploads';

try {
    // Récupérer tous les produits
    $products = Database::fetchAll(
        'SELECT id, name, image FROM products WHERE restaurant_id = ? ORDER BY id',
        [SNACK_RESTAURANT_ID]
    );

    echo "📊 Total produits: " . count($products) . "\n\n";

    $stats = [
        'total' => count($products),
        'corrected' => 0,
        'already_webp' => 0,
        'webp_not_found' => 0,
        'no_image' => 0
    ];

    foreach ($products as $product) {
        $id = $product['id'];
        $name = $product['name'];
        $currentImage = $product['image'];

        // Pas d'image
        if (empty($currentImage)) {
            $stats['no_image']++;
            continue;
        }

        // Déjà en WebP
        if (preg_match('/\.webp$/i', $currentImage)) {
            $stats['already_webp']++;
            continue;
        }

        // Convertir le chemin vers .webp
        $newImage = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $currentImage);
        $fullPath = SNACK_ROOT . '/' . $newImage;

        echo "#{$id} {$name}:\n";
        echo "  Ancien: {$currentImage}\n";
        echo "  Nouveau: {$newImage}\n";

        // Vérifier que le fichier WebP existe
        if (!file_exists($fullPath)) {
            echo "  ❌ WebP introuvable: {$fullPath}\n\n";
            $stats['webp_not_found']++;
            continue;
        }

        // Mettre à jour la base de données
        Database::query(
            'UPDATE products SET image = ? WHERE id = ?',
            [$newImage, $id]
        );

        echo "  ✅ Corrigé\n\n";
        $stats['corrected']++;
    }

    // Rapport final
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RAPPORT:\n\n";
    echo "   Total produits: {$stats['total']}\n";
    echo "   ✅ Corrigés: {$stats['corrected']}\n";
    echo "   ✓  Déjà en WebP: {$stats['already_webp']}\n";
    echo "   ⚠️  WebP manquant: {$stats['webp_not_found']}\n";
    echo "   -  Sans image: {$stats['no_image']}\n\n";

    if ($stats['corrected'] > 0) {
        echo "✅ Base de données mise à jour!\n";
        echo "   → Les images WebP devraient maintenant s'afficher sur le site\n";
        echo "   → Faites Ctrl+Shift+R pour vider le cache du navigateur\n";
    } else {
        echo "ℹ️  Aucune correction nécessaire\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
