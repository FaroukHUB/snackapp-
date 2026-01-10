<?php
/**
 * Test: Vérifier ce que menu.php retourne vraiment
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST SORTIE MENU.PHP ===\n\n";

// 1. Lire la sortie de menu.php (exécuté)
ob_start();
include(__DIR__ . '/config/menu.php');
$menuPhpOutput = ob_get_clean();
$menuData = json_decode($menuPhpOutput, true);

if (!$menuData) {
    echo "❌ Erreur de parsing JSON\n";
    exit;
}

echo "1. CUSTOM PRODUCTS (depuis menu.php):\n";
if (isset($menuData['customProducts'])) {
    $customProducts = $menuData['customProducts'];
    $productsToCheck = ['urgers', 'ptit-dej', 'arguerita', 'hakhchoukha', 'g-ahal', 'e-fabrik'];

    foreach ($productsToCheck as $id) {
        if (isset($customProducts[$id]['image'])) {
            echo "   {$id}: {$customProducts[$id]['image']}\n";
        } else {
            echo "   {$id}: ❌ PAS D'IMAGE\n";
        }
    }
} else {
    echo "   ❌ Pas de customProducts\n";
}

echo "\n2. PRODUCTS DANS CATÉGORIES (depuis menu.php):\n";
if (isset($menuData['menu']['categories'])) {
    foreach ($menuData['menu']['categories'] as $cat) {
        if (empty($cat['items'])) continue;

        foreach ($cat['items'] as $item) {
            $id = $item['id'] ?? '?';
            $image = $item['image'] ?? 'NULL';

            // Filtrer pour afficher seulement ceux qui nous intéressent
            if (in_array($id, ['urgers', 'ptit-dej', 'arguerita', 'hakhchoukha'])) {
                $ext = pathinfo($image, PATHINFO_EXTENSION);
                echo "   [{$cat['id']}] {$id}: {$image} (.{$ext})\n";
            }
        }
    }
}

echo "\n3. VÉRIFICATION DIRECTE FICHIERS JSON:\n";
echo "   menu.runtime.json urgers: ";
$runtime = json_decode(file_get_contents(__DIR__ . '/config/menu.runtime.json'), true);
echo $runtime['customProducts']['urgers']['image'] ?? 'NULL';
echo "\n";

echo "   menu.json urgers: ";
$menuJson = json_decode(file_get_contents(__DIR__ . '/config/menu.json'), true);
// Chercher urgers dans les catégories
$found = false;
foreach ($menuJson['menu']['categories'] ?? [] as $cat) {
    foreach ($cat['items'] ?? [] as $item) {
        if (($item['id'] ?? '') === 'urgers') {
            echo $item['image'] ?? 'NULL';
            $found = true;
            break 2;
        }
    }
}
if (!$found) {
    // Chercher dans customProducts du JSON
    if (isset($menuJson['customProducts']['urgers']['image'])) {
        echo $menuJson['customProducts']['urgers']['image'];
    } else {
        echo "NOT FOUND";
    }
}
echo "\n";
