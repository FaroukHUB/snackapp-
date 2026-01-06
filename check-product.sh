#!/bin/bash
# Vérifier pourquoi un produit spécifique n'a pas de modal

if [ -z "$1" ]; then
    echo "Usage: ./check-product.sh <product-id>"
    echo ""
    echo "Pour trouver l'ID du produit, regardez dans l'admin ou:"
    echo "  cat config/menu.runtime.json | grep -A 5 'customProducts'"
    exit 1
fi

PRODUCT_ID="$1"

echo "🔍 DIAGNOSTIC: Produit $PRODUCT_ID"
echo "===================================="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

php << ENDPHP
<?php
\$productId = '$PRODUCT_ID';
\$runtime = json_decode(file_get_contents('config/menu.runtime.json'), true);
\$menu = json_decode(file_get_contents('config/menu.json'), true);

echo "1️⃣ Dans RUNTIME.customProducts:\n";
echo "--------------------------------\n";
if (isset(\$runtime['customProducts'][\$productId])) {
    \$prod = \$runtime['customProducts'][\$productId];
    echo "✅ Trouvé\n";
    echo "  Nom: {\$prod['name']}\n";
    echo "  Catégorie: {\$prod['categoryId']}\n";
    echo "  baseIngredients: " . (isset(\$prod['baseIngredients']) ? json_encode(\$prod['baseIngredients']) : 'MANQUANT ❌') . "\n";
    echo "  supplements: " . (isset(\$prod['supplements']) ? json_encode(\$prod['supplements']) : '[]') . "\n";
    \$catId = \$prod['categoryId'];
} else {
    echo "❌ PAS TROUVÉ dans customProducts!\n";
    exit(1);
}

echo "\n2️⃣ Dans MENU.JSON:\n";
echo "------------------\n";
\$foundInMenu = false;
foreach ((\$menu['menu']['categories'] ?? []) as \$cat) {
    foreach ((\$cat['items'] ?? []) as \$item) {
        if ((\$item['id'] ?? '') === \$productId) {
            echo "✅ Trouvé dans catégorie: {\$cat['id']}\n";
            echo "  baseIngredients: " . (isset(\$item['baseIngredients']) ? json_encode(\$item['baseIngredients']) : 'MANQUANT ❌') . "\n";
            \$foundInMenu = true;
            break 2;
        }
    }
}

if (!\$foundInMenu) {
    echo "❌ PAS TROUVÉ dans menu.json!\n";
    echo "   → Le modal ne peut pas s'ouvrir!\n";

    echo "\n3️⃣ Vérification catégorie:\n";
    echo "---------------------------\n";
    \$catExists = false;
    foreach ((\$menu['menu']['categories'] ?? []) as \$cat) {
        if ((\$cat['id'] ?? '') === \$catId) {
            echo "✅ Catégorie '\$catId' existe dans menu.json\n";
            \$catExists = true;
            break;
        }
    }
    if (!\$catExists) {
        echo "❌ Catégorie '\$catId' MANQUANTE dans menu.json!\n";
        echo "   → Créez la catégorie d'abord!\n";
    } else {
        echo "⚠️  La catégorie existe mais le produit n'est pas dedans\n";
        echo "   → Regénération nécessaire!\n";
    }
}

echo "\n==================================\n";
if (\$foundInMenu) {
    echo "✅ Le produit est dans menu.json\n";
    echo "   Si le modal ne s'ouvre pas:\n";
    echo "   - Videz le cache (Ctrl+F5)\n";
    echo "   - Vérifiez la console JavaScript (F12)\n";
} else {
    echo "❌ PROBLÈME: Produit pas dans menu.json\n";
    echo "   → Exécutez: php fix-sync-complete.php\n";
}
ENDPHP
