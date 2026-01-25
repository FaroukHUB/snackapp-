<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== TEST INDEX ===\n";

try {
    ob_start();
    require_once __DIR__ . '/index.php';
    $output = ob_get_clean();
    echo "✅ Index chargé\n";
    echo "Longueur output: " . strlen($output) . " caractères\n";
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
