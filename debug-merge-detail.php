<?php
require_once 'admin-panel-v2/config.php';

$runtime = loadMenuRuntime();
$config = loadConfig();

echo "🔍 DEBUG DÉTAILLÉ - Pourquoi urgers est skippé?\n";
echo "==============================================\n\n";

$pid = 'urgers';
$p = $runtime['customProducts'][$pid] ?? null;

if (!$p) {
    echo "❌ urgers pas dans customProducts!\n";
    exit;
}

echo "✅ urgers trouvé dans customProducts\n";
echo "   Données: " . json_encode($p, JSON_PRETTY_PRINT) . "\n\n";

// Reproduire la logique de applyRuntimeToConfig
$deletedProducts = $runtime['deletedProducts'] ?? [];
$deletedCategories = $runtime['deletedCategories'] ?? [];

// Check 1
if (!is_array($p)) {
    echo "❌ SKIP: pas un array\n";
} else {
    echo "✅ CHECK 1: est un array\n";
}

// Check 2
if (isset($deletedProducts[$pid])) {
    echo "❌ SKIP: dans deletedProducts\n";
} else {
    echo "✅ CHECK 2: pas dans deletedProducts\n";
}

// Check 3
$categoryId = $p['categoryId'] ?? null;
echo "\n   categoryId du produit: " . json_encode($categoryId) . "\n";

if (!$categoryId) {
    echo "❌ SKIP: pas de categoryId\n";
} else {
    echo "✅ CHECK 3: a un categoryId\n";
    
    // Build catsById comme dans applyRuntimeToConfig
    $catsById = [];
    foreach ($config['menu']['categories'] ?? [] as $i => $cat) {
        $cid = $cat['id'] ?? null;
        if ($cid && !isset($deletedCategories[$cid])) {
            $catsById[$cid] = $i;
        }
    }
    
    echo "\n   Categories disponibles (catsById): " . json_encode(array_keys($catsById)) . "\n";
    
    // Check 4
    if (!isset($catsById[$categoryId])) {
        echo "❌ SKIP: categoryId '$categoryId' pas dans catsById!\n";
        echo "   Vérifiez si la catégorie existe dans menu.json\n";
    } else {
        echo "✅ CHECK 4: categoryId existe dans catsById\n";
        
        // Check 5
        if (isset($deletedCategories[$categoryId])) {
            echo "❌ SKIP: categoryId dans deletedCategories\n";
            echo "   deletedCategories: " . json_encode($deletedCategories) . "\n";
        } else {
            echo "✅ CHECK 5: categoryId pas dans deletedCategories\n";
            echo "\n🤔 Tous les checks passent... le produit devrait être mergé!\n";
        }
    }
}
