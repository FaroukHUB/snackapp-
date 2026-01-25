<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test login.php step by step<br><br>";

echo "1. Charger bootstrap...<br>";
try {
    require_once __DIR__ . '/snackup/admin/bootstrap.php';
    echo "✅ Bootstrap chargé<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    exit;
}

echo "2. Tester getCurrentRestaurant()...<br>";
try {
    $restaurant = getCurrentRestaurant();
    echo "✅ Restaurant: " . ($restaurant['name'] ?? 'N/A') . "<br><br>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

echo "3. Tester RestaurantRepository::verifyAdmin()...<br>";
try {
    $admin = RestaurantRepository::verifyAdmin(3, 'admin', 'admin123');
    if ($admin) {
        echo "✅ Admin trouvé: " . $admin['username'] . "<br><br>";
    } else {
        echo "❌ Admin non trouvé (credentials incorrects?)<br><br>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    echo "Trace:<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "4. Simuler login.php...<br>";
echo "Tout semble OK! Le problème est ailleurs.<br>";
