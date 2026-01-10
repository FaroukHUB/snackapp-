<?php
/**
 * Mettre à jour menu.json avec les chemins des images WebP existantes
 */

$menuJsonPath = __DIR__ . '/config/menu.json';
$uploadsDir = __DIR__ . '/images/uploads';

// Charger menu.json
$menuData = json_decode(file_get_contents($menuJsonPath), true);

// Lister tous les fichiers WebP
$webpFiles = glob($uploadsDir . '/*.webp');
$webpByProductId = [];

foreach ($webpFiles as $file) {
    $basename = basename($file);
    // Extraire l'ID du produit (avant le hash)
    if (preg_match('/^(.+?)-[a-f0-9]{8}\.webp$/', $basename, $matches)) {
        $productId = $matches[1];
        $webpByProductId[$productId] = 'images/uploads/' . $basename;
    }
}

echo "Fichiers WebP trouvés: " . count($webpByProductId) . "\n";

$updated = 0;

// Parcourir les catégories et produits
foreach ($menuData['menu']['categories'] as &$category) {
    foreach ($category['items'] as &$item) {
        $id = $item['id'] ?? null;
        if (!$id) continue;

        // Si le produit n'a pas d'image ET qu'un fichier WebP existe
        if (empty($item['image']) && isset($webpByProductId[$id])) {
            $item['image'] = $webpByProductId[$id];
            echo "✅ {$id}: {$webpByProductId[$id]}\n";
            $updated++;
        }
    }
}

if ($updated > 0) {
    // Sauvegarder menu.json
    file_put_contents(
        $menuJsonPath,
        json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
    echo "\n✅ {$updated} produits mis à jour dans menu.json\n";
} else {
    echo "\nℹ️  Aucune mise à jour nécessaire\n";
}
