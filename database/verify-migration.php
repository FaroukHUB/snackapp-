<?php
/**
 * Script de vérification post-migration
 * Affiche les statistiques des données migrées
 */

define('SNACK_ROOT', __DIR__ . '/..');
define('SNACK_RESTAURANT_ID', 1);

require_once __DIR__ . '/Database.php';

echo "🔍 Vérification Migration MySQL\n";
echo "================================\n\n";

// Connexion
$dbConfig = require __DIR__ . '/config.php';
Database::init($dbConfig['database']);

try {
    $pdo = Database::getInstance();
    echo "✅ Connexion MySQL OK\n\n";
} catch (Exception $e) {
    die("❌ Erreur connexion: " . $e->getMessage() . "\n");
}

// Statistiques catégories
echo "📊 CATÉGORIES\n";
echo "-------------\n";

$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as actives,
        SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as supprimees,
        SUM(CASE WHEN flavor = 'sale' THEN 1 ELSE 0 END) as salees,
        SUM(CASE WHEN flavor = 'sucre' THEN 1 ELSE 0 END) as sucrees
    FROM categories
    WHERE restaurant_id = " . SNACK_RESTAURANT_ID
);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  Total        : " . $stats['total'] . "\n";
echo "  Actives      : " . $stats['actives'] . "\n";
echo "  Supprimées   : " . $stats['supprimees'] . "\n";
echo "  Salées       : " . $stats['salees'] . "\n";
echo "  Sucrées      : " . $stats['sucrees'] . "\n";
echo "\n";

// Top 5 catégories
echo "📋 Top 5 catégories (avec produits) :\n";
$stmt = $pdo->query("
    SELECT
        c.name,
        c.flavor,
        c.icon,
        COUNT(p.id) as nb_produits,
        c.deleted_at
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    WHERE c.restaurant_id = " . SNACK_RESTAURANT_ID . "
    GROUP BY c.id
    ORDER BY nb_produits DESC
    LIMIT 5
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status = $row['deleted_at'] ? '❌ SUPPRIMÉE' : '✅';
    $flavor = $row['flavor'] ? "({$row['flavor']})" : '(none)';
    echo "  $status {$row['name']} $flavor - {$row['nb_produits']} produits - {$row['icon']}\n";
}
echo "\n";

// Statistiques produits
echo "📊 PRODUITS\n";
echo "-----------\n";

$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as actifs,
        SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as disponibles
    FROM products
    WHERE restaurant_id = " . SNACK_RESTAURANT_ID
);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  Total        : " . $stats['total'] . "\n";
echo "  Actifs       : " . $stats['actifs'] . "\n";
echo "  Disponibles  : " . $stats['disponibles'] . "\n";
echo "\n";

// Statistiques suppléments
echo "📊 SUPPLÉMENTS\n";
echo "--------------\n";

$stmt = $pdo->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN type = 'sale' THEN 1 ELSE 0 END) as sales,
        SUM(CASE WHEN type = 'sucre' THEN 1 ELSE 0 END) as sucres,
        SUM(CASE WHEN type = 'both' THEN 1 ELSE 0 END) as both
    FROM supplements
    WHERE restaurant_id = " . SNACK_RESTAURANT_ID
);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  Total        : " . $stats['total'] . "\n";
echo "  Salés        : " . $stats['sales'] . "\n";
echo "  Sucrés       : " . $stats['sucres'] . "\n";
echo "  Both         : " . $stats['both'] . "\n";
echo "\n";

// Statistiques associations catégories-suppléments
echo "📊 ASSOCIATIONS CATÉGORIES ↔ SUPPLÉMENTS\n";
echo "-----------------------------------------\n";

$stmt = $pdo->query("
    SELECT COUNT(*) as total
    FROM category_supplements cs
    JOIN categories c ON cs.category_id = c.id
    WHERE c.restaurant_id = " . SNACK_RESTAURANT_ID
);
$total = $stmt->fetchColumn();
echo "  Total associations : $total\n";

// Top 3 catégories avec le plus de suppléments
$stmt = $pdo->query("
    SELECT
        c.name,
        c.flavor,
        COUNT(cs.supplement_id) as nb_supps
    FROM categories c
    LEFT JOIN category_supplements cs ON c.id = cs.category_id
    WHERE c.restaurant_id = " . SNACK_RESTAURANT_ID . "
    GROUP BY c.id
    ORDER BY nb_supps DESC
    LIMIT 3
");
echo "\n  Top 3 catégories (par nb suppléments) :\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $flavor = $row['flavor'] ? "({$row['flavor']})" : '';
    echo "    - {$row['name']} $flavor : {$row['nb_supps']} suppléments\n";
}

echo "\n================================\n";
echo "✅ Vérification terminée !\n";
echo "================================\n";
