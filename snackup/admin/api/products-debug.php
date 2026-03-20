<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug products.php</h1>";

try {
    echo "<p>1. Loading bootstrap...</p>";
    require_once __DIR__ . '/../bootstrap.php';
    echo "<p>✓ Bootstrap loaded</p>";
    
    echo "<p>2. Simulating GET request...</p>";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'list';
    
    echo "<p>3. Calling MenuRepository::getAllCategories()...</p>";
    $categories = MenuRepository::getAllCategories();
    echo "<p>✓ Found " . count($categories) . " categories</p>";
    
    echo "<pre>";
    print_r($categories);
    echo "</pre>";
    
} catch (Throwable $e) {
    echo "<pre style='color: red; background: white; padding: 20px; border: 2px solid red;'>";
    echo "ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
