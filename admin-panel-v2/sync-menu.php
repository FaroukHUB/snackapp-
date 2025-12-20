<?php
/**
 * Synchronise les statuts des produits du runtime vers menu.json
 * À appeler après chaque changement de disponibilité
 */

require_once __DIR__ . '/config.php';

function syncMenuStatuses() {
    $menuJsonPath = __DIR__ . '/../config/menu.json';
    $runtimePath = MENU_RUNTIME_FILE;

    // Charger menu.json existant
    if (!file_exists($menuJsonPath)) {
        return ['success' => false, 'error' => 'menu.json non trouvé'];
    }

    $menuData = json_decode(file_get_contents($menuJsonPath), true);
    if (!$menuData) {
        return ['success' => false, 'error' => 'Erreur parsing menu.json'];
    }

    // Charger le runtime
    $runtime = loadMenuRuntime();
    $productStatuses = $runtime['products'] ?? [];

    // Mettre à jour les statuts dans menu.json
    $updated = 0;
    if (isset($menuData['menu']['categories'])) {
        foreach ($menuData['menu']['categories'] as &$category) {
            if (isset($category['items'])) {
                foreach ($category['items'] as &$item) {
                    $productId = $item['id'] ?? null;
                    if ($productId && isset($productStatuses[$productId]['status'])) {
                        $item['status'] = $productStatuses[$productId]['status'];
                        $updated++;
                    }
                }
            }
        }
    }

    // Sauvegarder
    $json = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    file_put_contents($menuJsonPath, $json);

    return ['success' => true, 'updated' => $updated];
}

// Si appelé directement
if (php_sapi_name() === 'cli' || isset($_GET['sync'])) {
    header('Content-Type: application/json');
    echo json_encode(syncMenuStatuses());
}
