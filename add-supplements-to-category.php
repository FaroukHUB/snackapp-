<?php
/**
 * Ajouter des suppléments à une catégorie
 * Les produits de cette catégorie auront automatiquement ces suppléments
 */

if ($argc < 2) {
    echo "Usage: php add-supplements-to-category.php <category-id>\n";
    echo "Exemple: php add-supplements-to-category.php burgers\n";
    exit(1);
}

$categoryId = $argv[1];

$root = '/home/zajr1824/Marvelous.mon-agenceweb.fr';
require_once $root . '/admin-panel-v2/config.php';

echo "🔧 AJOUT DE SUPPLÉMENTS À LA CATÉGORIE: $categoryId\n";
echo "====================================================\n\n";

// Charger runtime et menu
$runtime = loadMenuRuntime();
$menuPath = $root . '/config/menu.json';
$menu = json_decode(file_get_contents($menuPath), true);

// Afficher les suppléments disponibles
echo "📋 Suppléments disponibles:\n";
echo "----------------------------\n";

$saledSupplements = [];
$sucreSupplements = [];

foreach (($menu['supplements']['catalog'] ?? []) as $supId => $sup) {
    $flavor = $sup['flavor'] ?? 'sale';
    $category = $sup['category'] ?? 'autre';

    echo "  [{$sup['status'] ?? 'available'}] $supId: {$sup['name']} - {$sup['price']}€ ($category, $flavor)\n";

    if ($flavor === 'sale') {
        $saledSupplements[] = $supId;
    } else {
        $sucreSupplements[] = $supId;
    }
}

echo "\n";

// Détecter si c'est une catégorie salée ou sucrée
$isSucre = in_array($categoryId, ['crepes-sucrees', 'gaufres', 'bubble-waffle']);

$recommendedSupplements = $isSucre ? $sucreSupplements : $saledSupplements;

echo "💡 Suppléments recommandés pour '$categoryId' (" . ($isSucre ? 'SUCRÉ' : 'SALÉ') . "):\n";
echo "   " . implode(', ', array_slice($recommendedSupplements, 0, 10)) . "\n";
echo "\n";

echo "Voulez-vous ajouter ces suppléments à la catégorie? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));

if (strtolower($line) !== 'y') {
    echo "Annulé.\n";
    exit(0);
}

// Ajouter les suppléments à la catégorie
if (!isset($runtime['supplements']['defaultForCategories'])) {
    $runtime['supplements']['defaultForCategories'] = [];
}

$runtime['supplements']['defaultForCategories'][$categoryId] = $recommendedSupplements;

// Sauvegarder
saveMenuRuntime($runtime);

echo "\n✅ {count($recommendedSupplements)} suppléments ajoutés à la catégorie '$categoryId'\n";
echo "✅ Tous les produits de cette catégorie auront ces suppléments automatiquement!\n";
