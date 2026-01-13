<?php
/**
 * Script de diagnostic API - Le Marvelous
 * Teste l'état de l'API products.php et identifie les problèmes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Diagnostic API</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee;}";
echo ".ok{color:#10b981;}.error{color:#ef4444;}.warning{color:#f59e0b;}";
echo "h2{border-bottom:2px solid #3b82f6;padding-bottom:10px;margin-top:30px;}</style></head><body>";

echo "<h1>🔍 Diagnostic API - Le Marvelous</h1>";

// Test 1: Vérifier que le fichier existe
echo "<h2>1. Fichier products.php</h2>";
$productsFile = __DIR__ . '/api/products.php';
if (file_exists($productsFile)) {
    echo "<p class='ok'>✅ Fichier existe: $productsFile</p>";
    echo "<p>Permissions: " . substr(sprintf('%o', fileperms($productsFile)), -4) . "</p>";
} else {
    echo "<p class='error'>❌ Fichier manquant: $productsFile</p>";
}

// Test 2: Vérifier bootstrap.php
echo "<h2>2. Bootstrap</h2>";
$bootstrapFile = __DIR__ . '/bootstrap.php';
if (file_exists($bootstrapFile)) {
    echo "<p class='ok'>✅ bootstrap.php existe</p>";
    try {
        require_once $bootstrapFile;
        echo "<p class='ok'>✅ bootstrap.php chargé sans erreur</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Erreur au chargement: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>❌ bootstrap.php manquant</p>";
}

// Test 3: Vérifier connexion MySQL
echo "<h2>3. Connexion MySQL</h2>";
$dbConfigFile = dirname(__DIR__) . '/database/config.php';
if (file_exists($dbConfigFile)) {
    echo "<p class='ok'>✅ database/config.php existe</p>";
    $dbConfig = require $dbConfigFile;

    try {
        $pdo = new PDO(
            "mysql:host={$dbConfig['database']['host']};port={$dbConfig['database']['port']};dbname={$dbConfig['database']['dbname']};charset={$dbConfig['database']['charset']}",
            $dbConfig['database']['username'],
            $dbConfig['database']['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<p class='ok'>✅ Connexion MySQL réussie</p>";
        echo "<p>Base de données: {$dbConfig['database']['dbname']}</p>";

        // Test tables
        $tables = ['restaurants', 'categories', 'products', 'orders'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p class='ok'>✅ Table '$table': $count enregistrements</p>";
            } else {
                echo "<p class='warning'>⚠️ Table '$table' manquante</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur MySQL: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='warning'>⚠️ database/config.php manquant (mode JSON)</p>";
}

// Test 4: Vérifier menu.json
echo "<h2>4. Fichiers JSON</h2>";
$menuFile = dirname(__DIR__) . '/config/menu.json';
if (file_exists($menuFile)) {
    echo "<p class='ok'>✅ menu.json existe</p>";
    $menuData = json_decode(file_get_contents($menuFile), true);
    if ($menuData) {
        $catCount = count($menuData['menu']['categories'] ?? []);
        echo "<p class='ok'>✅ menu.json valide: $catCount catégories</p>";
    } else {
        echo "<p class='error'>❌ menu.json invalide (JSON mal formé)</p>";
    }
} else {
    echo "<p class='error'>❌ menu.json manquant</p>";
}

// Test 5: Tester l'API directement
echo "<h2>5. Test API products.php</h2>";
try {
    // Simuler une requête GET
    $_GET['action'] = 'list';

    ob_start();
    include $productsFile;
    $output = ob_get_clean();

    echo "<p class='ok'>✅ API exécutée sans crash PHP</p>";

    // Vérifier si c'est du JSON valide
    $json = json_decode($output, true);
    if ($json !== null) {
        echo "<p class='ok'>✅ Réponse JSON valide</p>";
        echo "<pre style='background:#0f0f1e;padding:15px;border-radius:8px;overflow:auto;max-height:300px;'>";
        echo htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Réponse n'est pas du JSON valide</p>";
        echo "<pre style='background:#0f0f1e;padding:15px;border-radius:8px;overflow:auto;max-height:300px;'>";
        echo htmlspecialchars($output);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur PHP: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 6: Vérifier permissions
echo "<h2>6. Permissions</h2>";
$dirs = [
    dirname(__DIR__) . '/config',
    dirname(__DIR__) . '/database',
    __DIR__ . '/data'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        $color = $writable ? 'ok' : 'error';
        $icon = $writable ? '✅' : '❌';
        echo "<p class='$color'>$icon $dir " . ($writable ? 'writable' : 'NOT writable') . "</p>";
    } else {
        echo "<p class='warning'>⚠️ $dir n'existe pas</p>";
    }
}

// Test 7: Variables d'environnement
echo "<h2>7. Environnement PHP</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "s</p>";
echo "<p>Upload Max Size: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? '<span class="ok">✅ Installé</span>' : '<span class="error">❌ Manquant</span>') . "</p>";

echo "<hr style='margin:40px 0;border:none;border-top:2px solid #3b82f6;'>";
echo "<p style='text-align:center;color:#9ca3af;'>Diagnostic terminé - " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
