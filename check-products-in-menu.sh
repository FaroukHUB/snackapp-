#!/bin/bash
# Vérifier si les produits custom sont dans menu.json

echo "🔍 DIAGNOSTIC: Produits custom dans menu.json"
echo "=============================================="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

echo "1️⃣ Produits custom dans RUNTIME:"
echo "---------------------------------"
php << 'ENDPHP'
<?php
$runtime = json_decode(file_get_contents('config/menu.runtime.json'), true);
$customProducts = $runtime['customProducts'] ?? [];

echo "Nombre: " . count($customProducts) . "\n\n";

foreach ($customProducts as $id => $prod) {
    $catId = $prod['categoryId'] ?? '?';
    echo "  • $id (catégorie: $catId)\n";
    echo "    Nom: {$prod['name']}\n";
}
ENDPHP

echo ""
echo "2️⃣ Ces produits sont-ils dans menu.json?"
echo "-----------------------------------------"

php << 'ENDPHP'
<?php
$runtime = json_decode(file_get_contents('config/menu.runtime.json'), true);
$menu = json_decode(file_get_contents('config/menu.json'), true);

$customProducts = $runtime['customProducts'] ?? [];

foreach ($customProducts as $id => $prod) {
    $found = false;

    foreach (($menu['menu']['categories'] ?? []) as $cat) {
        foreach (($cat['items'] ?? []) as $item) {
            if (($item['id'] ?? '') === $id) {
                echo "✅ $id trouvé dans menu.json (catégorie: {$cat['id']})\n";
                $found = true;
                break 2;
            }
        }
    }

    if (!$found) {
        echo "❌ $id MANQUANT dans menu.json!\n";
    }
}

if (count($customProducts) === 0) {
    echo "ℹ️  Aucun produit custom à vérifier\n";
}
ENDPHP

echo ""
echo "=============================================="
echo "📝 Si des produits sont MANQUANTS, le modal"
echo "   ne peut pas s'ouvrir car getProduct() ne"
echo "   les trouve pas!"
