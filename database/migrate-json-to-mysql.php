<?php
/**
 * Script de migration : Données JSON → MySQL
 * Migre le menu depuis le-marvelous.config.js + runtime vers MySQL
 */

define('SNACK_ROOT', __DIR__ . '/..');
define('SNACK_RESTAURANT_ID', 1); // ID du restaurant Le Marvelous

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../admin-panel-v2/config.php';

echo "🚀 Migration JSON → MySQL\n";
echo "========================\n\n";

// Chargement config MySQL
$dbConfig = require __DIR__ . '/config.php';
Database::init($dbConfig['database']);

// Connexion MySQL
try {
    $pdo = Database::getInstance();
    echo "✅ Connexion MySQL OK\n\n";
} catch (Exception $e) {
    die("❌ Erreur connexion MySQL: " . $e->getMessage() . "\n");
}

// Chargement données JSON
echo "📂 Chargement des données depuis menu.json...\n";

$menuJsonPath = SNACK_ROOT . '/config/menu.json';
if (!file_exists($menuJsonPath)) {
    die("❌ menu.json introuvable : $menuJsonPath\n");
}

$menuData = json_decode(file_get_contents($menuJsonPath), true);
if (!$menuData) {
    die("❌ Impossible de parser menu.json\n");
}

echo "✅ menu.json chargé : " . count($menuData['menu']['categories'] ?? []) . " catégories\n";

// Charger aussi le runtime pour les deletedCategories
$runtime = loadMenuRuntime();
$deletedCategories = array_flip($runtime['deletedCategories'] ?? []);
echo "✅ Catégories supprimées : " . count($runtime['deletedCategories'] ?? []) . "\n\n";

// Utiliser directement les données de menu.json (déjà fusionnées)
$merged = ['menu' => $menuData['menu'] ?? ['categories' => []]];
echo "📊 Total catégories à migrer : " . count($merged['menu']['categories']) . "\n\n";

// ========================================
// MIGRATION CATÉGORIES
// ========================================
echo "🔄 Migration des catégories...\n";
$categoryMapping = []; // slug JSON → ID MySQL

$stmt = $pdo->prepare("
    INSERT INTO categories
    (restaurant_id, name, slug, description, icon, flavor, sort_order, is_active, deleted_at)
    VALUES (:restaurant_id, :name, :slug, :description, :icon, :flavor, :sort_order, :is_active, :deleted_at)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        description = VALUES(description),
        icon = VALUES(icon),
        flavor = VALUES(flavor),
        is_active = VALUES(is_active),
        deleted_at = VALUES(deleted_at)
");

$categoryCount = 0;
foreach ($merged['menu']['categories'] as $index => $cat) {
    $slug = $cat['id'] ?? null;
    if (!$slug) continue;

    // Déterminer le flavor depuis les suppléments assignés
    $flavor = null;
    if (isset($runtime['supplements']['defaultForCategories'][$slug])) {
        $supps = $runtime['supplements']['defaultForCategories'][$slug];
        // Si contient des supps salés (fromages, viandes) → sale
        if (in_array('sup-mix-fromages', $supps) || in_array('sup-viande-hachee', $supps)) {
            $flavor = 'sale';
        }
        // Si contient des supps sucrés (nutella, fruits) → sucre
        elseif (in_array('sup-nutella', $supps) || in_array('sup-chocolat', $supps)) {
            $flavor = 'sucre';
        }
    }

    // Récupérer l'icône depuis menu.json
    $icon = 'fa-utensils';
    if (file_exists(SNACK_ROOT . '/config/menu.json')) {
        $menuData = json_decode(file_get_contents(SNACK_ROOT . '/config/menu.json'), true);
        $icon = $menuData['categoryIcons'][$slug] ?? 'fa-utensils';
    }

    $deletedAt = isset($deletedCategories[$slug]) ? date('Y-m-d H:i:s') : null;

    $stmt->execute([
        ':restaurant_id' => SNACK_RESTAURANT_ID,
        ':name' => $cat['name'] ?? $slug,
        ':slug' => $slug,
        ':description' => $cat['description'] ?? null,
        ':icon' => $icon,
        ':flavor' => $flavor,
        ':sort_order' => $cat['order'] ?? $index,
        ':is_active' => $deletedAt ? 0 : 1,
        ':deleted_at' => $deletedAt
    ]);

    $categoryId = $pdo->lastInsertId();
    if (!$categoryId) {
        // Catégorie existe déjà, récupérer son ID
        $fetch = $pdo->prepare("SELECT id FROM categories WHERE restaurant_id = ? AND slug = ?");
        $fetch->execute([SNACK_RESTAURANT_ID, $slug]);
        $categoryId = $fetch->fetchColumn();
    }

    $categoryMapping[$slug] = $categoryId;
    $categoryCount++;
    echo "  ✓ {$cat['name']} (flavor: " . ($flavor ?: 'none') . ")\n";
}

echo "✅ $categoryCount catégories migrées\n\n";

// ========================================
// MIGRATION PRODUITS
// ========================================
echo "🔄 Migration des produits...\n";

$stmt = $pdo->prepare("
    INSERT INTO products
    (restaurant_id, category_id, name, slug, description, image, price_solo, price_menu, status, sort_order)
    VALUES (:restaurant_id, :category_id, :name, :slug, :description, :image, :price_solo, :price_menu, :status, :sort_order)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        description = VALUES(description),
        price_solo = VALUES(price_solo),
        price_menu = VALUES(price_menu),
        status = VALUES(status)
");

$productCount = 0;
foreach ($merged['menu']['categories'] as $cat) {
    $categorySlug = $cat['id'] ?? null;
    if (!$categorySlug || !isset($categoryMapping[$categorySlug])) continue;

    $categoryId = $categoryMapping[$categorySlug];

    foreach ($cat['items'] ?? [] as $index => $product) {
        $productSlug = $product['id'] ?? null;
        if (!$productSlug) continue;

        $stmt->execute([
            ':restaurant_id' => SNACK_RESTAURANT_ID,
            ':category_id' => $categoryId,
            ':name' => $product['name'] ?? $productSlug,
            ':slug' => $productSlug,
            ':description' => $product['description'] ?? null,
            ':image' => $product['image'] ?? null,
            ':price_solo' => $product['priceSolo'] ?? 0,
            ':price_menu' => $product['priceMenu'] ?? null,
            ':status' => ($product['status'] ?? 'available') === 'available' ? 'available' : 'unavailable',
            ':sort_order' => $index
        ]);

        $productCount++;
    }
}

echo "✅ $productCount produits migrés\n\n";

// ========================================
// MIGRATION SUPPLÉMENTS
// ========================================
echo "🔄 Migration des suppléments...\n";

$stmt = $pdo->prepare("
    INSERT INTO supplements
    (restaurant_id, slug, name, price, type, sort_order, status)
    VALUES (:restaurant_id, :slug, :name, :price, :type, :sort_order, :status)
    ON DUPLICATE KEY UPDATE
        name = VALUES(name),
        price = VALUES(price),
        type = VALUES(type)
");

$supplementMapping = []; // slug → ID
$supplementCount = 0;

if (isset($runtime['supplements']['catalog'])) {
    foreach ($runtime['supplements']['catalog'] as $slug => $supp) {
        // Déterminer le type (sale/sucre)
        $type = 'both';
        if (strpos($slug, 'sup-nutella') !== false || strpos($slug, 'sup-chocolat') !== false
            || strpos($slug, 'sup-fruit') !== false || strpos($slug, 'sup-confiture') !== false) {
            $type = 'sucre';
        } elseif (strpos($slug, 'sup-fromage') !== false || strpos($slug, 'sup-viande') !== false
            || strpos($slug, 'sup-oeuf') !== false || strpos($slug, 'sup-jambon') !== false) {
            $type = 'sale';
        }

        $stmt->execute([
            ':restaurant_id' => SNACK_RESTAURANT_ID,
            ':slug' => $slug,
            ':name' => $supp['name'] ?? $slug,
            ':price' => $supp['price'] ?? 0,
            ':type' => $type,
            ':sort_order' => $supplementCount,
            ':status' => 'available'
        ]);

        $suppId = $pdo->lastInsertId();
        if (!$suppId) {
            $fetch = $pdo->prepare("SELECT id FROM supplements WHERE restaurant_id = ? AND slug = ?");
            $fetch->execute([SNACK_RESTAURANT_ID, $slug]);
            $suppId = $fetch->fetchColumn();
        }

        $supplementMapping[$slug] = $suppId;
        $supplementCount++;
    }
}

echo "✅ $supplementCount suppléments migrés\n\n";

// ========================================
// ASSOCIATIONS CATÉGORIES-SUPPLÉMENTS
// ========================================
echo "🔄 Création des associations catégories ↔ suppléments...\n";

$stmt = $pdo->prepare("
    INSERT IGNORE INTO category_supplements (category_id, supplement_id)
    VALUES (:category_id, :supplement_id)
");

$assocCount = 0;
if (isset($runtime['supplements']['defaultForCategories'])) {
    foreach ($runtime['supplements']['defaultForCategories'] as $categorySlug => $suppSlugs) {
        if (!isset($categoryMapping[$categorySlug])) continue;

        $categoryId = $categoryMapping[$categorySlug];

        foreach ($suppSlugs as $suppSlug) {
            if (!isset($supplementMapping[$suppSlug])) continue;

            $stmt->execute([
                ':category_id' => $categoryId,
                ':supplement_id' => $supplementMapping[$suppSlug]
            ]);
            $assocCount++;
        }
    }
}

echo "✅ $assocCount associations créées\n\n";

// ========================================
// RÉCAPITULATIF
// ========================================
echo "========================\n";
echo "✅ MIGRATION TERMINÉE !\n";
echo "========================\n";
echo "📊 Statistiques :\n";
echo "  - Catégories : $categoryCount\n";
echo "  - Produits : $productCount\n";
echo "  - Suppléments : $supplementCount\n";
echo "  - Associations : $assocCount\n\n";

echo "🎯 Prochaines étapes :\n";
echo "  1. Vérifier les données dans MySQL\n";
echo "  2. Modifier l'API pour utiliser MySQL\n";
echo "  3. Tester l'admin panel\n\n";
