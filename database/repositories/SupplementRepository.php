<?php
/**
 * SnackApp v2 - Supplement Repository
 * Gestion CRUD des suppléments avec soft delete et liaisons produits
 */

require_once __DIR__ . '/../Database.php';

class SupplementRepository {

    /**
     * Récupère tous les suppléments d'un restaurant
     */
    public static function getAll(int $restaurantId, bool $includeDeleted = false): array {
        return Database::fetchAll(
            "SELECT * FROM supplements
             WHERE restaurant_id = ?
             ORDER BY sort_order ASC, id ASC",
            [$restaurantId]
        );
    }

    /**
     * Récupère un supplément par ID
     */
    public static function getById(int $id, bool $includeDeleted = false): ?array {
        return Database::fetchOne(
            "SELECT * FROM supplements WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupère les suppléments par statut
     */
    public static function getByStatus(int $restaurantId, string $status, bool $includeDeleted = false): array {
        if (!in_array($status, ['available', 'unavailable'])) {
            throw new Exception("Statut invalide");
        }

        return Database::fetchAll(
            "SELECT * FROM supplements
             WHERE restaurant_id = ? AND status = ?
             ORDER BY sort_order ASC",
            [$restaurantId, $status]
        );
    }

    /**
     * Crée un nouveau supplément
     */
    public static function create(int $restaurantId, array $data): int {
        $insertData = [
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => $data['status'] ?? 'available',
            'sort_order' => $data['sort_order'] ?? 0
        ];

        return Database::insert('supplements', $insertData);
    }

    /**
     * Met à jour un supplément
     */
    public static function update(int $id, array $data): bool {
        // Filtrer les champs modifiables
        $updateData = [];
        $allowedFields = ['name', 'price', 'status', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = Database::update('supplements', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Désactive un supplément
     */
    public static function softDelete(int $id): bool {
        $rowsAffected = Database::update(
            'supplements',
            ['status' => 'unavailable'],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Restaure un supplément (réactive)
     */
    public static function restore(int $id): bool {
        $rowsAffected = Database::update(
            'supplements',
            ['status' => 'available'],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Suppression définitive (hard delete)
     */
    public static function hardDelete(int $id): bool {
        $rowsAffected = Database::delete('supplements', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Change le statut d'un supplément (available/unavailable)
     */
    public static function setStatus(int $id, string $status): bool {
        if (!in_array($status, ['available', 'unavailable'])) {
            throw new Exception("Statut invalide");
        }

        $rowsAffected = Database::update(
            'supplements',
            ['status' => $status],
            ['id' => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Réorganise l'ordre des suppléments
     */
    public static function reorder(int $restaurantId, array $supplementIds): bool {
        return Database::transaction(function() use ($restaurantId, $supplementIds) {
            foreach ($supplementIds as $index => $supplementId) {
                Database::update(
                    'supplements',
                    ['sort_order' => $index],
                    ['id' => $supplementId, 'restaurant_id' => $restaurantId]
                );
            }
            return true;
        });
    }

    /**
     * Statistiques des suppléments
     */
    public static function getStats(int $restaurantId): array {
        return Database::fetchOne(
            "SELECT
                COUNT(*) as total_supplements,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_supplements,
                SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_supplements,
                0 as deleted_supplements,
                AVG(price) as avg_price
             FROM supplements
             WHERE restaurant_id = ?",
            [$restaurantId]
        ) ?? [
            'total_supplements' => 0,
            'available_supplements' => 0,
            'unavailable_supplements' => 0,
            'deleted_supplements' => 0,
            'avg_price' => 0
        ];
    }

    // =============================================
    // GESTION LIAISONS PRODUITS-SUPPLÉMENTS
    // =============================================

    /**
     * Récupère tous les produits associés à un supplément
     */
    public static function getProducts(int $supplementId, bool $includeDeleted = false): array {
        return Database::fetchAll(
            "SELECT p.* FROM products p
             JOIN product_supplements ps ON p.id = ps.product_id
             WHERE ps.supplement_id = ?
             ORDER BY p.category_id ASC, p.sort_order ASC",
            [$supplementId]
        );
    }

    /**
     * Compte le nombre de produits utilisant ce supplément
     */
    public static function countProducts(int $supplementId, bool $includeDeleted = false): int {
        $result = Database::fetchOne(
            "SELECT COUNT(DISTINCT ps.product_id) as count
             FROM product_supplements ps
             WHERE ps.supplement_id = ?",
            [$supplementId]
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Associe un supplément à un produit
     */
    public static function attachToProduct(int $supplementId, int $productId): bool {
        // Vérifier si l'association existe déjà
        $existing = Database::fetchOne(
            "SELECT * FROM product_supplements WHERE product_id = ? AND supplement_id = ?",
            [$productId, $supplementId]
        );

        if ($existing) {
            return true; // Déjà associé
        }

        // Créer nouvelle association
        try {
            Database::insert('product_supplements', [
                'product_id' => $productId,
                'supplement_id' => $supplementId
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Dissocie un supplément d'un produit
     */
    public static function detachFromProduct(int $supplementId, int $productId): bool {
        $rowsAffected = Database::delete(
            'product_supplements',
            ['product_id' => $productId, 'supplement_id' => $supplementId]
        );

        return $rowsAffected > 0;
    }

    /**
     * Supprime définitivement une liaison produit-supplément
     */
    public static function hardDetachFromProduct(int $supplementId, int $productId): bool {
        $rowsAffected = Database::delete(
            'product_supplements',
            ['product_id' => $productId, 'supplement_id' => $supplementId]
        );

        return $rowsAffected > 0;
    }

    /**
     * Associe un supplément à plusieurs produits
     */
    public static function attachToProducts(int $supplementId, array $productIds): bool {
        return Database::transaction(function() use ($supplementId, $productIds) {
            foreach ($productIds as $productId) {
                self::attachToProduct($supplementId, $productId);
            }
            return true;
        });
    }

    /**
     * Dissocie un supplément de plusieurs produits
     */
    public static function detachFromProducts(int $supplementId, array $productIds): bool {
        return Database::transaction(function() use ($supplementId, $productIds) {
            foreach ($productIds as $productId) {
                self::detachFromProduct($supplementId, $productId);
            }
            return true;
        });
    }

    /**
     * Synchronise les produits associés à un supplément
     * Supprime les anciennes associations et crée les nouvelles
     */
    public static function syncProducts(int $supplementId, array $productIds): bool {
        return Database::transaction(function() use ($supplementId, $productIds) {
            // Supprimer toutes les anciennes associations
            Database::delete('product_supplements', ['supplement_id' => $supplementId]);

            // Créer nouvelles associations
            foreach ($productIds as $productId) {
                Database::insert('product_supplements', [
                    'product_id' => $productId,
                    'supplement_id' => $supplementId
                ]);
            }

            return true;
        });
    }

    /**
     * Récupère toutes les liaisons produits-suppléments actives
     */
    public static function getAllProductSupplements(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT ps.*, p.name as product_name, s.name as supplement_name
             FROM product_supplements ps
             JOIN products p ON ps.product_id = p.id
             JOIN supplements s ON ps.supplement_id = s.id
             WHERE p.restaurant_id = ?
             ORDER BY p.name ASC, s.name ASC",
            [$restaurantId]
        );
    }
}
