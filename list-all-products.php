<?php
/**
 * Lister TOUS les produits avec images depuis l'API
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== TOUS LES PRODUITS AVEC IMAGES ===\n\n";

$url = 'https://marvelous.mon-agenceweb.fr/config/menu.php';
$response = file_get_contents($url);
$menuData = json_decode($response, true);

if (!$menuData) {
    echo "❌ Erreur\n";
    exit;
}

$totalProducts = 0;
$withImages = 0;
$webpImages = 0;
$pngJpgImages = 0;

echo "PRODUITS DANS LE MENU:\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($menuData['menu']['categories'] ?? [] as $cat) {
    $catName = $cat['name'] ?? '?';
    $catId = $cat['id'] ?? '?';

    if (empty($cat['items'])) continue;

    echo "📁 {$catName} ({$catId}):\n";

    foreach ($cat['items'] as $item) {
        $totalProducts++;
        $name = $item['name'] ?? '?';
        $id = $item['id'] ?? '?';
        $image = $item['image'] ?? '';

        if (!empty($image)) {
            $withImages++;
            $ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));

            if ($ext === 'webp') {
                $webpImages++;
                echo "   ✅ {$name} ({$id}): {$image}\n";
            } else {
                $pngJpgImages++;
                echo "   ⚠️  {$name} ({$id}): {$image} [.{$ext}]\n";
            }
        } else {
            echo "   ❌ {$name} ({$id}): PAS D'IMAGE\n";
        }
    }
    echo "\n";
}

echo str_repeat("=", 80) . "\n";
echo "STATISTIQUES:\n";
echo "   Total produits: {$totalProducts}\n";
echo "   Avec images: {$withImages}\n";
echo "   - WebP: {$webpImages}\n";
echo "   - PNG/JPG: {$pngJpgImages}\n";
echo "   Sans image: " . ($totalProducts - $withImages) . "\n";
