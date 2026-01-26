<?php
/**
 * MenuRepository - Gestion MySQL pour catégories, produits, suppléments
 * Remplace le système de fichiers JSON
 */
require_once __DIR__ . '/../InstanceManager.php';

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../InstanceManager.php';

class MenuRepository {
    public static $restaurantId = 3; // Par défaut Le Marvelous, peut être changé

    /**
     * Récupère toutes les catégories actives avec leurs produits
     */
    public static function getAllCategories() {
        $pdo = Database::getInstance();

        // Récupérer catégories actives
        $stmt = $pdo->prepare("
            SELECT id, slug, name, description, icon, flavor, product_type, sort_order
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
                   status, sort_order, base_ingredients as baseIngredients
            FROM products
            WHERE category_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$categoryId]);

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse base_ingredients JSON string to array
        foreach ($products as &$product) {
            if (!empty($product['baseIngredients'])) {
                $product['baseIngredients'] = json_decode($product['baseIngredients'], true) ?: [];
            } else {
                $product['baseIngredients'] = [];
            }
        }

        return $products;
    }

    /**
     * Récupère tous les suppléments
     */
    public static function getAllSupplements() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, flavor, category, price, status
            FROM supplements
            WHERE restaurant_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([self::$restaurantId]);

        $supplements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $supp) {
            $supplements[$supp['id']] = [
                'id' => $supp['id'],
                'name' => $supp['name'],
                'flavor' => $supp['flavor'],
                'category' => $supp['category'],
                'price' => (float)$supp['price'],
                'status' => $supp['status']
            ];
        }

        return $supplements;
    }

    /**
     * Récupère les associations catégories -> suppléments
     */
    public static function getCategorySupplements() {
        $pdo = Database::getInstance();

        try {
            $stmt = $pdo->prepare("
                SELECT cs.category_id, cs.supplement_id
                FROM category_supplements cs
                WHERE EXISTS (
                    SELECT 1 FROM categories c
                    WHERE c.id = cs.category_id AND c.restaurant_id = ?
                )
            ");
            $stmt->execute([self::$restaurantId]);

            $associations = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $catId = $row['category_id'];
                if (!isset($associations[$catId])) {
                    $associations[$catId] = [];
                }
                $associations[$catId][] = $row['supplement_id'];
            }

            return $associations;
        } catch (PDOException $e) {
            // Si la table n'existe pas encore, retourner un tableau vide
            if ($e->getCode() === '42S02') { // Table doesn't exist
                return [];
            }
            throw $e;
        }
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
     * Feature désactivable via config instance (auto_category_supplements)
     */
    /**
     * Assigne automatiquement les suppléments selon le flavor
     * Feature désactivable via config instance (auto_category_supplements)
     */
    /**
     * Assigne automatiquement les suppléments selon le flavor
     * Feature désactivable via config instance (auto_category_supplements)
     */
    private static function assignSupplementsByFlavor($categoryId, $flavor) {
        // Vérifier si la feature est activée pour cette instance
        try {
            $config = InstanceManager::loadConfig();
            $featureEnabled = $config['features']['auto_category_supplements'] ?? true;

            if (!$featureEnabled) {
                // Feature désactivée pour cette instance (ex: pizzerias)
                return;
            }
        } catch (Exception $e) {
            // En cas d'erreur de chargement config, continuer (backward compatibility)
            error_log('[MenuRepository] Impossible de charger config instance: ' . $e->getMessage());
        }

        $pdo = Database::getInstance();

        // Récupérer les suppléments du type correspondant
        $stmt = $pdo->prepare("
            SELECT id FROM supplements
            WHERE restaurant_id = ? AND (flavor = ? OR flavor = 'both')
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
            SET is_active = 0
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$categoryId, self::$restaurantId]);
    }

    /**
     * Ajoute un produit
     */
    public static function addProduct($categoryId, $name, $description, $image, $priceSolo, $priceMenu = null, $baseIngredients = []) {
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

        // Convertir baseIngredients en JSON
        $baseIngredientsJson = !empty($baseIngredients) ? json_encode($baseIngredients, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $pdo->prepare("
            INSERT INTO products
            (restaurant_id, category_id, slug, name, description, image, price_solo, price_menu, base_ingredients, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)
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
            $baseIngredientsJson,
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
    public static function editProduct($productId, $name, $description, $image, $priceSolo, $priceMenu, $status, $baseIngredients = null) {
        $pdo = Database::getInstance();

        // Convertir baseIngredients en JSON si fourni
        $baseIngredientsJson = null;
        if ($baseIngredients !== null) {
            $baseIngredientsJson = !empty($baseIngredients) ? json_encode($baseIngredients, JSON_UNESCAPED_UNICODE) : null;
        }

        // Si baseIngredients est fourni, l'inclure dans l'UPDATE
        if ($baseIngredients !== null) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, description = ?, image = ?,
                    price_solo = ?, price_menu = ?, status = ?, base_ingredients = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $description,
                $image,
                $priceSolo,
                $priceMenu,
                $status,
                $baseIngredientsJson,
                $productId,
                self::$restaurantId
            ]);
        } else {
            // Comportement par défaut sans modifier base_ingredients
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
