<?php
require_once __DIR__ . '/database/Database.php';

// Charger et initialiser la config
$config = require __DIR__ . '/database/config.php';
Database::init($config['database']);

$json = json_decode(file_get_contents(__DIR__ . '/config/menu.json'), true);
$restaurantId = 1;

echo "<pre>=== IMPORT MENU ===\n\n";

// Vider les tables existantes
Database::query("DELETE FROM product_supplements WHERE product_id IN (SELECT id FROM products WHERE restaurant_id = ?)", [$restaurantId]);
Database::query("DELETE FROM products WHERE restaurant_id = ?", [$restaurantId]);
Database::query("DELETE FROM supplements WHERE restaurant_id = ?", [$restaurantId]);
Database::query("DELETE FROM categories WHERE restaurant_id = ?", [$restaurantId]);
echo "Tables vidées.\n";

// Importer les catégories
$catMap = [];
$catOrder = 1;
foreach ($json['menu']['categories'] as $cat) {
    $catId = Database::insert('categories', [
        'restaurant_id' => $restaurantId,
        'name' => $cat['name'],
        'slug' => $cat['id'],
        'description' => $cat['description'] ?? null,
        'sort_order' => $catOrder++,
        'is_active' => 1
    ]);
    $catMap[$cat['id']] = $catId;
    echo "Catégorie: {$cat['name']} (ID: $catId)\n";
}

// Importer les produits
$prodCount = 0;
foreach ($json['menu']['categories'] as $cat) {
    $catId = $catMap[$cat['id']];
    $prodOrder = 1;
    foreach ($cat['items'] ?? [] as $item) {
        $priceSolo = $item['priceSolo'] ?? $item['price'] ?? 0;
        $priceMenu = $item['priceMenu'] ?? null;
        
        Database::insert('products', [
            'restaurant_id' => $restaurantId,
            'category_id' => $catId,
            'name' => $item['name'],
            'slug' => $item['id'],
            'description' => $item['description'] ?? null,
            'image' => $item['image'] ?? null,
            'price_solo' => $priceSolo,
            'price_menu' => $priceMenu,
            'status' => $item['status'] ?? 'available',
            'sort_order' => $prodOrder++
        ]);
        $prodCount++;
    }
}
echo "\n$prodCount produits importés.\n";

// Importer les suppléments
$supMap = [];
$supOrder = 1;
foreach ($json['supplements']['catalog'] ?? [] as $supId => $sup) {
    $newId = Database::insert('supplements', [
        'restaurant_id' => $restaurantId,
        'name' => $sup['name'],
        'price' => $sup['price'],
        'status' => $sup['status'] ?? 'available',
        'sort_order' => $supOrder++
    ]);
    $supMap[$supId] = $newId;
    echo "Supplément: {$sup['name']} (ID: $newId)\n";
}

echo "\n=== IMPORT TERMINÉ ===\n";
echo "Catégories: " . count($catMap) . "\n";
echo "Produits: $prodCount\n";
echo "Suppléments: " . count($supMap) . "\n</pre>";
