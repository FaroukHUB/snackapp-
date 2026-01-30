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
            'sort_order' => $data['sort_order'] ?? 0,
            'flavor' => $data['flavor'] ?? 'sale',
            'group_name' => $data['group_name'] ?? 'autres',
            'image' => $data['image'] ?? null
        ];

        return Database::insert('supplements', $insertData);
    }

    /**
     * Met à jour un supplément
     */
    public static function update(int $id, array $data): bool {
        // Filtrer les champs modifiables
        $updateData = [];
        $allowedFields = ['name', 'price', 'status', 'sort_order', 'flavor', 'group_name', 'image'];

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
     * Désactive un supplément (soft delete)
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

    /**
     * Supprime tous les suppléments sucrés (legacy cleanup)
     */
    public static function deleteAllSweet(int $restaurantId): int {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM supplements WHERE restaurant_id = ? AND flavor = 'sucre'");
        $stmt->execute([$restaurantId]);
        return $stmt->rowCount();
    }

    /**
     * Récupère les suppléments par groupe
     */
    public static function getByGroup(int $restaurantId, string $groupName): array {
        return Database::fetchAll(
            "SELECT * FROM supplements
             WHERE restaurant_id = ? AND group_name = ? AND status = 'available'
             ORDER BY sort_order ASC",
            [$restaurantId, $groupName]
        );
    }

    /**
     * Récupère tous les suppléments groupés
     */
    public static function getAllGrouped(int $restaurantId): array {
        $supplements = Database::fetchAll(
            "SELECT * FROM supplements
             WHERE restaurant_id = ? AND status = 'available'
             ORDER BY group_name ASC, sort_order ASC",
            [$restaurantId]
        );

        $grouped = [];
        foreach ($supplements as $supp) {
            $group = $supp['group_name'] ?? 'autres';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $supp;
        }

        return $grouped;
    }
}
