<?php
/**
 * Script de diagnostic pour analyser les différences menu.json
 */

define('SNACK_ROOT', __DIR__ . '/..');

// Lire l'ancien et le nouveau menu.json
$menuJsonPath = SNACK_ROOT . '/config/menu.json';

if (!file_exists($menuJsonPath)) {
    die("❌ menu.json introuvable\n");
}

$menuData = json_decode(file_get_contents($menuJsonPath), true);

echo "🔍 DIAGNOSTIC MENU.JSON\n";
echo "======================\n\n";

// 1. Vérifier les icônes de catégories
echo "📌 1. ICÔNES DE CATÉGORIES\n";
echo "   categoryIcons: " . (isset($menuData['categoryIcons']) ? count($menuData['categoryIcons']) : 0) . " entrées\n";
if (isset($menuData['categoryIcons'])) {
    foreach ($menuData['categoryIcons'] as $slug => $icon) {
        echo "      - $slug => $icon\n";
    }
}
echo "\n";

// 2. Vérifier les catégories et leurs icônes
echo "📌 2. CATÉGORIES (icon dans items)\n";
if (isset($menuData['menu']['categories'])) {
    foreach ($menuData['menu']['categories'] as $cat) {
        $catName = $cat['name'] ?? 'sans nom';
        $catIcon = $cat['icon'] ?? 'MANQUANT';
        $catSlug = $cat['slug'] ?? $cat['id'] ?? 'sans slug';
        echo "   - $catName ($catSlug) => icon: $catIcon\n";
    }
}
echo "\n";

// 3. Vérifier les options spéciales
echo "📌 3. OPTIONS SPÉCIALES (pâtisserie, beverages)\n";
$optionsFound = 0;
if (isset($menuData['menu']['categories'])) {
    foreach ($menuData['menu']['categories'] as $cat) {
        if (isset($cat['items'])) {
            foreach ($cat['items'] as $item) {
                $itemName = $item['name'] ?? 'sans nom';
                $itemSlug = $item['slug'] ?? $item['id'] ?? 'sans slug';

                if (isset($item['pâtisserieOptions'])) {
                    echo "   ✅ $itemName ($itemSlug) a pâtisserieOptions (" . count($item['pâtisserieOptions']) . " options)\n";
                    $optionsFound++;
                }
                if (isset($item['beverageOptions'])) {
                    echo "   ✅ $itemName ($itemSlug) a beverageOptions (" . count($item['beverageOptions']) . " options)\n";
                    $optionsFound++;
                }
                if (isset($item['requiresChoice'])) {
                    echo "   ✅ $itemName ($itemSlug) a requiresChoice\n";
                }
            }
        }
    }
}
if ($optionsFound === 0) {
    echo "   ❌ AUCUNE option spéciale trouvée !\n";
}
echo "\n";

// 4. Vérifier les prix
echo "📌 4. PRIX DES PRODUITS\n";
$prixManquants = 0;
$prixZero = 0;
if (isset($menuData['menu']['categories'])) {
    foreach ($menuData['menu']['categories'] as $cat) {
        if (isset($cat['items'])) {
            foreach ($cat['items'] as $item) {
                $itemName = $item['name'] ?? 'sans nom';
                $priceSolo = $item['priceSolo'] ?? null;

                if ($priceSolo === null) {
                    echo "   ❌ $itemName => priceSolo MANQUANT\n";
                    $prixManquants++;
                } elseif ($priceSolo == 0) {
                    echo "   ⚠️  $itemName => priceSolo = 0 (peut être normal pour certains)\n";
                    $prixZero++;
                }
            }
        }
    }
}
echo "   Total prix manquants: $prixManquants\n";
echo "   Total prix à zéro: $prixZero\n";
echo "\n";

// 5. Vérifier les formules
echo "📌 5. FORMULES\n";
$formules = $menuData['formules'] ?? [];
echo "   Nombre de formules: " . count($formules) . "\n";
echo "\n";

// 6. Vérifier les suppléments
echo "📌 6. SUPPLÉMENTS\n";
$supplements = $menuData['supplements']['catalog'] ?? [];
echo "   Nombre de suppléments: " . count($supplements) . "\n";
if (count($supplements) > 0) {
    $premierSupp = array_values($supplements)[0];
    echo "   Exemple prix: " . ($premierSupp['price'] ?? 'MANQUANT') . "\n";
}
echo "\n";

echo "✅ Diagnostic terminé\n";
