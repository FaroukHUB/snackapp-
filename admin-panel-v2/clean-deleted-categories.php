<?php
require_once __DIR__ . '/../bootstrap.php';

$runtimePath = SNACK_ROOT . '/config/menu.runtime.json';
$runtime = json_decode(file_get_contents($runtimePath), true);

echo "Avant: " . count($runtime['deletedCategories']) . " catégories dans deletedCategories\n";
foreach ($runtime['deletedCategories'] as $id) {
    echo "  - $id\n";
}

// Vider completement
$runtime['deletedCategories'] = [];

// Sauvegarder
file_put_contents($runtimePath, json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✅ deletedCategories vidé!\n\n";

// Regénérer menu.json
require_once __DIR__ . '/config.php';
generatePublicMenuJson($runtime);
echo "✅ menu.json régénéré!\n";
