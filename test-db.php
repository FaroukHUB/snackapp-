<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>DEBUG ADMIN INDEX.PHP</h3>";

try {
    ob_start();
    include __DIR__ . '/snackup/admin/index.php';
    $output = ob_get_clean();
    
    echo "<p style='color:green'>✅ index.php chargé sans erreur</p><hr>";
    echo $output;
} catch (Throwable $e) {
    ob_end_clean();
    echo "<p style='color:red'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Fichier: " . htmlspecialchars($e->getFile()) . " ligne " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
