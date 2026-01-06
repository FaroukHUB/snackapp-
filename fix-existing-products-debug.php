<?php
$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
$runtimePath = $root . '/config/menu.runtime.json';

$runtime = json_decode(file_get_contents($runtimePath), true);

echo "🔧 AJOUT DE baseIngredients AUX PRODUITS EXISTANTS (DEBUG)\n";
echo "==========================================================\n\n";

$fixed = 0;
$already = 0;

foreach (($runtime['customProducts'] ?? []) as $pid => &$product) {
    if (!isset($product['baseIngredients'])) {
        $product['baseIngredients'] = [];
        echo "✅ $pid: baseIngredients ajouté\n";
        $fixed++;
    } else {
        echo "   $pid: déjà présent\n";
        $already++;
    }
}
unset($product); // ✅ IMPORTANT: Détruire la référence!

echo "\n🔍 VÉRIFICATION AVANT SAUVEGARDE:\n";
echo "urgers a baseIngredients? " . (isset($runtime['customProducts']['urgers']['baseIngredients']) ? 'OUI ✅' : 'NON ❌') . "\n";
if (isset($runtime['customProducts']['urgers']['baseIngredients'])) {
    echo "Valeur: " . json_encode($runtime['customProducts']['urgers']['baseIngredients']) . "\n";
}

// Sauvegarder
if ($fixed > 0) {
    file_put_contents($runtimePath, json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "\n✅ Runtime sauvegardé\n";
    
    // Vérifier immédiatement après la sauvegarde
    $verif = json_decode(file_get_contents($runtimePath), true);
    echo "🔍 VÉRIFICATION APRÈS SAUVEGARDE:\n";
    echo "urgers a baseIngredients dans le fichier? " . (isset($verif['customProducts']['urgers']['baseIngredients']) ? 'OUI ✅' : 'NON ❌') . "\n";
    
    echo "\n🔄 Régénération de menu.json...\n";
    require_once $root . '/admin-panel-v2/config.php';
    generatePublicMenuJson($runtime);
    echo "✅ menu.json régénéré\n";
} else {
    echo "\n✅ Tous les produits ont déjà baseIngredients\n";
}
