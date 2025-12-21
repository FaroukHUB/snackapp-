<?php
/**
 * Fichier de diagnostic pour l'admin panel
 * Accès: https://snack.mon-agenceweb.fr/admin-panel-v2/test.php
 */

echo "<h1>Diagnostic Admin Panel V2</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;background:#1a1a1a;color:#fff;} .ok{color:#4ade80;} .error{color:#ef4444;} pre{background:#2d2d2d;padding:10px;border-radius:5px;}</style>";

// 1. Vérifier PHP
echo "<h2>1. Version PHP</h2>";
echo "<p class='ok'>✓ PHP " . phpversion() . "</p>";

// 2. Vérifier fichiers
echo "<h2>2. Fichiers présents</h2>";
$files = [
    'config.php',
    'login.php',
    'index.php',
    'logout.php',
    'webhook.php',
    'api/orders.php',
    'api/customers.php',
    'api/products.php',
    'api/stats.php',
    'assets/js/app.js',
    'assets/js/customers.js',
    'assets/js/products.js',
];

foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p class='ok'>✓ $file</p>";
    } else {
        echo "<p class='error'>✗ $file - MANQUANT</p>";
    }
}

// 3. Vérifier permissions dossier data/
echo "<h2>3. Dossier data/</h2>";
$dataDir = __DIR__ . '/data/';

if (!is_dir($dataDir)) {
    echo "<p class='error'>✗ Dossier data/ n'existe pas</p>";
    echo "<p>Création du dossier...</p>";
    mkdir($dataDir, 0755, true);
} else {
    echo "<p class='ok'>✓ Dossier data/ existe</p>";
}

if (is_writable($dataDir)) {
    echo "<p class='ok'>✓ Dossier data/ accessible en écriture</p>";
} else {
    echo "<p class='error'>✗ Dossier data/ NON accessible en écriture</p>";
    echo "<p>Essai de changement de permissions...</p>";
    chmod($dataDir, 0755);
}

// 4. Vérifier fichiers JSON
echo "<h2>4. Fichiers JSON</h2>";
$jsonFiles = ['orders.json', 'customers.json', 'loyalty_points.json', 'settings.json', 'tgtg.json'];

foreach ($jsonFiles as $jsonFile) {
    $path = $dataDir . $jsonFile;
    if (file_exists($path)) {
        echo "<p class='ok'>✓ $jsonFile - " . filesize($path) . " bytes</p>";
    } else {
        echo "<p class='error'>✗ $jsonFile - MANQUANT (sera créé au premier accès)</p>";
    }
}

// 5. Vérifier config
echo "<h2>5. Configuration</h2>";
$configFile = '../config/fabrik-burger.config.js';
if (file_exists($configFile)) {
    echo "<p class='ok'>✓ Config restaurant trouvé</p>";

    $content = file_get_contents($configFile);
    if (preg_match('/const SNACK_CONFIG\s*=\s*({.*?});/s', $content, $matches)) {
        echo "<p class='ok'>✓ Config valide</p>";
        $config = json_decode($matches[1], true);
        if ($config) {
            echo "<pre>" . htmlspecialchars(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ Format config invalide</p>";
    }
} else {
    echo "<p class='error'>✗ Fichier config restaurant non trouvé</p>";
}

// 6. Test API
echo "<h2>6. Test APIs</h2>";

// Test orders API
$testUrl = 'api/orders.php?action=list';
echo "<p>Test: <code>$testUrl</code></p>";

// 7. Vérifier sessions
echo "<h2>7. Sessions PHP</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='ok'>✓ Sessions PHP actives</p>";
} else {
    session_start();
    echo "<p class='ok'>✓ Session démarrée</p>";
}

echo "<h2>✅ Diagnostic terminé</h2>";
echo "<p><a href='index.php' style='color:#4ade80;'>← Retour à l'admin panel</a></p>";
?>
