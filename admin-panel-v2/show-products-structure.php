<?php
/**
 * Afficher la structure de la table products et quelques produits
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== STRUCTURE TABLE PRODUCTS ===\n\n";

try {
    // 1. Structure de la table
    echo "1️⃣ Colonnes de la table 'products':\n";
    $columns = Database::fetchAll("SHOW COLUMNS FROM products");

    foreach ($columns as $col) {
        $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['Default'] !== null ? "DEFAULT '{$col['Default']}'" : '';
        echo "   - {$col['Field']}: {$col['Type']} {$nullable} {$default}\n";
    }

    echo "\n";

    // 2. Compter les produits
    echo "2️⃣ Statistiques:\n";
    $count = Database::fetchOne("SELECT COUNT(*) as count FROM products WHERE restaurant_id = ?", [SNACK_RESTAURANT_ID]);
    echo "   Total produits: {$count['count']}\n\n";

    // 3. Afficher quelques produits
    if ($count['count'] > 0) {
        echo "3️⃣ Exemples de produits:\n";
        $products = Database::fetchAll(
            "SELECT * FROM products WHERE restaurant_id = ? ORDER BY id DESC LIMIT 5",
            [SNACK_RESTAURANT_ID]
        );

        foreach ($products as $p) {
            echo "\n   📦 Produit #{$p['id']}:\n";
            echo "      Nom: {$p['name']}\n";
            echo "      Prix: {$p['price']} DA\n";

            // Afficher les colonnes liées à la catégorie
            if (isset($p['category_id'])) {
                echo "      Category ID: {$p['category_id']}\n";
            }
            if (isset($p['category_name'])) {
                echo "      Category Name: {$p['category_name']}\n";
            }
            if (isset($p['category'])) {
                echo "      Category: {$p['category']}\n";
            }

            echo "      Disponible: " . ($p['available'] ? 'OUI' : 'NON') . "\n";

            if (isset($p['image']) && !empty($p['image'])) {
                echo "      Image: {$p['image']}\n";
            }
        }
    }

    echo "\n";

    // 4. Vérifier la table categories
    echo "4️⃣ Vérification table 'categories':\n";

    $categoriesExist = Database::fetchOne("SHOW TABLES LIKE 'categories'");

    if ($categoriesExist) {
        echo "   ✅ Table 'categories' existe\n";

        $catCount = Database::fetchOne("SELECT COUNT(*) as count FROM categories WHERE restaurant_id = ?", [SNACK_RESTAURANT_ID]);
        echo "   Total catégories: {$catCount['count']}\n";

        if ($catCount['count'] > 0) {
            $categories = Database::fetchAll(
                "SELECT id, name, display_order FROM categories WHERE restaurant_id = ? ORDER BY display_order",
                [SNACK_RESTAURANT_ID]
            );

            echo "\n   📁 Liste des catégories:\n";
            foreach ($categories as $cat) {
                $productCount = Database::fetchOne(
                    "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND category_id = ?",
                    [SNACK_RESTAURANT_ID, $cat['id']]
                );

                echo "      - #{$cat['id']}: {$cat['name']} ({$productCount['count']} produits)\n";
            }
        }
    } else {
        echo "   ❌ Table 'categories' n'existe pas\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: {$e->getMessage()}\n";
}

echo "\n✅ Analyse terminée\n";
