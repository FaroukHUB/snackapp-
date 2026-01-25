<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test bootstrap.php<br><br>";

echo "1. Charger config.php...<br>";
try {
    require_once __DIR__ . '/snackup/admin/config.php';
    echo "✅ config.php chargé<br>";
    echo "RESTAURANT_ID = " . RESTAURANT_ID . "<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur config.php: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
    exit;
}

echo "2. Charger repositories...<br>";
$repos = [
    'RestaurantRepository',
    'MenuRepository', 
    'OrderRepository',
    'CustomerRepository',
    'PromoCodeRepository',
    'LoyaltyRepository'
];

foreach ($repos as $repo) {
    $file = __DIR__ . '/snackup/backend/repositories/' . $repo . '.php';
    if (file_exists($file)) {
        try {
            require_once $file;
            echo "✅ $repo chargé<br>";
        } catch (Exception $e) {
            echo "❌ Erreur $repo: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Fichier manquant: $repo.php<br>";
    }
}

echo "<br>3. Charger bootstrap.php...<br>";
try {
    require_once __DIR__ . '/snackup/admin/bootstrap.php';
    echo "✅ bootstrap.php chargé<br>";
} catch (Exception $e) {
    echo "❌ Erreur bootstrap: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
