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
    $deletedProducts = $runtime['deletedProducts'] ?? [];

    // 🔒 SÉCURITÉ: Logs minimaux (pas de données sensibles)
    $updated = 0;

    // Mettre à jour les produits (statut, image, prix, etc.)
    if (isset($menuData['menu']['categories'])) {
        foreach ($menuData['menu']['categories'] as &$category) {
            if (isset($category['items'])) {
                // D'abord filtrer les produits supprimés
                $itemsBefore = count($category['items']);
                $category['items'] = array_filter($category['items'], function($item) use ($deletedProducts) {
                    $productId = $item['id'] ?? null;
                    $isDeleted = $productId && in_array($productId, $deletedProducts, true);
                    return !$isDeleted;
                });
                $itemsAfter = count($category['items']);

                // Ensuite mettre à jour les produits restants
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
                        if (array_key_exists('badge', $productData)) {
                            $item['badge'] = $productData['badge'];
                        }
                        // Synchroniser les variants (Court/Long pour cafés)
                        if (isset($productData['variants'])) {
                            $item['variants'] = $productData['variants'];
                        }
                        // Synchroniser les numéros de capsules (legacy)
                        if (isset($productData['capsuleNumbers'])) {
                            $item['capsuleNumbers'] = $productData['capsuleNumbers'];
                        }
                        // Synchroniser les couleurs de capsules (nouvelle logique)
                        if (isset($productData['capsuleColors'])) {
                            $item['capsuleColors'] = $productData['capsuleColors'];
                        }
                        // Synchroniser les baseIngredients
                        if (isset($productData['baseIngredients'])) {
                            $item['baseIngredients'] = $productData['baseIngredients'];
                        }
                    }
                }

                // Réindexer le tableau
                $category['items'] = array_values($category['items']);
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
            if (isset($supplementsRuntime[$supId]['category'])) {
                $supplement['category'] = $supplementsRuntime[$supId]['category'];
            }
            if (isset($supplementsRuntime[$supId]['flavor'])) {
                $supplement['flavor'] = $supplementsRuntime[$supId]['flavor'];
            }
        }
    }

    // Ajouter les nouveaux suppléments depuis le runtime
    if (!empty($supplementsRuntime)) {
        if (!isset($menuData['supplements']['catalog'])) {
            $menuData['supplements']['catalog'] = [];
        }
        foreach ($supplementsRuntime as $supId => $supData) {
            if (!isset($menuData['supplements']['catalog'][$supId])) {
                // Nouveau supplément créé dans l'admin
                $menuData['supplements']['catalog'][$supId] = $supData;
                $updated++;
                error_log("[SYNC] Ajout nouveau supplément: {$supId}");
            }
        }
    }

    // Synchroniser defaultForCategories depuis le runtime (MERGER, pas écraser)
    if (isset($runtime['supplements']['defaultForCategories'])) {
        if (!isset($menuData['supplements']['defaultForCategories'])) {
            $menuData['supplements']['defaultForCategories'] = [];
        }
        foreach ($runtime['supplements']['defaultForCategories'] as $catId => $runtimeSupIds) {
            $existingSupIds = $menuData['supplements']['defaultForCategories'][$catId] ?? [];

            // MERGER : ajouter les IDs du runtime qui ne sont pas déjà dans menu.json
            $merged = array_unique(array_merge($existingSupIds, $runtimeSupIds));

            // Vérifier si la valeur a vraiment changé
            if ($existingSupIds !== $merged) {
                $menuData['supplements']['defaultForCategories'][$catId] = array_values($merged);
                $updated++;
            }
        }
    }

    // Supprimer SEULEMENT les suppléments explicitement marqués comme supprimés
    $deletedSupplements = $runtime['deletedSupplements'] ?? [];
    if (!empty($deletedSupplements) && isset($menuData['supplements']['catalog'])) {
        foreach ($deletedSupplements as $supId) {
            if (isset($menuData['supplements']['catalog'][$supId])) {
                unset($menuData['supplements']['catalog'][$supId]);
                $updated++;

                // Également le retirer de defaultForCategories
                if (isset($menuData['supplements']['defaultForCategories'])) {
                    foreach ($menuData['supplements']['defaultForCategories'] as $catId => &$supIds) {
                        $supIds = array_values(array_filter($supIds, fn($s) => $s !== $supId));
                    }
                }
            }
        }
    }

    // Synchroniser les formules
    $formules = $menuData['formules'] ?? [];

    // Appliquer les patches du runtime
    if (!empty($runtime['formules'])) {
        foreach ($runtime['formules'] as $id => $patch) {
            foreach ($formules as &$f) {
                if ($f['id'] === $id) {
                    $f = array_merge($f, $patch);
                    $updated++;
                    break;
                }
            }
        }
    }

    // Ajouter les formules custom
    if (!empty($runtime['customFormules'])) {
        foreach ($runtime['customFormules'] as $formule) {
            $exists = false;
            foreach ($formules as $existing) {
                if ($existing['id'] === $formule['id']) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $formules[] = $formule;
                $updated++;
            }
        }
    }

    // Supprimer les formules marquées comme supprimées
    if (!empty($runtime['deletedFormules'])) {
        $formules = array_filter($formules, fn($f) => !in_array($f['id'], $runtime['deletedFormules'], true));
        $formules = array_values($formules);
    }

    $menuData['formules'] = $formules;

    // Sauvegarder
    $json = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $bytesWritten = file_put_contents($menuJsonPath, $json);

    if ($bytesWritten === false) {
        error_log('[SÉCURITÉ] Erreur écriture menu.json');
        return ['success' => false, 'error' => 'Erreur lors de l\'écriture'];
    }

    return ['success' => true, 'updated' => $updated];
}

// Si appelé directement
if (php_sapi_name() === 'cli' || isset($_GET['sync'])) {
    header('Content-Type: application/json');
    echo json_encode(syncMenuStatuses());
}
