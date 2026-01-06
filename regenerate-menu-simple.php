<?php
// Régénération SIMPLE sans dépendre de loadConfig()

$runtimePath = __DIR__ . '/config/menu.runtime.json';
$menuPath = __DIR__ . '/config/menu.json';

// Charger le menu.json actuel
$menu = json_decode(file_get_contents($menuPath), true);

// Charger le runtime
$runtime = json_decode(file_get_contents($runtimePath), true);

echo "🔄 RÉGÉNÉRATION SIMPLE\n";
echo "====================\n\n";

// Pour chaque produit custom dans runtime
$updated = 0;
foreach ($runtime['customProducts'] ?? [] as $pid => $runtimeProduct) {
    // Trouver ce produit dans menu.json
    foreach ($menu['menu']['categories'] as $catIdx => $cat) {
        foreach ($cat['items'] ?? [] as $itemIdx => $item) {
            if (($item['id'] ?? '') === $pid) {
                // FUSIONNER runtime dans menu
                $menu['menu']['categories'][$catIdx]['items'][$itemIdx] = array_merge($item, $runtimeProduct);
                echo "✅ $pid: fusionné\n";
                $updated++;
                break 2;
            }
        }
    }
}

// Sauvegarder
file_put_contents($menuPath, json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n✅ $updated produits mis à jour dans menu.json\n";
