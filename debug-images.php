<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC IMAGES ===\n\n";

// 1. Vérifier les fichiers WebP
echo "1. FICHIERS WEBP:\n";
$webpFiles = glob(__DIR__ . '/images/uploads/*.webp');
echo "   Nombre de fichiers .webp: " . count($webpFiles) . "\n";
if (count($webpFiles) > 0) {
    echo "   Exemples:\n";
    foreach (array_slice($webpFiles, 0, 5) as $file) {
        $basename = basename($file);
        $size = filesize($file);
        echo "   - {$basename} ({$size} bytes)\n";
    }
}
echo "\n";

// 2. Vérifier menu.runtime.json
echo "2. MENU.RUNTIME.JSON:\n";
$runtimeJson = __DIR__ . '/config/menu.runtime.json';
if (file_exists($runtimeJson)) {
    $content = file_get_contents($runtimeJson);
    $data = json_decode($content, true);

    // Vérifier quelques produits clés
    $products = ['urgers', 'ptit-dej', 'arguerita', 'hakhchoukha'];
    foreach ($products as $id) {
        if (isset($data['customProducts'][$id]['image'])) {
            echo "   {$id}: {$data['customProducts'][$id]['image']}\n";
        }
    }
} else {
    echo "   ❌ Fichier introuvable!\n";
}
echo "\n";

// 3. Vérifier menu.json
echo "3. MENU.JSON:\n";
$menuJson = __DIR__ . '/config/menu.json';
if (file_exists($menuJson)) {
    $content = file_get_contents($menuJson);

    // Chercher urgers
    if (preg_match('/"image":\s*"([^"]*urgers[^"]*)"/', $content, $matches)) {
        echo "   urgers: {$matches[1]}\n";
    }

    // Chercher hakhchoukha
    if (preg_match('/"image":\s*"([^"]*hakhchoukha[^"]*)"/', $content, $matches)) {
        echo "   hakhchoukha: {$matches[1]}\n";
    }
} else {
    echo "   ❌ Fichier introuvable!\n";
}
echo "\n";

// 4. Vérifier si les fichiers PNG/JPG existent encore
echo "4. ANCIENS FICHIERS (PNG/JPG):\n";
$oldFiles = array_merge(
    glob(__DIR__ . '/images/uploads/*.png') ?: [],
    glob(__DIR__ . '/images/uploads/*.jpg') ?: [],
    glob(__DIR__ . '/images/uploads/*.jpeg') ?: []
);
echo "   Nombre d'anciens fichiers: " . count($oldFiles) . "\n";
if (count($oldFiles) > 0) {
    echo "   ⚠️ Ces fichiers devraient être supprimés:\n";
    foreach (array_slice($oldFiles, 0, 5) as $file) {
        echo "   - " . basename($file) . "\n";
    }
}
echo "\n";

// 5. Test d'accès à un fichier WebP
echo "5. TEST D'ACCÈS:\n";
if (count($webpFiles) > 0) {
    $testFile = $webpFiles[0];
    $basename = basename($testFile);
    $webPath = "images/uploads/{$basename}";
    $fullPath = __DIR__ . "/{$webPath}";

    echo "   Fichier test: {$basename}\n";
    echo "   Chemin complet: {$fullPath}\n";
    echo "   Existe: " . (file_exists($fullPath) ? "✅ OUI" : "❌ NON") . "\n";
    echo "   Lisible: " . (is_readable($fullPath) ? "✅ OUI" : "❌ NON") . "\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
    echo "   URL: https://marvelous.mon-agenceweb.fr/{$webPath}\n";
}
