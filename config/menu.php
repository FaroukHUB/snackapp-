<?php
/**
 * API publique : Retourne le menu FILTRÉ (avec deletedCategories appliqués)
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Charger les fonctions runtime
require_once __DIR__ . '/../admin-panel-v2/config.php';

// Lire menu.json de base
$menuJsonPath = __DIR__ . '/menu.json';
if (!file_exists($menuJsonPath)) {
    echo json_encode(['error' => 'Menu introuvable']);
    exit;
}

$menuData = json_decode(file_get_contents($menuJsonPath), true);

if (!$menuData) {
    echo json_encode(['error' => 'Erreur lecture menu']);
    exit;
}

// ✅ Charger runtime et appliquer les filtres (deletedCategories, etc.)
$runtime = loadMenuRuntime();
$menuDataFiltered = applyRuntimeToConfig($menuData, $runtime);

// Retourner le menu filtré (catégories supprimées invisibles)
echo json_encode($menuDataFiltered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

