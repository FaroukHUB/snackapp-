<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test connexion DB directe<br><br>";

// Charger les credentials
$config = require __DIR__ . '/instances/atelier-pizza/backend-config.php';

echo "Config chargée:<br>";
echo "Host: " . $config['database']['host'] . "<br>";
echo "Name: " . $config['database']['name'] . "<br>";
echo "User: " . $config['database']['user'] . "<br>";
echo "Pass: " . (strlen($config['database']['password']) > 0 ? '***' : 'VIDE!') . "<br><br>";

// Charger Database.php
require_once __DIR__ . '/snackup/backend/Database.php';

echo "Test 1: Connexion avec new Database()...<br>";
try {
    $db = new Database(
        $config['database']['host'],
        $config['database']['name'],
        $config['database']['user'],
        $config['database']['password']
    );
    echo "✅ Connexion réussie avec new Database()<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur new Database(): " . $e->getMessage() . "<br><br>";
}

echo "Test 2: Initialiser Database::init()...<br>";
try {
    Database::init($config['database']);
    echo "✅ Database::init() réussi<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur init(): " . $e->getMessage() . "<br><br>";
}

echo "Test 3: Utiliser Database::getInstance()...<br>";
try {
    $instance = Database::getInstance();
    echo "✅ getInstance() réussi<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur getInstance(): " . $e->getMessage() . "<br><br>";
}

echo "Test 4: Requête SQL...<br>";
try {
    $result = Database::fetchOne('SELECT COUNT(*) as count FROM restaurants');
    echo "✅ Requête OK: " . $result['count'] . " restaurants<br>";
} catch (Exception $e) {
    echo "❌ Erreur requête: " . $e->getMessage() . "<br>";
}
