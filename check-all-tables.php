<?php
require_once __DIR__ . '/snackup/admin/bootstrap.php';

$pdo = Database::getInstance();

echo "=== TABLES EXISTANTES DANS LA BASE ===\n\n";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "✅ $table\n";
}

echo "\n=== TABLES REQUISES PAR MenuRepository ===\n\n";
$requiredTables = [
    'categories',
    'products', 
    'supplements',
    'category_supplements'
];

foreach ($requiredTables as $table) {
    if (in_array($table, $tables)) {
        echo "✅ $table - EXISTE\n";
    } else {
        echo "❌ $table - MANQUANTE\n";
    }
}

echo "\n=== ANALYSE DU CODE products.php ===\n\n";
echo "Le code products.php essaie d'appeler:\n";
echo "1. MenuRepository::getAllCategories() - OK (table categories existe)\n";
echo "2. MenuRepository::getAllSupplements() - OK (table supplements existe)\n";
echo "3. MenuRepository::getCategorySupplements() - ERREUR (table category_supplements manquante)\n";

echo "\n=== SOLUTION ===\n\n";
echo "Option 1: Créer la table category_supplements\n";
echo "Option 2: Modifier le code pour ne pas requérir cette table (retourner [] si absente)\n";
?>
