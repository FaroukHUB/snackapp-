<?php
require_once __DIR__ . '/config.php';

echo "🧪 TEST PROTECTION ANTI-VIDAGE\n";
echo "================================\n\n";

// 1. Charger le menu actuel
$menuPath = __DIR__ . '/../config/menu.json';
$currentMenu = json_decode(file_get_contents($menuPath), true);
$originalCategoriesCount = count($currentMenu['menu']['categories'] ?? []);

echo "✅ Menu actuel : {$originalCategoriesCount} catégories\n\n";

// 2. Créer un runtime vide (simulation du bug)
$emptyRuntime = [
    'products' => [],
    'categories' => [],
    'customCategories' => [],
    'customProducts' => [],
    'deletedProducts' => [],
    'deletedCategories' => [],
    'formules' => [],
    'customFormules' => [],
    'deletedFormules' => [],
    'supplements' => [
        'catalog' => [],
        'defaultForCategories' => []
    ]
];

echo "🔬 Simulation: Tentative de sauvegarde avec runtime vide...\n";

// 3. Tenter de générer le menu (la protection devrait s'activer)
$result = generatePublicMenuJson($emptyRuntime);

echo "\nRésultat: " . ($result ? "✅ Succès" : "❌ Échec") . "\n\n";

// 4. Vérifier que les catégories sont toujours là
$newMenu = json_decode(file_get_contents($menuPath), true);
$newCategoriesCount = count($newMenu['menu']['categories'] ?? []);

echo "📊 RÉSULTAT DU TEST:\n";
echo "-------------------\n";
echo "Catégories avant: {$originalCategoriesCount}\n";
echo "Catégories après: {$newCategoriesCount}\n\n";

if ($newCategoriesCount === $originalCategoriesCount && $newCategoriesCount > 0) {
    echo "✅ ✅ ✅ PROTECTION FONCTIONNE ! ✅ ✅ ✅\n";
    echo "Les catégories ont été préservées malgré le runtime vide.\n";
} else {
    echo "❌ ÉCHEC: Les catégories ont été perdues !\n";
}

// 5. Vérifier les logs
echo "\n📝 Dernières lignes des logs (cherchez 🚨 PROTECTION):\n";
echo "------------------------------------------------------\n";
system('tail -20 ' . ini_get('error_log') . ' 2>/dev/null || echo "Logs non disponibles"');
