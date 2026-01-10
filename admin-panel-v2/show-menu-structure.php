<?php
/**
 * Afficher la structure complète du menu.json
 */

echo "=== STRUCTURE COMPLÈTE MENU.JSON ===\n\n";

$menuPath = __DIR__ . '/../config/menu.json';
$content = file_get_contents($menuPath);
$menu = json_decode($content, true);

if (!$menu) {
    echo "❌ Impossible de lire le menu\n";
    exit(1);
}

echo "📋 Clés principales:\n";
foreach (array_keys($menu) as $key) {
    $type = is_array($menu[$key]) ? 'array[' . count($menu[$key]) . ']' : gettype($menu[$key]);
    echo "   - {$key}: {$type}\n";
}

echo "\n";

// Examiner la structure 'menu' en détail
if (isset($menu['menu'])) {
    echo "🔍 Contenu de 'menu':\n";

    if (is_array($menu['menu']) && count($menu['menu']) > 0) {
        foreach ($menu['menu'] as $idx => $category) {
            if (is_array($category)) {
                echo "\n   Catégorie #{$idx}:\n";
                foreach ($category as $key => $value) {
                    if (is_array($value)) {
                        echo "      {$key}: array[" . count($value) . "]\n";

                        // Si c'est 'items', afficher quelques noms
                        if ($key === 'items' && count($value) > 0) {
                            echo "         Produits:\n";
                            foreach (array_slice($value, 0, 5) as $item) {
                                $name = $item['name'] ?? 'Sans nom';
                                echo "            - {$name}\n";
                            }
                            if (count($value) > 5) {
                                echo "            ... et " . (count($value) - 5) . " autres\n";
                            }
                        }
                    } else {
                        $valueStr = is_string($value) ? $value : json_encode($value);
                        if (strlen($valueStr) > 50) {
                            $valueStr = substr($valueStr, 0, 50) . '...';
                        }
                        echo "      {$key}: {$valueStr}\n";
                    }
                }
            }
        }
    } else {
        echo "   Type: " . gettype($menu['menu']) . "\n";
        echo "   Valeur: " . substr(json_encode($menu['menu']), 0, 200) . "\n";
    }
}

echo "\n";

// Compter TOUS les produits dans toutes les structures possibles
echo "📊 Recherche de produits dans TOUTES les structures:\n";

$totalProducts = 0;

// Structure 1: menu[X].items
if (isset($menu['menu']) && is_array($menu['menu'])) {
    foreach ($menu['menu'] as $cat) {
        if (isset($cat['items']) && is_array($cat['items'])) {
            $totalProducts += count($cat['items']);
        }
    }
}

// Structure 2: categories[X].items
if (isset($menu['categories']) && is_array($menu['categories'])) {
    foreach ($menu['categories'] as $cat) {
        if (isset($cat['items']) && is_array($cat['items'])) {
            $totalProducts += count($cat['items']);
        }
    }
}

echo "   Total produits trouvés: {$totalProducts}\n";

if ($totalProducts === 0) {
    echo "\n❌ AUCUN PRODUIT TROUVÉ!\n";
    echo "   → Le menu est vide ou la structure est incorrecte\n";
    echo "   → Les produits ajoutés via l'admin ont peut-être été perdus\n";
} else {
    echo "\n✅ {$totalProducts} produits trouvés\n";
}

echo "\n✅ Analyse terminée\n";
