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

// Test 4: Include index.php
echo "<h3>4. Test chargement index.php</h3>\n";
echo "Tentative de chargement...<br>\n";
ob_start();
try {
    include __DIR__ . '/index.php';
    echo "✅ index.php chargé<br>\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erreur index.php: " . $e->getMessage() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (ParseError $e) {
    ob_end_clean();
    echo "❌ Erreur de syntaxe dans index.php:<br>\n";
    echo "Fichier: " . $e->getFile() . "<br>\n";
    echo "Ligne: " . $e->getLine() . "<br>\n";
    echo "Message: " . $e->getMessage() . "<br>\n";
}
