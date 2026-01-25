<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test simulation login.php<br><br>";

echo "1. Inclure login.php directement...<br>";
try {
    // Capturer l'output de login.php
    ob_start();
    include __DIR__ . '/snackup/admin/login.php';
    $output = ob_get_clean();
    
    echo "✅ login.php chargé sans erreur<br>";
    echo "<hr>";
    echo $output;
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
