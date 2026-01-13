<?php
/**
 * Vérifier si les produits sont en MySQL
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== VÉRIFICATION PRODUITS MYSQL ===\n\n";

// 1. Vérifier la configuration
echo "1️⃣ Configuration:\n";
echo "   SNACK_USE_JSON: " . (SNACK_USE_JSON ? 'OUI' : 'NON') . "\n";
echo "   SNACK_DB_ERROR: " . (defined('SNACK_DB_ERROR') && SNACK_DB_ERROR ? 'OUI' : 'NON') . "\n";

if (SNACK_USE_JSON) {
    echo "   ⚠️  MODE JSON ACTIVÉ - MySQL n'est pas utilisé!\n\n";
} else {
    echo "   ✅ MODE MYSQL ACTIVÉ\n\n";
}

// 2. Vérifier les produits en base
try {
    echo "2️⃣ Produits en base de données:\n";

    $productCount = Database::fetchOne("SELECT COUNT(*) as count FROM products WHERE restaurant_id = ?", [SNACK_RESTAURANT_ID]);
    echo "   Total produits: {$productCount['count']}\n";

    if ($productCount['count'] > 0) {
        echo "\n   📋 Quelques produits:\n";
        $products = Database::fetchAll(
            "SELECT id, name, category, price FROM products WHERE restaurant_id = ? ORDER BY id DESC LIMIT 10",
            [SNACK_RESTAURANT_ID]
        );

        foreach ($products as $p) {
            echo "      - #{$p['id']}: {$p['name']} ({$p['category']}) - {$p['price']} DA\n";
        }
    } else {
        echo "   ❌ AUCUN PRODUIT EN BASE!\n";
        echo "   → Les produits n'ont pas été migrés vers MySQL\n";
        echo "   → Ou ils ont été supprimés\n";
    }

    echo "\n";

    // 3. Vérifier les catégories
    echo "3️⃣ Catégories en base de données:\n";

    $categoryCount = Database::fetchOne("SELECT COUNT(DISTINCT category) as count FROM products WHERE restaurant_id = ?", [SNACK_RESTAURANT_ID]);
    echo "   Total catégories: {$categoryCount['count']}\n";

    if ($categoryCount['count'] > 0) {
        $categories = Database::fetchAll(
            "SELECT category, COUNT(*) as product_count FROM products WHERE restaurant_id = ? GROUP BY category",
            [SNACK_RESTAURANT_ID]
        );

        echo "\n   📁 Détail par catégorie:\n";
        foreach ($categories as $cat) {
            echo "      - {$cat['category']}: {$cat['product_count']} produits\n";
        }
    }

} catch (Exception $e) {
    echo "   ❌ ERREUR: {$e->getMessage()}\n";
    echo "   → Impossible d'accéder à la base de données\n";
}

echo "\n✅ Vérification terminée\n";
