<?php
require_once __DIR__ . '/config.php';
echo "=== TEST SYNC CATEGORIES CUSTOM ===\n\n";
$runtime = loadMenuRuntime();
$customCatCount = count($runtime['customCategories'] ?? []);
echo "✅ Runtime: {$customCatCount} customCategories\n";
if ($customCatCount > 0) {
    foreach ($runtime['customCategories'] as $id => $cat) {
        echo "  * {$id}: {$cat['name']}\n";
    }
}
$menuJsonPath = __DIR__ . '/../config/menu.json';
$menuData = json_decode(file_get_contents($menuJsonPath), true);
$catCount = count($menuData['menu']['categories'] ?? []);
echo "\n✅ menu.json: {$catCount} catégories\n";
$config = ['menu' => $menuData['menu'] ?? []];
$merged = applyRuntimeToConfig($config, $runtime);
$mergedCount = count($merged['menu']['categories'] ?? []);
echo "\n=== RÉSULTAT ===\n";
echo "Avant fusion: {$catCount}\n";
echo "Après fusion: {$mergedCount}\n";
echo "CustomCategories: {$customCatCount}\n";
if ($mergedCount == $catCount + $customCatCount) {
    echo "\n✅ LA FUSION FONCTIONNE!\n";
} else {
    echo "\n❌ PROBLÈME DANS LA FUSION!\n";
}
