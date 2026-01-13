<?php
/**
 * Script pour forcer la synchronisation du menu.runtime.json vers menu.json
 * À exécuter une seule fois pour résoudre le problème de sync
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Force Sync menu.runtime.json → menu.json</h1>";

require_once __DIR__ . '/bootstrap.php';

echo "<p>📂 Chargement du runtime...</p>";
$runtime = loadMenuRuntime();

echo "<pre>";
echo "Runtime customProducts: " . count($runtime['customProducts'] ?? []) . " produits\n";
echo "Runtime customFormules: " . count($runtime['customFormules'] ?? []) . " formules\n";
echo "</pre>";

echo "<p>🔍 Test de loadConfig()...</p>";
$config = loadConfig();
if (!$config) {
    echo "<p style='color:orange;'>⚠️ loadConfig() a retourné NULL ou FALSE</p>";
    echo "<p>Pas grave! generatePublicMenuJson() utilise maintenant menu.json directement.</p>";
} else {
    echo "<p style='color:green;'>✅ Config chargé avec succès</p>";
    echo "<pre>";
    echo "Categories dans config: " . count($config['menu']['categories'] ?? []) . "\n";
    echo "</pre>";
}

echo "<p>🔄 Génération du menu public...</p>";
$result = generatePublicMenuJson($runtime);

if ($result) {
    echo "<p style='color:green;font-weight:bold;'>✅ SUCCESS! menu.json a été mis à jour</p>";

    // Vérifier que les produits sont bien dans menu.json
    $menuJsonPath = __DIR__ . '/../config/menu.json';
    $menuData = json_decode(file_get_contents($menuJsonPath), true);

    $totalProducts = 0;
    foreach ($menuData['menu']['categories'] as $cat) {
        $totalProducts += count($cat['items'] ?? []);
    }

    echo "<p>📊 Menu.json contient maintenant: {$totalProducts} produits au total</p>";
    echo "<p>📦 Dont " . count($runtime['customProducts'] ?? []) . " produits customs du runtime</p>";

    echo "<h3>🎯 Produits customs ajoutés:</h3>";
    echo "<ul>";
    foreach ($runtime['customProducts'] as $prod) {
        echo "<li><strong>{$prod['name']}</strong> (ID: {$prod['id']}) - Catégorie: {$prod['categoryId']}</li>";
    }
    echo "</ul>";

} else {
    echo "<p style='color:red;font-weight:bold;'>❌ ERREUR: La synchronisation a échoué</p>";
    echo "<p>generatePublicMenuJson() a retourné false</p>";
}

echo "<p><a href='products-manager.php'>← Retour au gestionnaire de produits</a></p>";
