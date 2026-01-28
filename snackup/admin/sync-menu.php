<?php
/**
 * Synchronisation Runtime → MySQL
 * Met à jour la base de données avec les modifications du runtime
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../backend/Database.php';
require_once __DIR__ . '/../backend/InstanceManager.php';

/**
 * Synchronise les statuts et modifications du runtime vers MySQL
 */
function syncMenuStatuses(): void {
    $runtime = loadMenuRuntime();

    if (empty($runtime)) {
        return;
    }

    try {
        $pdo = Database::getInstance();
        $restaurantId = InstanceManager::getRestaurantId();

        // Sync supplements
        if (!empty($runtime['supplements']['catalog'])) {
            $stmtUpdate = $pdo->prepare("
                UPDATE supplements
                SET name = ?, price = ?, status = ?, flavor = ?, group_name = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            foreach ($runtime['supplements']['catalog'] as $id => $sup) {
                $stmtUpdate->execute([
                    $sup['name'] ?? '',
                    (float)($sup['price'] ?? 0),
                    $sup['status'] ?? 'available',
                    $sup['flavor'] ?? 'sale',
                    $sup['category'] ?? $sup['group_name'] ?? 'autres',
                    $id,
                    $restaurantId
                ]);
            }
        }

        // Sync deleted supplements
        if (!empty($runtime['deletedSupplements'])) {
            $placeholders = implode(',', array_fill(0, count($runtime['deletedSupplements']), '?'));
            $stmtDelete = $pdo->prepare("
                UPDATE supplements SET status = 'unavailable'
                WHERE id IN ($placeholders) AND restaurant_id = ?
            ");
            $params = array_merge($runtime['deletedSupplements'], [$restaurantId]);
            $stmtDelete->execute($params);
        }

        // Sync products status/price
        if (!empty($runtime['products'])) {
            $stmtUpdateProduct = $pdo->prepare("
                UPDATE products
                SET status = COALESCE(?, status),
                    price_solo = COALESCE(?, price_solo),
                    price_menu = COALESCE(?, price_menu)
                WHERE id = ? AND restaurant_id = ?
            ");

            foreach ($runtime['products'] as $id => $product) {
                $stmtUpdateProduct->execute([
                    $product['status'] ?? null,
                    isset($product['priceSolo']) ? (float)$product['priceSolo'] : null,
                    isset($product['priceMenu']) ? (float)$product['priceMenu'] : null,
                    $id,
                    $restaurantId
                ]);
            }
        }

        // Sync deleted products
        if (!empty($runtime['deletedProducts'])) {
            $placeholders = implode(',', array_fill(0, count($runtime['deletedProducts']), '?'));
            $stmtDeleteProduct = $pdo->prepare("
                UPDATE products SET status = 'unavailable'
                WHERE id IN ($placeholders) AND restaurant_id = ?
            ");
            $params = array_merge($runtime['deletedProducts'], [$restaurantId]);
            $stmtDeleteProduct->execute($params);
        }

        // Sync categories status
        if (!empty($runtime['categories'])) {
            $stmtUpdateCat = $pdo->prepare("
                UPDATE categories SET is_active = ? WHERE id = ? AND restaurant_id = ?
            ");

            foreach ($runtime['categories'] as $id => $cat) {
                $isActive = ($cat['status'] ?? 'available') === 'available' ? 1 : 0;
                $stmtUpdateCat->execute([$isActive, $id, $restaurantId]);
            }
        }

        // Sync deleted categories
        if (!empty($runtime['deletedCategories'])) {
            $placeholders = implode(',', array_fill(0, count($runtime['deletedCategories']), '?'));
            $stmtDeleteCat = $pdo->prepare("
                UPDATE categories SET is_active = 0
                WHERE id IN ($placeholders) AND restaurant_id = ?
            ");
            $params = array_merge($runtime['deletedCategories'], [$restaurantId]);
            $stmtDeleteCat->execute($params);
        }

    } catch (Exception $e) {
        error_log('[syncMenuStatuses] Erreur: ' . $e->getMessage());
    }
}
