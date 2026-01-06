<?php
/**
 * SnackApp v1 - Menu Repository
 * Gestion des catégories, produits et suppléments
 */

require_once __DIR__ . '/../Database.php';

class MenuRepository {

    /**
     * Récupère le menu complet d'un restaurant (format compatible JSON actuel)
     */
    public static function getFullMenu(int $restaurantId, bool $includeUnavailable = false): array {
        // Catégories
        $categories = Database::fetchAll(
            "SELECT id, name, slug, description, image, sort_order
             FROM categories
             WHERE restaurant_id = ? AND is_active = 1
             ORDER BY sort_order",
            [$restaurantId]
        );

        // Produits par catégorie
        foreach ($categories as &$category) {
            $category['items'] = Database::fetchAll(
                "SELECT p.id, p.name, p.slug, p.description, p.image,
                        p.price_solo as priceSolo, p.price_menu as priceMenu, p.status
                 FROM products p
                 WHERE p.category_id = ?" . ($includeUnavailable ? "" : " AND p.status = 'available'") . "
                 ORDER BY p.sort_order",
                [$category['id']]
            );

            // Suppléments pour chaque produit
            foreach ($category['items'] as &$item) {
                $supplements = Database::fetchAll(
                    "SELECT s.id
                     FROM supplements s
                     JOIN product_supplements ps ON s.id = ps.supplement_id
                     WHERE ps.product_id = ?",
                    [$item['id']]
                );
                $item['supplements'] = array_column($supplements, 'id');
            }
        }

        // Catalogue des suppléments
        $supplements = Database::fetchAll(
            "SELECT id, name, price, status
             FROM supplements
             WHERE restaurant_id = ?
             ORDER BY sort_order",
            [$restaurantId]
        );

        $catalog = [];
        foreach ($supplements as $sup) {
            $catalog[$sup['id']] = [
                'id' => (string) $sup['id'],
                'name' => $sup['name'],
                'price' => (float) $sup['price'],
                'status' => $sup['status']
            ];
        }

        return [
            'menu' => ['categories' => $categories],
            'supplements' => [
                'catalog' => $catalog,
                'defaultForCategories' => []
            ]
        ];
    }

    /**
     * Récupère toutes les catégories
     */
    public static function getCategories(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT c.*, COUNT(p.id) as products_count
             FROM categories c
             LEFT JOIN products p ON c.id = p.category_id
             WHERE c.restaurant_id = ?
             GROUP BY c.id
             ORDER BY c.sort_order",
            [$restaurantId]
        );
    }

    /**
     * Ajoute une catégorie
     */
    public static function addCategory(int $restaurantId, string $name, ?string $description = null): int {
        $slug = self::slugify($name);
        $maxOrder = Database::fetchOne(
            "SELECT MAX(sort_order) as max_order FROM categories WHERE restaurant_id = ?",
            [$restaurantId]
        );

        return Database::insert('categories', [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'sort_order' => ($maxOrder['max_order'] ?? 0) + 1
        ]);
    }

    /**
     * Récupère les produits d'une catégorie
     */
    public static function getProducts(int $categoryId): array {
        return Database::fetchAll(
            "SELECT * FROM products WHERE category_id = ? ORDER BY sort_order",
            [$categoryId]
        );
    }

    /**
     * Récupère tous les produits d'un restaurant
     */
    public static function getAllProducts(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT p.*, c.name as category_name
             FROM products p
             JOIN categories c ON p.category_id = c.id
             WHERE p.restaurant_id = ?
             ORDER BY c.sort_order, p.sort_order",
            [$restaurantId]
        );
    }

    /**
     * Ajoute un produit
     */
    public static function addProduct(int $restaurantId, array $data): int {
        $slug = self::slugify($data['name']);
        $maxOrder = Database::fetchOne(
            "SELECT MAX(sort_order) as max_order FROM products WHERE category_id = ?",
            [$data['category_id']]
        );

        $productId = Database::insert('products', [
            'restaurant_id' => $restaurantId,
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'price_solo' => $data['priceSolo'],
            'price_menu' => $data['priceMenu'] ?? null,
            'status' => 'available',
            'sort_order' => ($maxOrder['max_order'] ?? 0) + 1
        ]);

        // Lier les suppléments
        if (!empty($data['supplements'])) {
            foreach ($data['supplements'] as $supId) {
                Database::query(
                    "INSERT IGNORE INTO product_supplements (product_id, supplement_id) VALUES (?, ?)",
                    [$productId, $supId]
                );
            }
        }

        return $productId;
    }

    /**
     * Met à jour un produit
     */
    public static function updateProduct(int $productId, array $data): bool {
        $updateData = [];

        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['description'])) $updateData['description'] = $data['description'];
        if (isset($data['priceSolo'])) $updateData['price_solo'] = $data['priceSolo'];
        if (isset($data['priceMenu'])) $updateData['price_menu'] = $data['priceMenu'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['image'])) $updateData['image'] = $data['image'];

        if (!empty($updateData)) {
            Database::update('products', $updateData, ['id' => $productId]);
        }

        // Mettre à jour les suppléments
        if (isset($data['supplements'])) {
            Database::query("DELETE FROM product_supplements WHERE product_id = ?", [$productId]);
            foreach ($data['supplements'] as $supId) {
                Database::query(
                    "INSERT INTO product_supplements (product_id, supplement_id) VALUES (?, ?)",
                    [$productId, $supId]
                );
            }
        }

        return true;
    }

    /**
     * Supprime un produit
     */
    public static function deleteProduct(int $productId): bool {
        return Database::delete('products', ['id' => $productId]) > 0;
    }

    /**
     * Récupère tous les suppléments
     */
    public static function getSupplements(int $restaurantId): array {
        return Database::fetchAll(
            "SELECT * FROM supplements WHERE restaurant_id = ? ORDER BY sort_order",
            [$restaurantId]
        );
    }

    /**
     * Ajoute un supplément
     */
    public static function addSupplement(int $restaurantId, string $name, float $price): int {
        return Database::insert('supplements', [
            'restaurant_id' => $restaurantId,
            'name' => $name,
            'price' => $price,
            'status' => 'available'
        ]);
    }

    /**
     * Met à jour un supplément
     */
    public static function updateSupplement(int $supplementId, array $data): bool {
        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['price'])) $updateData['price'] = $data['price'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];

        return Database::update('supplements', $updateData, ['id' => $supplementId]) > 0;
    }

    /**
     * Supprime un supplément
     */
    public static function deleteSupplement(int $supplementId): bool {
        return Database::delete('supplements', ['id' => $supplementId]) > 0;
    }

    /**
     * Helper: générer un slug
     */
    private static function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text) ?: 'item-' . time();
    }
}
