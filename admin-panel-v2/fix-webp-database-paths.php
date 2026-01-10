<?php
/**
 * 🔧 CORRECTION CHEMINS DATABASE POUR WEBP
 *
 * Met à jour tous les chemins d'images dans la base de données
 * pour pointer vers les fichiers .webp au lieu de .png/.jpg
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== CORRECTION CHEMINS DATABASE WEBP ===\n\n";

$uploadsDir = __DIR__ . '/../images/uploads';

// 1. Vérifier les produits avec anciennes extensions
$products = Database::fetchAll(
    'SELECT id, name, image FROM products WHERE restaurant_id = ?',
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
    $fullPath = __DIR__ . '/../' . $newImage;

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
    try {
        Database::query(
            'UPDATE products SET image = ? WHERE id = ?',
            [$newImage, $id]
        );
        echo "  ✅ Corrigé\n\n";
        $stats['corrected']++;
    } catch (Exception $e) {
        echo "  ❌ Erreur BDD: {$e->getMessage()}\n\n";
    }
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
    echo "   → Testez votre site pour vérifier les images\n";
} else {
    echo "ℹ️  Aucune correction nécessaire\n";
}
