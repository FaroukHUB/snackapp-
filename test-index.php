<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test index.php admin<br><br>";

try {
    ob_start();
    include __DIR__ . '/snackup/admin/index.php';
    $output = ob_get_clean();
    
    echo "✅ index.php chargé<br><hr>";
    echo $output;
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    echo "Ligne " . $e->getLine() . " dans " . $e->getFile() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
