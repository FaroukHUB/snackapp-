<?php
/**
 * Script de diagnostic - Atelier Pizza
 * À exécuter via SSH pour identifier le problème
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 DIAGNOSTIC ATELIER PIZZA\n";
echo "============================\n\n";

// 1. Vérifier les fichiers de configuration
echo "1️⃣  Vérification des fichiers...\n";
$files = [
    'instances/atelier-pizza/backend-config.php',
    'config/menu.php',
    'config/restaurant.php',
    'snackup/backend/Database.php',
    'snackup/backend/repositories/MenuRepository.php',
    'snackup/backend/repositories/RestaurantRepository.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ MANQUANT: $file\n";
    }
}
echo "\n";

// 2. Charger et vérifier la config
echo "2️⃣  Test configuration backend...\n";
$configPath = __DIR__ . '/instances/atelier-pizza/backend-config.php';
if (file_exists($configPath)) {
    $config = require $configPath;
    echo "   ✅ Config chargée\n";
    echo "   - Restaurant ID: " . $config['app']['restaurant_id'] . "\n";
    echo "   - Database: " . $config['database']['name'] . "\n";
    echo "   - User: " . $config['database']['user'] . "\n";
    echo "   - Host: " . $config['database']['host'] . "\n";
} else {
    echo "   ❌ Config introuvable\n";
    exit(1);
}
echo "\n";

// 3. Test connexion MySQL
echo "3️⃣  Test connexion MySQL...\n";
try {
    $dsn = sprintf(
        "mysql:host=%s;dbname=%s;charset=%s",
        $config['database']['host'],
        $config['database']['name'],
        $config['database']['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['database']['user'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "   ✅ Connexion MySQL OK\n\n";

    // 4. Vérifier les tables
    echo "4️⃣  Vérification des tables...\n";
    $tables = ['restaurants', 'restaurant_settings', 'categories', 'products', 'supplements'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            // Compter les lignes
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "   ✅ $table ($count lignes)\n";
        } else {
            echo "   ❌ Table $table manquante\n";
        }
    }
    echo "\n";

    // 5. Vérifier le restaurant ID 3
    echo "5️⃣  Vérification restaurant ID 3...\n";
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = 3");
    $stmt->execute();
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($restaurant) {
        echo "   ✅ Restaurant trouvé: " . $restaurant['name'] . "\n";
        echo "   - Slug: " . $restaurant['slug'] . "\n";
        echo "   - Téléphone: " . $restaurant['phone'] . "\n";
    } else {
        echo "   ❌ Restaurant ID 3 introuvable dans la DB\n";
        echo "   ⚠️  Vous devez exécuter: mysql < instances/atelier-pizza/init_restaurant.sql\n";
    }
    echo "\n";

    // 6. Vérifier les produits
    echo "6️⃣  Vérification des produits...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE restaurant_id = 3");
    $stmt->execute();
    $productCount = $stmt->fetchColumn();

    if ($productCount > 0) {
        echo "   ✅ $productCount produits trouvés pour restaurant ID 3\n";
    } else {
        echo "   ❌ Aucun produit pour restaurant ID 3\n";
        echo "   ⚠️  Vous devez exécuter: mysql < instances/atelier-pizza/populate_menu.sql\n";
    }
    echo "\n";

} catch (PDOException $e) {
    echo "   ❌ ERREUR MySQL: " . $e->getMessage() . "\n";
    echo "\n";
    echo "   Causes possibles:\n";
    echo "   - Mot de passe incorrect dans backend-config.php\n";
    echo "   - Base de données zajr1824_atelierpizza pas créée\n";
    echo "   - Utilisateur zajr1824_atelierpizza n'a pas les permissions\n";
    echo "\n";
    exit(1);
}

// 7. Test des APIs
echo "7️⃣  Test des APIs...\n";
echo "   Testez manuellement:\n";
echo "   - curl https://atelierpizza.mon-agenceweb.fr/config/restaurant.php\n";
echo "   - curl https://atelierpizza.mon-agenceweb.fr/config/menu.php\n";
echo "\n";

echo "============================\n";
echo "✅ Diagnostic terminé\n";
echo "\n";
echo "📋 PROCHAINES ÉTAPES:\n";
echo "Si des tables ou données manquent, exécutez:\n";
echo "   mysql -u zajr1824_atelierpizza -p zajr1824_atelierpizza < instances/atelier-pizza/init_restaurant.sql\n";
echo "   mysql -u zajr1824_atelierpizza -p zajr1824_atelierpizza < instances/atelier-pizza/populate_menu.sql\n";
echo "\n";
