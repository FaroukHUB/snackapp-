<?php
/**
 * Script de debug pour identifier les erreurs 500
 * Accès: http://localhost:8888/snackapp/admin-panel-v2/api/debug.php
 */

// Afficher TOUTES les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG SNACKAPP ===\n\n";

// 1. Version PHP
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "   PHP >= 7.4 requis: " . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? "✅ OK" : "❌ ERREUR") . "\n\n";

// 2. Extensions requises
echo "2. Extensions PHP:\n";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    echo "   - $ext: " . (extension_loaded($ext) ? "✅ OK" : "❌ MANQUANT") . "\n";
}
echo "\n";

// 3. Chemins
echo "3. Chemins:\n";
$rootPath = dirname(dirname(__DIR__));
echo "   - SNACK_ROOT: $rootPath\n";
echo "   - Database.php: " . (file_exists($rootPath . '/database/Database.php') ? "✅ OK" : "❌ MANQUANT") . "\n";
echo "   - config.php: " . (file_exists($rootPath . '/database/config.php') ? "✅ OK" : "❌ MANQUANT (copier config.example.php)") . "\n";
echo "   - MenuRepository.php: " . (file_exists($rootPath . '/database/repositories/MenuRepository.php') ? "✅ OK" : "❌ MANQUANT") . "\n";
echo "\n";

// 4. Test de connexion DB
echo "4. Test connexion MySQL:\n";
try {
    $configFile = $rootPath . '/database/config.php';
    if (!file_exists($configFile)) {
        throw new Exception("config.php n'existe pas! Copier config.example.php vers config.php");
    }

    $config = require $configFile;
    $dbConfig = $config['database'];

    echo "   - Host: {$dbConfig['host']}\n";
    echo "   - Port: {$dbConfig['port']}\n";
    echo "   - Database: {$dbConfig['dbname']}\n";
    echo "   - User: {$dbConfig['username']}\n";

    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "   - Connexion: ✅ OK\n\n";

    // 5. Vérifier les tables
    echo "5. Tables MySQL:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    $requiredTables = ['restaurants', 'categories', 'products', 'supplements', 'orders', 'customers'];
    foreach ($requiredTables as $table) {
        $exists = in_array($table, $tables);
        echo "   - $table: " . ($exists ? "✅ OK" : "❌ MANQUANTE") . "\n";
    }

    if (empty($tables)) {
        echo "\n   ⚠️  Aucune table! Exécuter: php database/migrate.php\n";
    }
    echo "\n";

    // 6. Tester le chargement des classes
    echo "6. Test chargement classes:\n";
    try {
        require_once $rootPath . '/database/Database.php';
        echo "   - Database.php: ✅ OK\n";

        require_once $rootPath . '/database/repositories/MenuRepository.php';
        echo "   - MenuRepository.php: ✅ OK\n";

        require_once $rootPath . '/database/repositories/RestaurantRepository.php';
        echo "   - RestaurantRepository.php: ✅ OK\n";

        require_once $rootPath . '/database/repositories/OrderRepository.php';
        echo "   - OrderRepository.php: ✅ OK\n";

        require_once $rootPath . '/database/repositories/CustomerRepository.php';
        echo "   - CustomerRepository.php: ✅ OK\n";
    } catch (Error $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    }
    echo "\n";

    // 7. Tester le bootstrap
    echo "7. Test bootstrap.php:\n";
    try {
        // Reset pour éviter les conflits de define
        require_once dirname(__DIR__) . '/bootstrap.php';
        echo "   - bootstrap.php: ✅ OK\n";
        echo "   - SNACK_USE_JSON: " . (SNACK_USE_JSON ? "true (mode JSON)" : "false (mode MySQL)") . "\n";
        echo "   - SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";

        if (defined('SNACK_DB_ERROR')) {
            echo "   - ⚠️ SNACK_DB_ERROR: " . SNACK_DB_ERROR . "\n";
        }
    } catch (Error $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
        echo "   Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    }
    echo "\n";

    // 8. Tester l'API menu
    echo "8. Test MenuRepository::getFullMenu():\n";
    try {
        Database::init($dbConfig);
        $menu = MenuRepository::getFullMenu(1);
        $catCount = count($menu['menu']['categories'] ?? []);
        echo "   - ✅ OK - $catCount catégories trouvées\n";
    } catch (Exception $e) {
        echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    echo "   ❌ ERREUR CONNEXION: " . $e->getMessage() . "\n";
    echo "\n   Vérifier:\n";
    echo "   - MAMP MySQL est démarré?\n";
    echo "   - Le port est correct (8889 pour MAMP)?\n";
    echo "   - La base de données 'snackapp' existe?\n";
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
