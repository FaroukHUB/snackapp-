<?php
/**
 * Script de migration : Données JSON → MySQL
 * Migre le menu depuis le-marvelous.config.js + runtime vers MySQL
 */

define('SNACK_ROOT', __DIR__ . '/..');
define('SNACK_RESTAURANT_ID', 2); // ID du restaurant Le Marvelous (table restaurants)

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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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

// Charger les icônes depuis menu.json (une seule fois)
$categoryIcons = $menuData['categoryIcons'] ?? [];
echo "✅ Icônes chargées : " . count($categoryIcons) . "\n\n";

// Utiliser directement les données de menu.json (déjà fusionnées)
$merged = ['menu' => $menuData['menu'] ?? ['categories' => []]];
echo "📊 Total catégories à migrer : " . count($merged['menu']['categories']) . "\n\n";

// ========================================
// PRÉPARATION : Fusionner les associations catégories-suppléments
// ========================================
$allAssociations = [];

// 1. Charger les associations par défaut de menu.json
if (isset($menuData['supplements']['defaultForCategories'])) {
    $allAssociations = $menuData['supplements']['defaultForCategories'];
}

// 2. Fusionner avec les associations de runtime (ajouts/modifications de l'admin)
if (isset($runtime['supplements']['defaultForCategories'])) {
    foreach ($runtime['supplements']['defaultForCategories'] as $catSlug => $supps) {
        if (!isset($allAssociations[$catSlug])) {
            $allAssociations[$catSlug] = $supps;
        } else {
            // Fusionner et dédupliquer
            $allAssociations[$catSlug] = array_unique(array_merge($allAssociations[$catSlug], $supps));
        }
    }
}

echo "✅ " . count($allAssociations) . " catégories avec suppléments assignés\n\n";

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
    if (!$slug) {
        echo "  ⚠ Catégorie sans slug ignorée\n";
        continue;
    }

    try {
        // Déterminer le flavor depuis les suppléments assignés (associations fusionnées)
        $flavor = null;
        if (isset($allAssociations[$slug])) {
            $supps = $allAssociations[$slug];
            // Si contient des supps salés (fromages, viandes) → sale
            if (in_array('sup-mix-fromages', $supps) || in_array('sup-viande-hachee', $supps)) {
                $flavor = 'sale';
            }
            // Si contient des supps sucrés (nutella, fruits) → sucre
            elseif (in_array('sup-nutella', $supps) || in_array('sup-chocolat', $supps)) {
                $flavor = 'sucre';
            }
        }

        // Récupérer l'icône depuis les icônes pré-chargées
        $icon = $categoryIcons[$slug] ?? 'fa-utensils';

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
        echo "  ✓ {$cat['name']} (flavor: " . ($flavor ?: 'none') . ", icon: $icon)\n";
    } catch (Exception $e) {
        echo "  ❌ ERREUR pour catégorie '$slug': " . $e->getMessage() . "\n";
        die("Migration arrêtée\n");
    }
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

        try {
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
        } catch (Exception $e) {
            echo "  ❌ ERREUR produit '$productSlug': " . $e->getMessage() . "\n";
        }
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

// Charger les suppléments depuis menu.json (pas runtime)
if (isset($menuData['supplements']['catalog'])) {
    foreach ($menuData['supplements']['catalog'] as $slug => $supp) {
        try {
            // Utiliser le flavor déjà présent dans les données JSON
            $type = $supp['flavor'] ?? 'both';

            // Fallback si pas de flavor défini
            if (!in_array($type, ['sale', 'sucre', 'both'])) {
                $type = 'both';
            }

            $stmt->execute([
                ':restaurant_id' => SNACK_RESTAURANT_ID,
                ':slug' => $slug,
                ':name' => $supp['name'] ?? $slug,
                ':price' => $supp['price'] ?? 0,
                ':type' => $type,
                ':sort_order' => $supplementCount,
                ':status' => $supp['status'] ?? 'available'
            ]);

            $suppId = $pdo->lastInsertId();
            if (!$suppId) {
                $fetch = $pdo->prepare("SELECT id FROM supplements WHERE restaurant_id = ? AND slug = ?");
                $fetch->execute([SNACK_RESTAURANT_ID, $slug]);
                $suppId = $fetch->fetchColumn();
            }

            $supplementMapping[$slug] = $suppId;
            $supplementCount++;
        } catch (Exception $e) {
            echo "  ❌ ERREUR supplément '$slug': " . $e->getMessage() . "\n";
        }
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

// Utiliser les associations fusionnées calculées au début
// (déjà fusionnées depuis menu.json + runtime)
foreach ($allAssociations as $categorySlug => $suppSlugs) {
    if (!isset($categoryMapping[$categorySlug])) continue;

    $categoryId = $categoryMapping[$categorySlug];

    foreach ($suppSlugs as $suppSlug) {
        if (!isset($supplementMapping[$suppSlug])) continue;

        try {
            $stmt->execute([
                ':category_id' => $categoryId,
                ':supplement_id' => $supplementMapping[$suppSlug]
            ]);
            $assocCount++;
        } catch (Exception $e) {
            echo "  ⚠ Association ignorée ($categorySlug → $suppSlug): " . $e->getMessage() . "\n";
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
