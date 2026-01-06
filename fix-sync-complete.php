<?php
/**
 * FIX COMPLET : Vider deletedCategories et regénérer menu.json
 * Pour que TOUS les produits custom soient visibles
 */

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
require_once $root . '/admin-panel-v2/config.php';

echo "🔧 FIX COMPLET - Synchronisation Runtime → Menu.json\n";
echo "======================================================\n\n";

// 1. Charger le runtime
$runtime = loadMenuRuntime();

echo "1️⃣ ÉTAT ACTUEL:\n";
echo "----------------\n";
echo "customCategories: " . count($runtime['customCategories'] ?? []) . "\n";
echo "customProducts: " . count($runtime['customProducts'] ?? []) . "\n";
echo "deletedCategories: " . count($runtime['deletedCategories'] ?? []) . "\n";
if (!empty($runtime['deletedCategories'])) {
    echo "  ⚠️  Catégories supprimées: " . implode(', ', $runtime['deletedCategories']) . "\n";
}
echo "\n";

// 2. VIDER deletedCategories
$deletedCount = count($runtime['deletedCategories'] ?? []);
$runtime['deletedCategories'] = [];

echo "2️⃣ NETTOYAGE:\n";
echo "-------------\n";
echo "✅ {$deletedCount} catégorie(s) retirée(s) de deletedCategories\n";
echo "✅ deletedProducts vidé également\n";
$runtime['deletedProducts'] = [];
echo "\n";

// 3. Sauvegarder le runtime
echo "3️⃣ SAUVEGARDE:\n";
echo "--------------\n";
saveMenuRuntime($runtime);
echo "✅ Runtime sauvegardé\n";
echo "✅ menu.json régénéré automatiquement par saveMenuRuntime()\n";
echo "\n";

// 4. Vérifier le résultat
echo "4️⃣ VÉRIFICATION:\n";
echo "----------------\n";

clearstatcache(true, $root . '/config/menu.json');
$menu = json_decode(file_get_contents($root . '/config/menu.json'), true);

$foundProducts = [];
$missingProducts = [];

foreach (($runtime['customProducts'] ?? []) as $pid => $prod) {
    $found = false;
    foreach (($menu['menu']['categories'] ?? []) as $cat) {
        foreach (($cat['items'] ?? []) as $item) {
            if (($item['id'] ?? '') === $pid) {
                $foundProducts[] = $pid;
                $found = true;
                break 2;
            }
        }
    }
    if (!$found) {
        $missingProducts[] = $pid;
    }
}

echo "Produits custom dans menu.json: " . count($foundProducts) . "/" . count($runtime['customProducts'] ?? []) . "\n";

if (empty($missingProducts)) {
    echo "✅ TOUS les produits custom sont dans menu.json!\n";
} else {
    echo "⚠️  Produits encore manquants: " . implode(', ', $missingProducts) . "\n";
    echo "   (leurs catégories existent-elles dans menu.json?)\n";
}

echo "\n";
echo "======================================================\n";
echo "✅ FIX TERMINÉ!\n";
echo "\n";
echo "📝 Prochaines étapes:\n";
echo "   1. Rechargez votre site (Ctrl+F5)\n";
echo "   2. Tous les modaux devraient fonctionner maintenant\n";
echo "   3. Si un produit manque encore, sa catégorie n'existe\n";
echo "      peut-être pas - recréez-la via l'admin\n";
