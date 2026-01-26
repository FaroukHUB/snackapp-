<?php
/**
 * Script de debug pour vérifier les baseIngredients
 * Affiche tous les produits avec leurs baseIngredients
 */

require_once __DIR__ . '/bootstrap.php';

try {
    $pdo = Database::getInstance();

    echo "<h1>Debug - Base Ingredients</h1>";
    echo "<p>Instance: " . INSTANCE_NAME . "</p>";
    echo "<p>Restaurant ID: " . RESTAURANT_ID . "</p>";

    // Vérifier si la colonne existe
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Colonnes de la table products:</h2>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . htmlspecialchars($col) . "</li>";
    }
    echo "</ul>";

    if (!in_array('base_ingredients', $columns)) {
        echo "<p style='color:red;font-weight:bold'>❌ La colonne base_ingredients N'EXISTE PAS !</p>";
        exit;
    }

    echo "<p style='color:green;font-weight:bold'>✅ La colonne base_ingredients existe</p>";

    // Récupérer tous les produits avec baseIngredients
    $stmt = $pdo->prepare("
        SELECT p.id, p.name, p.base_ingredients, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.restaurant_id = ?
        ORDER BY p.id ASC
    ");
    $stmt->execute([RESTAURANT_ID]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Produits (total: " . count($products) . "):</h2>";

    if (empty($products)) {
        echo "<p>Aucun produit trouvé</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Catégorie</th><th>base_ingredients (JSON)</th><th>base_ingredients (parsed)</th></tr>";

        $hasBaseIngredients = false;
        foreach ($products as $product) {
            $baseIngrJson = $product['base_ingredients'];
            $baseIngrParsed = $baseIngrJson ? json_decode($baseIngrJson, true) : null;

            $bgColor = $baseIngrJson ? 'background-color: #c8e6c9' : '';

            if ($baseIngrJson) {
                $hasBaseIngredients = true;
            }

            echo "<tr style='$bgColor'>";
            echo "<td>" . htmlspecialchars($product['id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['category_name'] ?? 'N/A') . "</td>";
            echo "<td style='font-family:monospace;font-size:11px'>" . htmlspecialchars($baseIngrJson ?? 'NULL') . "</td>";
            echo "<td>";
            if ($baseIngrParsed && is_array($baseIngrParsed)) {
                echo "<ul style='margin:0;padding-left:20px'>";
                foreach ($baseIngrParsed as $ing) {
                    echo "<li>" . htmlspecialchars($ing) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "NULL";
            }
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";

        if (!$hasBaseIngredients) {
            echo "<p style='color:orange;font-weight:bold'>⚠️ Aucun produit n'a de baseIngredients définis</p>";
        } else {
            echo "<p style='color:green;font-weight:bold'>✅ Certains produits ont des baseIngredients</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
