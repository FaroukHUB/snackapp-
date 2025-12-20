<?php
/**
 * Synchronise les statuts des produits et suppléments du runtime vers menu.json
 * À appeler après chaque changement de disponibilité
 */

require_once __DIR__ . '/config.php';

function syncMenuStatuses() {
    $menuJsonPath = __DIR__ . '/../config/menu.json';

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
    $supplementsRuntime = $runtime['supplements']['catalog'] ?? [];

    $updated = 0;

    // Mettre à jour les produits (statut, image, prix, etc.)
    if (isset($menuData['menu']['categories'])) {
        foreach ($menuData['menu']['categories'] as &$category) {
            if (isset($category['items'])) {
                foreach ($category['items'] as &$item) {
                    $productId = $item['id'] ?? null;
                    if ($productId && isset($productStatuses[$productId])) {
                        $productData = $productStatuses[$productId];
                        if (isset($productData['status'])) {
                            $item['status'] = $productData['status'];
                            $updated++;
                        }
                        if (isset($productData['image'])) {
                            $item['image'] = $productData['image'];
                        }
                        if (isset($productData['name'])) {
                            $item['name'] = $productData['name'];
                        }
                        if (isset($productData['description'])) {
                            $item['description'] = $productData['description'];
                        }
                        if (isset($productData['priceSolo'])) {
                            $item['priceSolo'] = $productData['priceSolo'];
                        }
                        if (isset($productData['priceMenu'])) {
                            $item['priceMenu'] = $productData['priceMenu'];
                        }
                    }
                }
            }
        }
    }

    // Mettre à jour les statuts des suppléments
    if (isset($menuData['supplements']['catalog'])) {
        foreach ($menuData['supplements']['catalog'] as $supId => &$supplement) {
            if (isset($supplementsRuntime[$supId]['status'])) {
                $supplement['status'] = $supplementsRuntime[$supId]['status'];
                $updated++;
            }
            if (isset($supplementsRuntime[$supId]['name'])) {
                $supplement['name'] = $supplementsRuntime[$supId]['name'];
            }
            if (isset($supplementsRuntime[$supId]['price'])) {
                $supplement['price'] = $supplementsRuntime[$supId]['price'];
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
