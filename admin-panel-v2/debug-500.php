<?php
/**
 * Script ultra-simple pour capturer l'erreur HTTP 500
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug HTTP 500</h1>";
echo "<hr>";

// Capturer toutes les erreurs
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<div style='background:red;color:white;padding:10px;margin:10px 0;'>";
    echo "<strong>ERREUR PHP ($errno):</strong><br>";
    echo "Message: $errstr<br>";
    echo "Fichier: $errfile<br>";
    echo "Ligne: $errline<br>";
    echo "</div>";
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<div style='background:darkred;color:white;padding:20px;margin:10px 0;font-size:16px;'>";
        echo "<strong>❌ ERREUR FATALE:</strong><br>";
        echo "Type: " . $error['type'] . "<br>";
        echo "Message: " . htmlspecialchars($error['message']) . "<br>";
        echo "Fichier: " . htmlspecialchars($error['file']) . "<br>";
        echo "Ligne: " . $error['line'] . "<br>";
        echo "</div>";
    }
});

echo "<h2>Étape 1: Test bootstrap.php</h2>";
try {
    require_once __DIR__ . '/bootstrap.php';
    echo "✅ Bootstrap chargé<br>";
} catch (Throwable $e) {
    echo "❌ Erreur: " . htmlspecialchars($e->getMessage()) . "<br>";
    die();
}

echo "<h2>Étape 2: Tentative de chargement index.php</h2>";
echo "Chargement en cours...<br><br>";

// Forcer l'affichage immédiat
flush();
ob_flush();

// Inclure index.php (cela devrait déclencher l'erreur)
require __DIR__ . '/index.php';
