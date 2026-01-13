<?php
/**
 * Analyser la taille des images et identifier celles à optimiser
 */

echo "=== ANALYSE TAILLE DES IMAGES ===\n\n";

$uploadsDir = __DIR__ . '/../images/uploads';

if (!is_dir($uploadsDir)) {
    echo "❌ Dossier uploads introuvable: {$uploadsDir}\n";
    exit(1);
}

// Lister toutes les images
$images = glob($uploadsDir . '/*');

if (count($images) === 0) {
    echo "❌ Aucune image trouvée\n";
    exit(1);
}

echo "📊 Total images: " . count($images) . "\n\n";

// Analyser les tailles
$imageData = [];
$totalSize = 0;

foreach ($images as $img) {
    if (!is_file($img)) continue;

    $size = filesize($img);
    $totalSize += $size;

    $imageData[] = [
        'path' => $img,
        'name' => basename($img),
        'size' => $size,
        'sizeKB' => round($size / 1024, 2),
        'sizeMB' => round($size / 1024 / 1024, 2)
    ];
}

// Trier par taille décroissante
usort($imageData, function($a, $b) {
    return $b['size'] - $a['size'];
});

// Statistiques
$avgSize = count($imageData) > 0 ? $totalSize / count($imageData) : 0;

echo "📈 STATISTIQUES:\n";
echo "   Taille totale: " . round($totalSize / 1024 / 1024, 2) . " MB\n";
echo "   Taille moyenne: " . round($avgSize / 1024, 2) . " KB\n";
echo "   Plus grosse: " . $imageData[0]['sizeKB'] . " KB\n";
echo "   Plus petite: " . end($imageData)['sizeKB'] . " KB\n\n";

// Répartition par tranche de taille
$ranges = [
    'very_large' => ['min' => 1024, 'label' => '> 1 MB', 'count' => 0],
    'large' => ['min' => 500, 'max' => 1024, 'label' => '500 KB - 1 MB', 'count' => 0],
    'medium' => ['min' => 200, 'max' => 500, 'label' => '200-500 KB', 'count' => 0],
    'small' => ['min' => 100, 'max' => 200, 'label' => '100-200 KB', 'count' => 0],
    'optimized' => ['max' => 100, 'label' => '< 100 KB', 'count' => 0],
];

foreach ($imageData as $img) {
    $kb = $img['sizeKB'];

    if ($kb >= 1024) {
        $ranges['very_large']['count']++;
    } elseif ($kb >= 500) {
        $ranges['large']['count']++;
    } elseif ($kb >= 200) {
        $ranges['medium']['count']++;
    } elseif ($kb >= 100) {
        $ranges['small']['count']++;
    } else {
        $ranges['optimized']['count']++;
    }
}

echo "📦 RÉPARTITION PAR TAILLE:\n";
foreach ($ranges as $range) {
    $label = str_pad($range['label'], 20);
    echo "   {$label}: {$range['count']} images\n";
}

echo "\n";

// Top 15 plus grosses images
echo "🔝 TOP 15 PLUS GROSSES IMAGES:\n";
foreach (array_slice($imageData, 0, 15) as $idx => $img) {
    $num = $idx + 1;
    echo "   {$num}. " . str_pad($img['name'], 40) . " → {$img['sizeKB']} KB";

    if ($img['sizeKB'] > 500) {
        echo " ⚠️  TRÈS LOURD";
    } elseif ($img['sizeKB'] > 200) {
        echo " ⚠️  LOURD";
    }

    echo "\n";
}

echo "\n";

// Recommandations
echo "💡 RECOMMANDATIONS:\n";

$toLarge = $ranges['very_large']['count'] + $ranges['large']['count'];

if ($toLarge > 10) {
    echo "   🚨 PROBLÈME DE PERFORMANCE:\n";
    echo "      → {$toLarge} images sont > 500 KB\n";
    echo "      → Le chargement du site est ralenti\n";
    echo "      → Impact: pages lentes, modaux lents\n\n";

    echo "   ✅ SOLUTION: Compresser les images\n";
    echo "      Objectif: < 100 KB par image (idéal < 50 KB)\n";
    echo "      Outils: TinyPNG, ImageOptim, ou script PHP\n\n";

    $savings = 0;
    foreach ($imageData as $img) {
        if ($img['sizeKB'] > 100) {
            // Estimation: réduction de 70%
            $potential = $img['size'] * 0.7;
            $savings += $potential;
        }
    }

    $savingsMB = round($savings / 1024 / 1024, 2);
    echo "   📉 Gain potentiel: {$savingsMB} MB → Chargement 3-5x plus rapide\n";

} elseif ($toLarge > 0) {
    echo "   ⚠️  Quelques images sont lourdes mais pas critique\n";
    echo "      → Optionnel: Compresser les {$toLarge} images > 500 KB\n";
} else {
    echo "   ✅ Taille des images OK!\n";
}

echo "\n✅ Analyse terminée\n";
