<?php
/**
 * Script de diagnostic pour identifier l'erreur HTTP 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test de diagnostic admin panel</h2>\n";

// Test 1: Bootstrap
echo "<h3>1. Test bootstrap.php</h3>\n";
try {
    require_once __DIR__ . '/bootstrap.php';
    echo "✅ bootstrap.php chargé<br>\n";
} catch (Exception $e) {
    echo "❌ Erreur bootstrap: " . $e->getMessage() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
    exit;
}

// Test 2: Constantes
echo "<h3>2. Constantes définies</h3>\n";
echo "SNACK_ROOT: " . (defined('SNACK_ROOT') ? SNACK_ROOT : 'NON DÉFINI') . "<br>\n";
echo "SNACK_USE_JSON: " . (defined('SNACK_USE_JSON') ? (SNACK_USE_JSON ? 'true' : 'false') : 'NON DÉFINI') . "<br>\n";

// Test 3: Session
echo "<h3>3. Test session</h3>\n";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session active<br>\n";
} else {
    echo "⚠️ Session non active<br>\n";
}

// Test 4: Vérifier erreurs PHP
echo "<h3>4. Dernières erreurs PHP</h3>\n";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "Log: $errorLog<br>\n";
    $lines = file($errorLog);
    $recent = array_slice($lines, -20);
    echo "<pre>" . htmlspecialchars(implode('', $recent)) . "</pre>\n";
} else {
    echo "Pas de fichier error_log configuré<br>\n";
}

// Test 5: Tester config.php directement
echo "<h3>5. Test loadConfig()</h3>\n";
try {
    $config = loadConfig();
    if ($config) {
        echo "✅ loadConfig() OK<br>\n";
        echo "Restaurant: " . ($config['name'] ?? 'N/A') . "<br>\n";
    } else {
        echo "❌ loadConfig() retourne NULL<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur loadConfig: " . $e->getMessage() . "<br>\n";
}

// Test 6: Variables globales
echo "<h3>6. Variables globales</h3>\n";
echo "config: " . (isset($GLOBALS['config']) ? 'SET' : 'NON SET') . "<br>\n";
echo "primaryColor: " . ($GLOBALS['primaryColor'] ?? 'NON SET') . "<br>\n";
