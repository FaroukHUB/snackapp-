<?php
/**
 * Diagnostic de performance - Vérifier la taille du menu
 */

echo "=== DIAGNOSTIC PERFORMANCE ===\n\n";

// 1. Taille du menu.json
$menuPath = __DIR__ . '/../config/menu.json';
$menuRuntimePath = __DIR__ . '/../config/menu.runtime.json';

echo "1️⃣ Taille des fichiers menu:\n";

if (file_exists($menuPath)) {
    $size = filesize($menuPath);
    $sizeKB = round($size / 1024, 2);
    echo "   menu.json: {$sizeKB} KB\n";

    $menu = json_decode(file_get_contents($menuPath), true);
    $categoriesCount = count($menu['categories'] ?? []);
    $productsCount = 0;
    foreach ($menu['categories'] ?? [] as $cat) {
        $productsCount += count($cat['items'] ?? []);
    }
    echo "   → {$categoriesCount} catégories, {$productsCount} produits\n";
} else {
    echo "   ❌ menu.json introuvable\n";
}

if (file_exists($menuRuntimePath)) {
    $size = filesize($menuRuntimePath);
    $sizeKB = round($size / 1024, 2);
    echo "   menu.runtime.json: {$sizeKB} KB\n";
} else {
    echo "   ❌ menu.runtime.json introuvable\n";
}

echo "\n2️⃣ Test de vitesse chargement menu:\n";

$start = microtime(true);
$menu = json_decode(file_get_contents($menuPath), true);
$end = microtime(true);
$duration = round(($end - $start) * 1000, 2);

echo "   Temps de chargement: {$duration}ms\n";

if ($duration > 100) {
    echo "   ⚠️  LENT! Le fichier menu est trop gros\n";
} else {
    echo "   ✅ OK\n";
}

echo "\n3️⃣ Vérification cache rate_limit:\n";
$cacheFiles = glob(__DIR__ . '/cache/rate_limit_*.json');
echo "   Fichiers cache: " . count($cacheFiles) . "\n";

if (count($cacheFiles) > 100) {
    echo "   ⚠️  Beaucoup de fichiers cache! Nettoyer avec:\n";
    echo "      cd admin-panel-v2/cache && find . -name 'rate_limit_*.json' -mtime +7 -delete\n";
}

echo "\n4️⃣ Analyse des images produits:\n";
$imagesPath = __DIR__ . '/../images/uploads';
if (is_dir($imagesPath)) {
    $images = glob($imagesPath . '/*');
    $totalSize = 0;
    foreach ($images as $img) {
        if (is_file($img)) {
            $totalSize += filesize($img);
        }
    }
    $totalSizeMB = round($totalSize / 1024 / 1024, 2);
    echo "   Total images: " . count($images) . " fichiers\n";
    echo "   Taille totale: {$totalSizeMB} MB\n";

    if (count($images) > 200) {
        echo "   ⚠️  Beaucoup d'images! Nettoyer les anciennes images.\n";
    }
}

echo "\n✅ Diagnostic terminé\n";
