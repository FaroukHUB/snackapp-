#!/bin/bash
# Script pour supprimer COMPLÈTEMENT une catégorie de tous les fichiers

if [ -z "$1" ]; then
    echo "Usage: ./remove-category-completely.sh <category-id>"
    echo "Exemple: ./remove-category-completely.sh burgers"
    exit 1
fi

CATEGORY_ID="$1"

echo "🗑️  SUPPRESSION COMPLÈTE de la catégorie: $CATEGORY_ID"
echo "=================================================="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

# 1. Chercher où elle existe
echo "1️⃣ Recherche de '$CATEGORY_ID' dans les fichiers:"
echo "---------------------------------------------------"

if grep -q "\"$CATEGORY_ID\"" config/menu.json; then
    echo "✓ Trouvée dans menu.json"
    FOUND_MENU=1
else
    echo "✗ Absente de menu.json"
    FOUND_MENU=0
fi

if grep -q "\"$CATEGORY_ID\"" config/menu.runtime.json; then
    echo "✓ Trouvée dans menu.runtime.json"
    FOUND_RUNTIME=1
else
    echo "✗ Absente de menu.runtime.json"
    FOUND_RUNTIME=0
fi

if [ $FOUND_MENU -eq 0 ] && [ $FOUND_RUNTIME -eq 0 ]; then
    echo ""
    echo "✅ La catégorie '$CATEGORY_ID' n'existe nulle part. Vous pouvez la créer!"
    exit 0
fi

echo ""
echo "2️⃣ Suppression:"
echo "---------------------------------------------------"

# Sauvegardes
cp config/menu.json config/menu.json.backup-$(date +%Y%m%d-%H%M%S)
cp config/menu.runtime.json config/menu.runtime.json.backup-$(date +%Y%m%d-%H%M%S)

# Créer un script PHP pour supprimer proprement
cat > /tmp/remove-cat-$$.php << 'ENDPHP'
<?php
$categoryId = $argv[1] ?? '';
if (!$categoryId) exit(1);

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';

// 1. Runtime
$runtimePath = $root . '/config/menu.runtime.json';
$runtime = json_decode(file_get_contents($runtimePath), true);

// Supprimer de customCategories
if (isset($runtime['customCategories'][$categoryId])) {
    unset($runtime['customCategories'][$categoryId]);
    echo "✓ Supprimée de runtime.customCategories\n";
}

// Retirer de deletedCategories si présente
if (in_array($categoryId, $runtime['deletedCategories'] ?? [])) {
    $runtime['deletedCategories'] = array_values(array_filter(
        $runtime['deletedCategories'],
        fn($id) => $id !== $categoryId
    ));
    echo "✓ Retirée de runtime.deletedCategories\n";
}

// Supprimer les produits de cette catégorie
foreach (($runtime['customProducts'] ?? []) as $pid => $prod) {
    if (($prod['categoryId'] ?? '') === $categoryId) {
        unset($runtime['customProducts'][$pid]);
        echo "✓ Produit $pid supprimé\n";
    }
}

file_put_contents($runtimePath, json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 2. Menu.json
$menuPath = $root . '/config/menu.json';
$menu = json_decode(file_get_contents($menuPath), true);

$found = false;
foreach (($menu['menu']['categories'] ?? []) as $i => $cat) {
    if (($cat['id'] ?? '') === $categoryId) {
        unset($menu['menu']['categories'][$i]);
        $menu['menu']['categories'] = array_values($menu['menu']['categories']);
        $found = true;
        echo "✓ Supprimée de menu.json.categories\n";
        break;
    }
}

// Supprimer l'icône
if (isset($menu['categoryIcons'][$categoryId])) {
    unset($menu['categoryIcons'][$categoryId]);
    echo "✓ Icône supprimée de menu.json.categoryIcons\n";
}

file_put_contents($menuPath, json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n✅ Catégorie '$categoryId' supprimée complètement!\n";
ENDPHP

php /tmp/remove-cat-$$.php "$CATEGORY_ID"
rm /tmp/remove-cat-$$.php

echo ""
echo "=================================================="
echo "✅ TERMINÉ! Vous pouvez maintenant créer '$CATEGORY_ID' via l'admin"
