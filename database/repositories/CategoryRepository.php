<?php
/**
 * SnackApp v2 - Category Repository
 * Gestion CRUD des catégories avec soft delete
 */

require_once __DIR__ . '/../Database.php';

class CategoryRepository {

    /**
     * Récupère toutes les catégories actives d'un restaurant
     */
    public static function getAll(int $restaurantId, bool $includeDeleted = false): array {
        $deletedFilter = $includeDeleted ? '' : 'AND deleted_at IS NULL';

        return Database::fetchAll(
            "SELECT * FROM categories
             WHERE restaurant_id = ? {$deletedFilter}
             ORDER BY sort_order ASC, id ASC",
            [$restaurantId]
        );
    }

    /**
     * Récupère une catégorie par ID
     */
    public static function getById(int $id, bool $includeDeleted = false): ?array {
        $deletedFilter = $includeDeleted ? '' : 'AND deleted_at IS NULL';

        return Database::fetchOne(
            "SELECT * FROM categories WHERE id = ? {$deletedFilter}",
            [$id]
        );
    }

    /**
     * Récupère une catégorie par slug
     */
    public static function getBySlug(int $restaurantId, string $slug, bool $includeDeleted = false): ?array {
        $deletedFilter = $includeDeleted ? '' : 'AND deleted_at IS NULL';

        return Database::fetchOne(
            "SELECT * FROM categories
             WHERE restaurant_id = ? AND slug = ? {$deletedFilter}",
            [$restaurantId, $slug]
        );
    }

    /**
     * Crée une nouvelle catégorie
     */
    public static function create(int $restaurantId, array $data): int {
        // Vérifier unicité du slug
        $existing = self::getBySlug($restaurantId, $data['slug']);
        if ($existing) {
            throw new Exception("Une catégorie avec ce slug existe déjà");
        }

        $insertData = [
            'restaurant_id' => $restaurantId,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ];

        return Database::insert('categories', $insertData);
    }

    /**
     * Met à jour une catégorie
     */
    public static function update(int $id, array $data): bool {
        // Filtrer les champs modifiables
        $updateData = [];
        $allowedFields = ['name', 'slug', 'description', 'image', 'sort_order', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        // Si slug modifié, vérifier unicité
        if (isset($updateData['slug'])) {
            $category = self::getById($id);
            if (!$category) {
                throw new Exception("Catégorie non trouvée");
            }

            if ($updateData['slug'] !== $category['slug']) {
                $existing = self::getBySlug($category['restaurant_id'], $updateData['slug']);
                if ($existing && $existing['id'] !== $id) {
                    throw new Exception("Une catégorie avec ce slug existe déjà");
                }
            }
        }

        $rowsAffected = Database::update('categories', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Soft delete d'une catégorie
     */
    public static function softDelete(int $id): bool {
        $rowsAffected = Database::update(
            'categories',
            ['deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Restaure une catégorie supprimée
     */
    public static function restore(int $id): bool {
        $rowsAffected = Database::update(
            'categories',
            ['deleted_at' => null],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Suppression définitive (hard delete)
     */
    public static function hardDelete(int $id): bool {
        $rowsAffected = Database::delete('categories', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Compte le nombre de produits dans une catégorie
     */
    public static function countProducts(int $categoryId, bool $includeDeleted = false): int {
        $deletedFilter = $includeDeleted ? '' : 'AND deleted_at IS NULL';

        $result = Database::fetchOne(
            "SELECT COUNT(*) as count FROM products
             WHERE category_id = ? {$deletedFilter}",
            [$categoryId]
        );

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Réorganise l'ordre des catégories
     */
    public static function reorder(int $restaurantId, array $categoryIds): bool {
        return Database::transaction(function() use ($restaurantId, $categoryIds) {
            foreach ($categoryIds as $index => $categoryId) {
                Database::update(
                    'categories',
                    ['sort_order' => $index],
                    ['id' => $categoryId, 'restaurant_id' => $restaurantId]
                );
            }
            return true;
        });
    }

    /**
     * Active/désactive une catégorie
     */
    public static function toggleActive(int $id): bool {
        $category = self::getById($id);
        if (!$category) {
            return false;
        }

        $newStatus = $category['is_active'] ? 0 : 1;
        $rowsAffected = Database::update(
            'categories',
            ['is_active' => $newStatus],
            ['id' => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Statistiques des catégories
     */
    public static function getStats(int $restaurantId): array {
        return Database::fetchOne(
            "SELECT
                COUNT(*) as total_categories,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_categories,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted_categories
             FROM categories
             WHERE restaurant_id = ?",
            [$restaurantId]
        ) ?? [
            'total_categories' => 0,
            'active_categories' => 0,
            'deleted_categories' => 0
        ];
    }
}
