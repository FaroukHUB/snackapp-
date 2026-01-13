<?php
/**
 * Script de migration : ajoute le champ 'flavor' aux suppléments existants dans le runtime
 */

require_once __DIR__ . '/config.php';

echo "🔧 Correction des suppléments dans le runtime...\n\n";

// Charger le runtime
$runtime = loadMenuRuntime();

if (!isset($runtime['supplements']['catalog'])) {
    echo "✅ Aucun supplément dans le runtime\n";
    exit(0);
}

$saledCategories = ['fromage', 'legume', 'viande', 'autre'];
$sucreCategories = ['base', 'croquant', 'fruit', 'prime'];

$updated = 0;
$skipped = 0;

foreach ($runtime['supplements']['catalog'] as $id => &$supplement) {
    // Si le flavor existe déjà, skip
    if (isset($supplement['flavor'])) {
        $skipped++;
        continue;
    }

    // Déterminer le flavor basé sur la catégorie
    if (isset($supplement['category'])) {
        if (in_array($supplement['category'], $saledCategories)) {
            $supplement['flavor'] = 'sale';
            $updated++;
            echo "✅ {$supplement['name']}: flavor = sale\n";
        } elseif (in_array($supplement['category'], $sucreCategories)) {
            $supplement['flavor'] = 'sucre';
            $updated++;
            echo "✅ {$supplement['name']}: flavor = sucre\n";
        } else {
            echo "⚠️  Catégorie inconnue pour {$supplement['name']}: {$supplement['category']}\n";
        }
    } else {
        echo "⚠️  Pas de catégorie pour {$supplement['name']}\n";
    }
}
unset($supplement);

// Sauvegarder le runtime
if ($updated > 0) {
    saveMenuRuntime($runtime);

    // Sync vers menu.json
    require_once __DIR__ . '/sync-menu.php';
    $result = syncMenuStatuses();

    echo "\n✅ Runtime mis à jour et synchronisé!\n";
    echo "   - $updated suppléments corrigés\n";
    echo "   - $skipped suppléments déjà corrects\n";

    if (isset($result['updated'])) {
        echo "   - {$result['updated']} items synchronisés vers menu.json\n";
    }
} else {
    echo "\n✅ Aucune correction nécessaire\n";
    echo "   - $skipped suppléments déjà corrects\n";
}
