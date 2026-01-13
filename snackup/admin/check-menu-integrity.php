<?php
/**
 * Vérification de l'intégrité du menu.json
 */

echo "=== VÉRIFICATION INTÉGRITÉ MENU ===\n\n";

$menuPath = __DIR__ . '/../config/menu.json';
$menuSauvegardePath = __DIR__ . '/../config/menu.json.SAUVEGARDE';
$menuRuntimePath = __DIR__ . '/../config/menu.runtime.json';

// 1. Lire menu.json
echo "1️⃣ Lecture menu.json:\n";
$menuContent = file_get_contents($menuPath);
echo "   Taille: " . strlen($menuContent) . " bytes\n";

$menu = json_decode($menuContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "   ❌ ERREUR JSON: " . json_last_error_msg() . "\n";
    echo "   → Le fichier est corrompu!\n";
} else {
    echo "   ✅ JSON valide\n";

    // Afficher la structure
    if (isset($menu['categories'])) {
        echo "   Catégories: " . count($menu['categories']) . "\n";

        if (count($menu['categories']) === 0) {
            echo "   ❌ PROBLÈME: Aucune catégorie!\n";
        } else {
            foreach ($menu['categories'] as $cat) {
                $itemCount = count($cat['items'] ?? []);
                echo "      - {$cat['name']}: {$itemCount} produits\n";
            }
        }
    } else {
        echo "   ❌ PROBLÈME: Clé 'categories' manquante!\n";
        echo "   → Clés présentes: " . implode(', ', array_keys($menu)) . "\n";
    }
}

echo "\n";

// 2. Vérifier sauvegarde
echo "2️⃣ Vérification sauvegarde:\n";

if (file_exists($menuSauvegardePath)) {
    $size = filesize($menuSauvegardePath);
    echo "   ✅ menu.json.SAUVEGARDE existe ({$size} bytes)\n";

    $menuSauvegarde = json_decode(file_get_contents($menuSauvegardePath), true);
    if ($menuSauvegarde && isset($menuSauvegarde['categories'])) {
        $catCount = count($menuSauvegarde['categories']);
        echo "   → {$catCount} catégories dans la sauvegarde\n";

        if ($catCount > 0 && count($menu['categories'] ?? []) === 0) {
            echo "   ⚠️  RECOMMANDATION: Restaurer depuis la sauvegarde!\n";
            echo "      cp config/menu.json.SAUVEGARDE config/menu.json\n";
        }
    }
} else {
    echo "   ⚠️  Pas de sauvegarde trouvée\n";
}

echo "\n";

// 3. Vérifier menu.runtime.json
echo "3️⃣ Vérification menu.runtime.json:\n";

if (file_exists($menuRuntimePath)) {
    $runtimeContent = file_get_contents($menuRuntimePath);
    $runtime = json_decode($runtimeContent, true);

    if ($runtime && isset($runtime['categories'])) {
        $catCount = count($runtime['categories']);
        echo "   → {$catCount} catégories dans runtime\n";

        if ($catCount > count($menu['categories'] ?? [])) {
            echo "   ⚠️  Runtime a PLUS de catégories que menu.json\n";
            echo "      → menu.json est probablement corrompu\n";
        }
    }
}

echo "\n";

// 4. Autres sauvegardes
echo "4️⃣ Recherche d'autres sauvegardes:\n";
$backups = glob(__DIR__ . '/../config/menu.json.*');
if (count($backups) > 0) {
    foreach ($backups as $backup) {
        $basename = basename($backup);
        $size = round(filesize($backup) / 1024, 2);
        echo "   - {$basename} ({$size} KB)\n";
    }
} else {
    echo "   Aucune sauvegarde trouvée\n";
}

echo "\n✅ Vérification terminée\n";
