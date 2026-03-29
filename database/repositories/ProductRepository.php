<?php
/**
 * SnackApp v2 - Product Repository
 * Gestion CRUD des produits avec soft delete et options_config JSON
 */

require_once __DIR__ . '/../Database.php';

class ProductRepository {

    /**
     * Récupère tous les produits d'un restaurant
     */
    public static function getAll(int $restaurantId, bool $includeDeleted = false): array {
        $products = Database::fetchAll(
            "SELECT * FROM products
             WHERE restaurant_id = ?
             ORDER BY category_id ASC, sort_order ASC, id ASC",
            [$restaurantId]
        );

        // Décoder options_config JSON
        return array_map([self::class, 'decodeOptionsConfig'], $products);
    }

    /**
     * Récupère tous les produits d'une catégorie
     */
    public static function getByCategory(int $categoryId, bool $includeDeleted = false): array {
        $products = Database::fetchAll(
            "SELECT * FROM products
             WHERE category_id = ?
             ORDER BY sort_order ASC, id ASC",
            [$categoryId]
        );

        return array_map([self::class, 'decodeOptionsConfig'], $products);
    }

    /**
     * Récupère un produit par ID
     */
    public static function getById(int $id, bool $includeDeleted = false): ?array {
        $product = Database::fetchOne(
            "SELECT * FROM products WHERE id = ?",
            [$id]
        );

        return $product ? self::decodeOptionsConfig($product) : null;
    }

    /**
     * Récupère un produit par slug
     */
    public static function getBySlug(int $restaurantId, string $slug, bool $includeDeleted = false): ?array {
        $product = Database::fetchOne(
            "SELECT * FROM products
             WHERE restaurant_id = ? AND slug = ?",
            [$restaurantId, $slug]
        );

        return $product ? self::decodeOptionsConfig($product) : null;
    }

    /**
     * Crée un nouveau produit
     */
    public static function create(int $restaurantId, int $categoryId, array $data): int {
        // Vérifier unicité du slug
        $existing = self::getBySlug($restaurantId, $data['slug']);
        if ($existing) {
            throw new Exception("Un produit avec ce slug existe déjà");
        }

        // Encoder options_config si présent
        $optionsConfig = null;
        if (isset($data['options_config']) && is_array($data['options_config'])) {
            $optionsConfig = json_encode($data['options_config'], JSON_UNESCAPED_UNICODE);
        } elseif (isset($data['options_config']) && is_string($data['options_config'])) {
            $optionsConfig = $data['options_config'];
        }

        $insertData = [
            'restaurant_id' => $restaurantId,
            'category_id' => $categoryId,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'price_solo' => $data['price_solo'],
            'price_menu' => $data['price_menu'] ?? null,
            'status' => $data['status'] ?? 'available',
            'options_config' => $optionsConfig,
            'sort_order' => $data['sort_order'] ?? 0
        ];

        return Database::insert('products', $insertData);
    }

    /**
     * Met à jour un produit
     */
    public static function update(int $id, array $data): bool {
        // Filtrer les champs modifiables
        $updateData = [];
        $allowedFields = ['category_id', 'name', 'slug', 'description', 'image', 'price_solo', 'price_menu', 'status', 'options_config', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'options_config') {
                    // Encoder options_config si array
                    if (is_array($data[$field])) {
                        $updateData[$field] = json_encode($data[$field], JSON_UNESCAPED_UNICODE);
                    } elseif (is_string($data[$field]) || is_null($data[$field])) {
                        $updateData[$field] = $data[$field];
                    }
                } else {
                    $updateData[$field] = $data[$field];
                }
            }
        }

        if (empty($updateData)) {
            return false;
        }

        // Si slug modifié, vérifier unicité
        if (isset($updateData['slug'])) {
            $product = self::getById($id);
            if (!$product) {
                throw new Exception("Produit non trouvé");
            }

            if ($updateData['slug'] !== $product['slug']) {
                $existing = self::getBySlug($product['restaurant_id'], $updateData['slug']);
                if ($existing && $existing['id'] !== $id) {
                    throw new Exception("Un produit avec ce slug existe déjà");
                }
            }
        }

        $rowsAffected = Database::update('products', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Désactive un produit
     */
    public static function softDelete(int $id): bool {
        $rowsAffected = Database::update(
            'products',
            ['status' => 'unavailable'],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Restaure un produit (réactive)
     */
    public static function restore(int $id): bool {
        $rowsAffected = Database::update(
            'products',
            ['status' => 'available'],
            ['id' => $id]
        );
        return $rowsAffected > 0;
    }

    /**
     * Suppression définitive (hard delete)
     */
    public static function hardDelete(int $id): bool {
        $rowsAffected = Database::delete('products', ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Change le statut d'un produit (available/unavailable)
     */
    public static function setStatus(int $id, string $status): bool {
        if (!in_array($status, ['available', 'unavailable'])) {
            throw new Exception("Statut invalide");
        }

        $rowsAffected = Database::update(
            'products',
            ['status' => $status],
            ['id' => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Réorganise l'ordre des produits dans une catégorie
     */
    public static function reorder(int $categoryId, array $productIds): bool {
        return Database::transaction(function() use ($categoryId, $productIds) {
            foreach ($productIds as $index => $productId) {
                Database::update(
                    'products',
                    ['sort_order' => $index],
                    ['id' => $productId, 'category_id' => $categoryId]
                );
            }
            return true;
        });
    }

    /**
     * Recherche de produits par nom
     */
    public static function search(int $restaurantId, string $query, bool $includeDeleted = false): array {
        $searchTerm = '%' . $query . '%';

        $products = Database::fetchAll(
            "SELECT * FROM products
             WHERE restaurant_id = ? AND name LIKE ?
             ORDER BY name ASC",
            [$restaurantId, $searchTerm]
        );

        return array_map([self::class, 'decodeOptionsConfig'], $products);
    }

    /**
     * Récupère les produits disponibles (status = available)
     */
    public static function getAvailable(int $restaurantId): array {
        $products = Database::fetchAll(
            "SELECT * FROM products
             WHERE restaurant_id = ? AND status = 'available'
             ORDER BY category_id ASC, sort_order ASC",
            [$restaurantId]
        );

        return array_map([self::class, 'decodeOptionsConfig'], $products);
    }

    /**
     * Statistiques des produits
     */
    public static function getStats(int $restaurantId): array {
        return Database::fetchOne(
            "SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_products,
                SUM(CASE WHEN status = 'unavailable' THEN 1 ELSE 0 END) as unavailable_products,
                0 as deleted_products,
                SUM(CASE WHEN options_config IS NOT NULL THEN 1 ELSE 0 END) as products_with_options
             FROM products
             WHERE restaurant_id = ?",
            [$restaurantId]
        ) ?? [
            'total_products' => 0,
            'available_products' => 0,
            'unavailable_products' => 0,
            'deleted_products' => 0,
            'products_with_options' => 0
        ];
    }

    /**
     * Décode options_config JSON en array
     */
    private static function decodeOptionsConfig(array $product): array {
        if (!empty($product['options_config'])) {
            $decoded = json_decode($product['options_config'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $product['options_config'] = $decoded;
            }
        }
        return $product;
    }

    /**
     * Met à jour les options_config d'un produit
     */
    public static function updateOptions(int $id, array $options): bool {
        $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);

        $rowsAffected = Database::update(
            'products',
            ['options_config' => $optionsJson],
            ['id' => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Récupère les suppléments d'un produit
     */
    public static function getSupplements(int $productId): array {
        return Database::fetchAll(
            "SELECT s.* FROM supplements s
             JOIN product_supplements ps ON s.id = ps.supplement_id
             WHERE ps.product_id = ?
             ORDER BY s.sort_order ASC",
            [$productId]
        );
    }

    /**
     * Associe des suppléments à un produit
     */
    public static function attachSupplements(int $productId, array $supplementIds): bool {
        return Database::transaction(function() use ($productId, $supplementIds) {
            // Supprimer anciennes associations
            Database::delete('product_supplements', ['product_id' => $productId]);

            // Créer nouvelles associations
            foreach ($supplementIds as $supplementId) {
                Database::insert('product_supplements', [
                    'product_id' => $productId,
                    'supplement_id' => $supplementId
                ]);
            }

            return true;
        });
    }
}
