<?php
/**
 * Recréer automatiquement les catégories manquantes pour les produits orphelins
 */

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
require_once $root . '/admin-panel-v2/config.php';

echo "🔧 RÉPARATION : Recréer catégories manquantes\n";
echo "==============================================\n\n";

$runtime = loadMenuRuntime();
$menu = json_decode(file_get_contents($root . '/config/menu.json'), true);

// Trouver toutes les catégories utilisées par les produits custom
$usedCategories = [];
foreach (($runtime['customProducts'] ?? []) as $pid => $prod) {
    $catId = $prod['categoryId'] ?? null;
    if ($catId) {
        $usedCategories[$catId] = ($usedCategories[$catId] ?? 0) + 1;
    }
}

echo "1️⃣ Catégories utilisées par les produits:\n";
echo "-------------------------------------------\n";
foreach ($usedCategories as $catId => $count) {
    echo "  • $catId ($count produit(s))\n";
}
echo "\n";

// Vérifier quelles catégories existent
$existingCategories = [];

// Dans menu.json
foreach (($menu['menu']['categories'] ?? []) as $cat) {
    $existingCategories[$cat['id']] = 'menu.json';
}

// Dans customCategories du runtime
foreach (($runtime['customCategories'] ?? []) as $catId => $cat) {
    $existingCategories[$catId] = 'runtime.customCategories';
}

echo "2️⃣ Vérification existence:\n";
echo "---------------------------\n";

$missingCategories = [];
foreach ($usedCategories as $catId => $count) {
    if (isset($existingCategories[$catId])) {
        echo "  ✅ $catId existe dans {$existingCategories[$catId]}\n";
    } else {
        echo "  ❌ $catId MANQUANTE!\n";
        $missingCategories[] = $catId;
    }
}
echo "\n";

if (empty($missingCategories)) {
    echo "✅ Toutes les catégories existent déjà!\n";
    exit(0);
}

echo "3️⃣ CRÉATION des catégories manquantes:\n";
echo "---------------------------------------\n";

foreach ($missingCategories as $catId) {
    // Créer un nom lisible depuis l'ID
    $name = ucwords(str_replace(['-', '_'], ' ', $catId));

    $runtime['customCategories'][$catId] = [
        'id' => $catId,
        'name' => $name,
        'description' => "Catégorie $name",
        'items' => []
    ];

    echo "  ➕ Créée: $catId ($name)\n";
}
echo "\n";

echo "4️⃣ SAUVEGARDE:\n";
echo "--------------\n";
saveMenuRuntime($runtime);
echo "✅ Runtime sauvegardé\n";
echo "✅ menu.json régénéré\n";
echo "\n";

echo "5️⃣ VÉRIFICATION FINALE:\n";
echo "------------------------\n";

clearstatcache(true, $root . '/config/menu.json');
$menuFinal = json_decode(file_get_contents($root . '/config/menu.json'), true);

$foundCount = 0;
$missingCount = 0;

foreach (($runtime['customProducts'] ?? []) as $pid => $prod) {
    $found = false;
    foreach (($menuFinal['menu']['categories'] ?? []) as $cat) {
        foreach (($cat['items'] ?? []) as $item) {
            if (($item['id'] ?? '') === $pid) {
                $found = true;
                break 2;
            }
        }
    }
    if ($found) {
        $foundCount++;
    } else {
        $missingCount++;
        echo "  ⚠️  $pid encore manquant\n";
    }
}

echo "\n";
echo "==============================================\n";
echo "Produits dans menu.json: $foundCount/" . count($runtime['customProducts'] ?? []) . "\n";

if ($missingCount === 0) {
    echo "✅ TOUS LES PRODUITS SONT MAINTENANT VISIBLES!\n";
    echo "\n";
    echo "📝 Rechargez votre site (Ctrl+F5) et testez les modaux!\n";
} else {
    echo "⚠️  $missingCount produit(s) encore manquant(s)\n";
}
