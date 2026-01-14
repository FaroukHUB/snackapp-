<?php
/**
 * MenuRepository - Gestion MySQL pour catégories, produits, suppléments
 * Remplace le système de fichiers JSON
 */

require_once __DIR__ . '/../Database.php';

class MenuRepository {
    public static $restaurantId = 2; // Par défaut Le Marvelous, peut être changé

    /**
     * Récupère toutes les catégories actives avec leurs produits
     */
    public static function getAllCategories() {
        $pdo = Database::getInstance();

        // Récupérer catégories actives
        $stmt = $pdo->prepare("
            SELECT id, slug, name, description, icon, flavor, sort_order
            FROM categories
            WHERE restaurant_id = ? AND is_active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([self::$restaurantId]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pour chaque catégorie, récupérer ses produits
        foreach ($categories as &$cat) {
            $cat['items'] = self::getProductsByCategory($cat['id']);
        }

        return $categories;
    }

    /**
     * Récupère les produits d'une catégorie
     */
    public static function getProductsByCategory($categoryId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, slug, name, description, image,
                   price_solo as priceSolo, price_menu as priceMenu,
                   status, sort_order
            FROM products
            WHERE category_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les suppléments
     */
    public static function getAllSupplements() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, slug, name, price, type, status
            FROM supplements
            WHERE restaurant_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([self::$restaurantId]);

        $supplements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $supp) {
            $supplements[$supp['slug']] = [
                'id' => $supp['slug'],
                'name' => $supp['name'],
                'price' => (int)$supp['price'],
                'status' => $supp['status'],
                'type' => $supp['type']
            ];
        }

        return $supplements;
    }

    /**
     * Récupère les associations catégories -> suppléments
     */
    public static function getCategorySupplements() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT c.slug as category_slug, s.slug as supplement_slug
            FROM category_supplements cs
            JOIN categories c ON cs.category_id = c.id
            JOIN supplements s ON cs.supplement_id = s.id
            WHERE c.restaurant_id = ?
        ");
        $stmt->execute([self::$restaurantId]);

        $associations = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (!isset($associations[$row['category_slug']])) {
                $associations[$row['category_slug']] = [];
            }
            $associations[$row['category_slug']][] = $row['supplement_slug'];
        }

        return $associations;
    }

    /**
     * Ajoute une catégorie avec auto-assignment des suppléments
     */
    public static function addCategory($name, $description, $icon, $flavor) {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Générer un slug unique
            $slug = self::generateSlug($name);

            // Déterminer sort_order (dernier + 1)
            $stmt = $pdo->prepare("
                SELECT MAX(sort_order) as max_order
                FROM categories
                WHERE restaurant_id = ?
            ");
            $stmt->execute([self::$restaurantId]);
            $sortOrder = ($stmt->fetchColumn() ?: 0) + 1;

            // Insérer la catégorie
            $stmt = $pdo->prepare("
                INSERT INTO categories
                (restaurant_id, slug, name, description, icon, flavor, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                self::$restaurantId,
                $slug,
                $name,
                $description,
                $icon,
                $flavor ?: null,
                $sortOrder
            ]);

            $categoryId = $pdo->lastInsertId();

            // Auto-assignment suppléments selon flavor
            if ($flavor === 'sale' || $flavor === 'sucre') {
                self::assignSupplementsByFlavor($categoryId, $flavor);
            }

            $pdo->commit();

            return [
                'id' => $categoryId,
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'flavor' => $flavor
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Assigne automatiquement les suppléments selon le flavor
     */
    private static function assignSupplementsByFlavor($categoryId, $flavor) {
        $pdo = Database::getInstance();

        // Récupérer les suppléments du type correspondant
        $stmt = $pdo->prepare("
            SELECT id FROM supplements
            WHERE restaurant_id = ? AND (type = ? OR type = 'both')
        ");
        $stmt->execute([self::$restaurantId, $flavor]);
        $supplements = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Créer les associations
        $stmt = $pdo->prepare("
            INSERT INTO category_supplements (category_id, supplement_id)
            VALUES (?, ?)
        ");

        foreach ($supplements as $suppId) {
            $stmt->execute([$categoryId, $suppId]);
        }
    }

    /**
     * Modifie une catégorie
     */
    public static function editCategory($categoryId, $name, $description, $icon, $flavor) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE categories
            SET name = ?, description = ?, icon = ?, flavor = ?
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([
            $name,
            $description,
            $icon,
            $flavor ?: null,
            $categoryId,
            self::$restaurantId
        ]);
    }

    /**
     * Supprime une catégorie (soft delete)
     */
    public static function deleteCategory($categoryId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE categories
            SET deleted_at = NOW(), is_active = 0
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$categoryId, self::$restaurantId]);
    }

    /**
     * Ajoute un produit
     */
    public static function addProduct($categoryId, $name, $description, $image, $priceSolo, $priceMenu = null) {
        $pdo = Database::getInstance();

        $slug = self::generateSlug($name);

        // Déterminer sort_order
        $stmt = $pdo->prepare("
            SELECT MAX(sort_order) as max_order
            FROM products
            WHERE category_id = ?
        ");
        $stmt->execute([$categoryId]);
        $sortOrder = ($stmt->fetchColumn() ?: 0) + 1;

        $stmt = $pdo->prepare("
            INSERT INTO products
            (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)
        ");

        $stmt->execute([
            self::$restaurantId,
            $categoryId,
            $slug,
            $name,
            $description,
            $image,
            $priceSolo,
            $priceMenu,
            $sortOrder
        ]);

        return [
            'id' => $pdo->lastInsertId(),
            'slug' => $slug,
            'name' => $name
        ];
    }

    /**
     * Modifie un produit
     */
    public static function editProduct($productId, $name, $description, $image, $priceSolo, $priceMenu, $status) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE products
            SET name = ?, description = ?, image = ?,
                price_solo = ?, price_menu = ?, status = ?
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([
            $name,
            $description,
            $image,
            $priceSolo,
            $priceMenu,
            $status,
            $productId,
            self::$restaurantId
        ]);
    }

    /**
     * Supprime un produit (soft delete)
     */
    public static function deleteProduct($productId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE products
            SET deleted_at = NOW()
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$productId, self::$restaurantId]);
    }

    /**
     * Génère un slug unique depuis un nom
     */
    private static function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ajouter un suffix unique si nécessaire
        $slug .= '-' . substr(md5(uniqid()), 0, 8);

        return $slug;
    }
}
