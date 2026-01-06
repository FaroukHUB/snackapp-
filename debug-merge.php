<?php
require_once 'admin-panel-v2/config.php';

$runtime = loadMenuRuntime();
$config = loadConfig();

echo "🔍 DEBUG FUSION\n";
echo "================\n\n";

// Vérifier que urgers est dans runtime
echo "1️⃣ urgers dans runtime.customProducts?\n";
if (isset($runtime['customProducts']['urgers'])) {
    echo "   OUI ✅\n";
    echo "   baseIngredients: " . json_encode($runtime['customProducts']['urgers']['baseIngredients'] ?? 'MANQUANT') . "\n";
} else {
    echo "   NON ❌\n";
}

echo "\n2️⃣ Appel de applyRuntimeToConfig...\n";
$merged = applyRuntimeToConfig($config, $runtime);

echo "\n3️⃣ urgers dans le résultat mergé?\n";
$found = false;
foreach ($merged['menu']['categories'] as $cat) {
    foreach ($cat['items'] ?? [] as $item) {
        if (($item['id'] ?? '') === 'urgers') {
            $found = true;
            echo "   OUI ✅ dans catégorie: " . $cat['name'] . "\n";
            echo "   baseIngredients: " . json_encode($item['baseIngredients'] ?? 'MANQUANT') . "\n";
            echo "   Tous les champs: " . json_encode($item, JSON_PRETTY_PRINT) . "\n";
            break 2;
        }
    }
}
if (!$found) {
    echo "   NON ❌\n";
}
