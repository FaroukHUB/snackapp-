<?php
/**
 * Script temporaire pour forcer la régénération de menu.json
 * avec les deletedCategories prises en compte
 */

define('SNACK_ROOT', __DIR__);

require_once __DIR__ . '/admin-panel-v2/config.php';

echo "🔄 Chargement du runtime...\n";
$runtime = loadMenuRuntime();

echo "📋 Catégories supprimées: " . json_encode($runtime['deletedCategories'] ?? []) . "\n";

echo "🔄 Chargement config.php...\n";
$config = loadConfig();
if (!$config) {
    echo "❌ ERREUR: loadConfig() a échoué !\n";
    echo "Vérifie que config/menu.php existe.\n";
    exit(1);
}
echo "✅ Config chargé.\n";

echo "🔄 Application du runtime...\n";
$merged = applyRuntimeToConfig($config, $runtime);
echo "✅ Runtime appliqué. Nombre de catégories après filtre: " . count($merged['menu']['categories'] ?? []) . "\n";

echo "🔄 Régénération de menu.json...\n";
$result = generatePublicMenuJson($runtime);

if ($result) {
    echo "✅ menu.json régénéré avec succès !\n";
    echo "✅ Les catégories supprimées ont été retirées.\n";
} else {
    echo "❌ Erreur lors de la régénération.\n";
    echo "Vérifie les logs PHP: tail -f ~/logs/error.log\n";
}
