<?php
require_once 'admin-panel-v2/config.php';

$runtime = loadMenuRuntime();
$config = loadConfig();

echo "🔍 DEBUG avec array_flip CORRECT\n";
echo "==================================\n\n";

$pid = 'urgers';
$p = $runtime['customProducts'][$pid];

// ✅ Reproduire EXACTEMENT la logique de applyRuntimeToConfig
$deletedCategories = isset($runtime['deletedCategories']) && is_array($runtime['deletedCategories']) 
    ? array_flip($runtime['deletedCategories']) 
    : [];

echo "deletedCategories (après flip): " . json_encode($deletedCategories) . "\n\n";

$categoryId = $p['categoryId'] ?? null;
echo "categoryId du produit: $categoryId\n";

// Build catsById EXACTEMENT comme dans le vrai code
$catsById = [];
foreach ($config['menu']['categories'] ?? [] as $i => $cat) {
    $cid = $cat['id'] ?? null;
    if ($cid && !isset($deletedCategories[$cid])) {
        $catsById[$cid] = $i;
    }
}

echo "Categories disponibles: " . json_encode(array_keys($catsById)) . "\n\n";

if (isset($catsById[$categoryId])) {
    echo "✅ La catégorie '$categoryId' existe dans catsById!\n";
    echo "   Index: " . $catsById[$categoryId] . "\n\n";
    
    // Vérifier si le produit existe déjà
    $catIndex = $catsById[$categoryId];
    $exists = false;
    $existingIndex = -1;
    
    foreach ($config['menu']['categories'][$catIndex]['items'] as $idx => $it) {
        if (($it['id'] ?? null) === $pid) {
            $exists = true;
            $existingIndex = $idx;
            break;
        }
    }
    
    if ($exists) {
        echo "✅ Le produit existe déjà à l'index $existingIndex\n";
        echo "   La fusion DEVRAIT se faire!\n";
    } else {
        echo "   Le produit n'existe pas, il sera ajouté\n";
    }
} else {
    echo "❌ PROBLÈME: catégorie '$categoryId' pas dans catsById!\n";
}
