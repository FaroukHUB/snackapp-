<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "=== TEST INDEX ===\n\n";

try {
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    echo "✅ Index chargé (longueur: " . strlen($output) . " car)\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "</pre>";
