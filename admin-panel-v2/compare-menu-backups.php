<?php
/**
 * Comparer les sauvegardes pour trouver la meilleure
 */

echo "=== COMPARAISON SAUVEGARDES MENU ===\n\n";

$backupFiles = [
    'config/menu.json',
    'config/menu.json.SAUVEGARDE',
    'config/menu.json.backup',
    'config/menu.json.backup-20260105-134315'
];

$bestBackup = null;
$maxProducts = 0;

foreach ($backupFiles as $file) {
    $path = __DIR__ . '/../' . $file;

    if (!file_exists($path)) {
        continue;
    }

    echo "📄 " . basename($file) . ":\n";

    $content = file_get_contents($path);
    $data = json_decode($content, true);

    if (!$data) {
        echo "   ❌ JSON invalide\n\n";
        continue;
    }

    // Compter les produits
    $productCount = 0;
    $categoryCount = 0;

    // Vérifier structure 1: categories (ancienne structure correcte)
    if (isset($data['categories'])) {
        $categoryCount = count($data['categories']);
        foreach ($data['categories'] as $cat) {
            $productCount += count($cat['items'] ?? []);
        }
        echo "   ✅ Structure: 'categories' (CORRECTE)\n";
    }
    // Vérifier structure 2: menu (nouvelle structure)
    elseif (isset($data['menu'])) {
        $categoryCount = count($data['menu']);
        foreach ($data['menu'] as $cat) {
            $productCount += count($cat['items'] ?? []);
        }
        echo "   ⚠️  Structure: 'menu' (doit être convertie)\n";
    }
    else {
        echo "   ❌ Structure inconnue\n\n";
        continue;
    }

    echo "   → {$categoryCount} catégories\n";
    echo "   → {$productCount} produits\n";

    // Lister quelques catégories
    $categories = $data['categories'] ?? $data['menu'] ?? [];
    if (count($categories) > 0) {
        echo "   Catégories: ";
        $catNames = array_slice(array_column($categories, 'name'), 0, 3);
        echo implode(', ', $catNames);
        if (count($categories) > 3) {
            echo ", ...";
        }
        echo "\n";
    }

    echo "\n";

    if ($productCount > $maxProducts) {
        $maxProducts = $productCount;
        $bestBackup = $file;
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🏆 MEILLEURE SAUVEGARDE:\n";
echo "   {$bestBackup}\n";
echo "   → {$maxProducts} produits\n";
echo "\n";

if ($bestBackup) {
    $currentPath = __DIR__ . '/../config/menu.json';
    $bestPath = __DIR__ . '/../' . $bestBackup;

    // Vérifier si c'est déjà le fichier actuel
    if ($bestBackup === 'config/menu.json') {
        echo "⚠️  Le menu actuel a la meilleure structure, mais il faut le CONVERTIR\n";
        echo "   Structure actuelle: 'menu' → doit devenir 'categories'\n";
    } else {
        echo "✅ RECOMMANDATION:\n";
        echo "   1. Sauvegarder le menu actuel:\n";
        echo "      cp config/menu.json config/menu.json.before-restore\n";
        echo "\n";
        echo "   2. Restaurer la meilleure sauvegarde:\n";
        echo "      cp {$bestBackup} config/menu.json\n";
        echo "\n";
        echo "   3. Regénérer le runtime:\n";
        echo "      cd admin-panel-v2\n";
        echo "      php -r \"require 'config.php'; generatePublicMenuJson(loadData('menu.json'));\"\n";
    }
}

echo "\n✅ Comparaison terminée\n";
