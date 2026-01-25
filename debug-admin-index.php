<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Debug admin index.php</h3>";

try {
    ob_start();
    include __DIR__ . '/snackup/admin/index.php';
    $output = ob_get_clean();
    
    echo "<p style='color:green'>✅ index.php chargé sans erreur</p><hr>";
    echo $output;
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
