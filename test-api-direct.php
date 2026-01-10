<?php
/**
 * Test direct de l'API menu.php via cURL
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST API MENU ===\n\n";

// 1. Récupérer la sortie via HTTP
$url = 'https://marvelous.mon-agenceweb.fr/config/menu.php';
$response = file_get_contents($url);

if (!$response) {
    echo "❌ Impossible de charger menu.php\n";
    exit;
}

$menuData = json_decode($response, true);

if (!$menuData) {
    echo "❌ Erreur de parsing JSON\n";
    echo "Réponse brute (100 premiers caractères):\n";
    echo substr($response, 0, 100) . "\n";
    exit;
}

echo "✅ Menu chargé avec succès\n\n";

// 2. Vérifier customProducts
echo "CUSTOM PRODUCTS:\n";
$productsToCheck = ['urgers', 'ptit-dej', 'arguerita', 'hakhchoukha', 'g-ahal', 'e-fabrik', 'ain-epice', 'rousty'];

if (isset($menuData['customProducts'])) {
    foreach ($productsToCheck as $id) {
        if (isset($menuData['customProducts'][$id])) {
            $image = $menuData['customProducts'][$id]['image'] ?? 'NULL';
            $ext = pathinfo($image, PATHINFO_EXTENSION);
            echo "   {$id}: {$image} (.{$ext})\n";
        } else {
            echo "   {$id}: ❌ NON TROUVÉ\n";
        }
    }
} else {
    echo "   ❌ Pas de customProducts dans la réponse\n";
}

echo "\n";

// 3. Vérifier les produits dans les catégories
echo "PRODUITS DANS CATÉGORIES:\n";
$foundInCategories = [];

if (isset($menuData['menu']['categories'])) {
    foreach ($menuData['menu']['categories'] as $cat) {
        $catId = $cat['id'] ?? '?';

        foreach ($cat['items'] ?? [] as $item) {
            $id = $item['id'] ?? '?';

            if (in_array($id, $productsToCheck)) {
                $image = $item['image'] ?? 'NULL';
                $ext = pathinfo($image, PATHINFO_EXTENSION);
                echo "   [{$catId}] {$id}: {$image} (.{$ext})\n";
                $foundInCategories[] = $id;
            }
        }
    }
}

echo "\n";

// 4. Résumé
echo "RÉSUMÉ:\n";
echo "   Produits testés: " . count($productsToCheck) . "\n";
echo "   Dans customProducts: " . (isset($menuData['customProducts']) ? count(array_intersect($productsToCheck, array_keys($menuData['customProducts']))) : 0) . "\n";
echo "   Dans catégories: " . count($foundInCategories) . "\n";

// 5. Vérifier un fichier WebP spécifique
echo "\nVÉRIFICATION FICHIER:\n";
$testFile = 'images/uploads/urgers-cfce7857.webp';
$fullPath = __DIR__ . '/' . $testFile;
echo "   Fichier: {$testFile}\n";
echo "   Existe: " . (file_exists($fullPath) ? "✅ OUI" : "❌ NON") . "\n";
if (file_exists($fullPath)) {
    echo "   Taille: " . filesize($fullPath) . " bytes\n";
}
