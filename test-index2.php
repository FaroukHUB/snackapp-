<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test 1: Charger backend-config.php<br>";
$config = require __DIR__ . '/instances/atelier-pizza/backend-config.php';
echo "✅ Config chargée<br>";
print_r($config['database']);
echo "<br><br>";

echo "Test 2: Charger Database.php<br>";
require_once __DIR__ . '/snackup/backend/Database.php';
echo "✅ Database.php chargé<br><br>";

echo "Test 3: Connexion à la base<br>";
try {
    $db = new Database(
        $config['database']['host'],
        $config['database']['name'],
        $config['database']['user'],
        $config['database']['password']
    );
    echo "✅ Connexion réussie!<br>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}
