<?php
/**
 * Ajouter baseIngredients à tous les produits custom existants
 */

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
$runtimePath = $root . '/config/menu.runtime.json';

$runtime = json_decode(file_get_contents($runtimePath), true);

echo "🔧 AJOUT DE baseIngredients AUX PRODUITS EXISTANTS\n";
echo "===================================================\n\n";

$fixed = 0;
$already = 0;

foreach (($runtime['customProducts'] ?? []) as $pid => &$product) {
    if (!isset($product['baseIngredients'])) {
        // Ajouter baseIngredients vide
        $product['baseIngredients'] = [];
        echo "✅ $pid: baseIngredients ajouté\n";
        $fixed++;
    } else {
        echo "   $pid: déjà présent\n";
        $already++;
    }
}

// Sauvegarder
if ($fixed > 0) {
    file_put_contents($runtimePath, json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n===================================================\n";
    echo "✅ {$fixed} produit(s) fixé(s)\n";
    echo "   {$already} produit(s) déjà OK\n";
    echo "\n🔄 Régénération de menu.json...\n";

    // Regénérer menu.json
    require_once $root . '/admin-panel-v2/config.php';
    generatePublicMenuJson($runtime);
    echo "✅ menu.json régénéré\n";
} else {
    echo "\n✅ Tous les produits ont déjà baseIngredients\n";
}

echo "\n📝 NOTE: Les produits auront maintenant le modal complet!\n";
echo "   Pour ajouter des ingrédients spécifiques, modifiez les produits\n";
echo "   via l'admin en envoyant baseIngredients=[\"ingredient1\",\"ingredient2\"]\n";
