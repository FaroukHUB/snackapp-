<?php
/**
 * MenuRepository - Gestion MySQL pour catégories, produits, suppléments
 * Remplace le système de fichiers JSON
 */
require_once __DIR__ . '/../InstanceManager.php';

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../InstanceManager.php';

class MenuRepository {

    /**
     * ID du restaurant pour multi-instance (peut être défini par bootstrap.php)
     * @var int|null
     */
    public static $restaurantId = null;

    /**
     * Récupère le restaurant_id de l'instance courante
     */
    private static function getRestaurantId() {
        // Si le restaurant ID a été défini statiquement (par bootstrap.php), l'utiliser
        if (self::$restaurantId !== null) {
            return self::$restaurantId;
        }

        // Sinon, charger depuis la config de l'instance
        $config = InstanceManager::loadConfig();
        return $config['app']['restaurant_id'] ?? null;
    }

    /**
     * Récupère toutes les catégories actives avec leurs produits
     */
    public static function getAllCategories() {
        $pdo = Database::getInstance();

        // Récupérer catégories actives
        $stmt = $pdo->prepare("
            SELECT id, name, slug, description, icon, flavor, sort_order
            FROM categories
            WHERE restaurant_id = ? AND is_active = 1
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([self::getRestaurantId()]);
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
            SELECT id, name, description, image,
                   price_solo as priceSolo, price_menu as priceMenu,
                   status, sort_order, base_ingredients as baseIngredients
            FROM products
            WHERE category_id = ? AND restaurant_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$categoryId, self::getRestaurantId()]);

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
            SELECT id, name, flavor, price, status
            FROM supplements
            WHERE restaurant_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([self::getRestaurantId()]);

        $supplements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $supp) {
            $supplements[$supp['id']] = [
                'id' => $supp['id'],
                'name' => $supp['name'],
                'flavor' => $supp['flavor'],
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
            $stmt->execute([self::getRestaurantId()]);

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

            // Déterminer sort_order (dernier + 1)
            $stmt = $pdo->prepare("
                SELECT MAX(sort_order) as max_order
                FROM categories
                WHERE restaurant_id = ?
            ");
            $stmt->execute([self::getRestaurantId()]);
            $sortOrder = ($stmt->fetchColumn() ?: 0) + 1;

            // Générer le slug
            $slug = self::generateSlug($name, 'categories');

            // Insérer la catégorie
            $stmt = $pdo->prepare("
                INSERT INTO categories
                (restaurant_id, name, slug, description, icon, flavor, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                self::getRestaurantId(),
                $name,
                $slug,
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
        $stmt->execute([self::getRestaurantId(), $flavor]);
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

        // Générer un nouveau slug basé sur le nouveau nom
        $slug = self::generateSlug($name, 'categories', $categoryId);

        $stmt = $pdo->prepare("
            UPDATE categories
            SET name = ?, slug = ?, description = ?, icon = ?, flavor = ?
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([
            $name,
            $slug,
            $description,
            $icon,
            $flavor ?: null,
            $categoryId,
            self::getRestaurantId()
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

        return $stmt->execute([$categoryId, self::getRestaurantId()]);
    }

    /**
     * Ajoute un produit
     */
    public static function addProduct($categoryId, $name, $description, $image, $priceSolo, $priceMenu = null, $baseIngredients = []) {
        $pdo = Database::getInstance();

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

        // Générer le slug
        $slug = self::generateSlug($name, 'products');

        $stmt = $pdo->prepare("
            INSERT INTO products
            (restaurant_id, category_id, name, slug, description, image, price_solo, price_menu, base_ingredients, status, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)
        ");

        $stmt->execute([
            self::getRestaurantId(),
            $categoryId,
            $name,
            $slug,
            $description,
            $image,
            $priceSolo,
            $priceMenu,
            $baseIngredientsJson,
            $sortOrder
        ]);

        return [
            'id' => $pdo->lastInsertId(),
            'name' => $name
        ];
    }

    /**
     * Modifie un produit
     */
    public static function editProduct($productId, $name, $description, $image, $priceSolo, $priceMenu, $status, $baseIngredients = null) {
        $pdo = Database::getInstance();

        // Générer un nouveau slug basé sur le nouveau nom
        $slug = self::generateSlug($name, 'products', $productId);

        // Convertir baseIngredients en JSON si fourni
        $baseIngredientsJson = null;
        if ($baseIngredients !== null) {
            $baseIngredientsJson = !empty($baseIngredients) ? json_encode($baseIngredients, JSON_UNESCAPED_UNICODE) : null;
        }

        // Si baseIngredients est fourni, l'inclure dans l'UPDATE
        if ($baseIngredients !== null) {
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, slug = ?, description = ?, image = ?,
                    price_solo = ?, price_menu = ?, status = ?, base_ingredients = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $slug,
                $description,
                $image,
                $priceSolo,
                $priceMenu,
                $status,
                $baseIngredientsJson,
                $productId,
                self::getRestaurantId()
            ]);
        } else {
            // Comportement par défaut sans modifier base_ingredients
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, slug = ?, description = ?, image = ?,
                    price_solo = ?, price_menu = ?, status = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $slug,
                $description,
                $image,
                $priceSolo,
                $priceMenu,
                $status,
                $productId,
                self::getRestaurantId()
            ]);
        }
    }

    /**
     * Supprime un produit (désactivation)
     */
    public static function deleteProduct($productId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE products
            SET status = 'unavailable'
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$productId, self::getRestaurantId()]);
    }

    /**
     * Récupère toutes les formules actives
     */
    public static function getAllFormules() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, description, image,
                   price, original_price as originalPrice,
                   includes, status, sort_order
            FROM formules
            WHERE restaurant_id = ? AND status = 'available'
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([self::getRestaurantId()]);

        $formules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse includes JSON string to array
        foreach ($formules as &$formule) {
            if (!empty($formule['includes'])) {
                $formule['includes'] = json_decode($formule['includes'], true) ?: [];
            } else {
                $formule['includes'] = [];
            }

            // Convert numeric strings to proper types
            $formule['price'] = (float)$formule['price'];
            $formule['originalPrice'] = !empty($formule['originalPrice']) ? (float)$formule['originalPrice'] : null;
        }

        return $formules;
    }

    /**
     * Détecte le type de la colonne id dans la table formules
     * @return string 'int' ou 'varchar'
     */
    private static function detectIdType(): string {
        static $idType = null;

        if ($idType === null) {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("DESCRIBE formules");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($columns as $col) {
                if ($col['Field'] === 'id') {
                    $type = strtolower($col['Type']);
                    $idType = (strpos($type, 'int') !== false) ? 'int' : 'varchar';
                    error_log("[MenuRepository] Détection type colonne 'id': {$type} → {$idType}");
                    break;
                }
            }
        }

        return $idType ?? 'int';
    }

    /**
     * Génère un ID unique pour les formules (si varchar)
     * Format: formule-{timestamp}-{random}
     */
    private static function generateFormuleId(): string {
        return 'formule-' . time() . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Ajoute une formule
     */
    public static function addFormule($name, $description, $price, $originalPrice = null, $image = null, $includes = []) {
        $pdo = Database::getInstance();

        // Détecter le type de la colonne id
        $idType = self::detectIdType();

        // Déterminer sort_order
        $stmt = $pdo->prepare("
            SELECT MAX(sort_order) as max_order
            FROM formules
            WHERE restaurant_id = ?
        ");
        $stmt->execute([self::getRestaurantId()]);
        $sortOrder = ($stmt->fetchColumn() ?: 0) + 1;

        // Convertir includes en JSON
        $includesJson = !empty($includes) ? json_encode($includes, JSON_UNESCAPED_UNICODE) : null;

        if ($idType === 'varchar') {
            // Colonne id est VARCHAR → Générer un ID unique
            $generatedId = self::generateFormuleId();

            error_log("[MenuRepository] 🔑 Génération ID string pour formule: {$generatedId}");

            $stmt = $pdo->prepare("
                INSERT INTO formules
                (id, restaurant_id, name, description, image, price, original_price, includes, status, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'available', ?)
            ");

            $stmt->execute([
                $generatedId,
                self::getRestaurantId(),
                $name,
                $description,
                $image,
                $price,
                $originalPrice,
                $includesJson,
                $sortOrder
            ]);

            $insertedId = $generatedId;

        } else {
            // Colonne id est INT AUTO_INCREMENT → Comportement classique
            $stmt = $pdo->prepare("
                INSERT INTO formules
                (restaurant_id, name, description, image, price, original_price, includes, status, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'available', ?)
            ");

            $stmt->execute([
                self::getRestaurantId(),
                $name,
                $description,
                $image,
                $price,
                $originalPrice,
                $includesJson,
                $sortOrder
            ]);

            $insertedId = $pdo->lastInsertId();

            // 🔒 VALIDATION: S'assurer que l'AUTO_INCREMENT a fonctionné
            if (!$insertedId || $insertedId === '0' || $insertedId === '') {
                error_log('[MenuRepository] ❌ ERREUR CRITIQUE: lastInsertId() a retourné une valeur invalide: ' . var_export($insertedId, true));
                throw new Exception('Échec AUTO_INCREMENT: ID non généré par la base de données');
            }
        }

        error_log("[MenuRepository] ✅ Formule insérée avec ID ({$idType}): {$insertedId}");

        return [
            'id' => $insertedId,
            'name' => $name,
            'price' => $price,
            'originalPrice' => $originalPrice
        ];
    }

    /**
     * Modifie une formule
     */
    public static function editFormule($formuleId, $name, $description, $price, $originalPrice, $status, $image = null, $includes = null) {
        $pdo = Database::getInstance();

        // Si includes est fourni, le convertir en JSON
        $includesJson = null;
        if ($includes !== null) {
            $includesJson = !empty($includes) ? json_encode($includes, JSON_UNESCAPED_UNICODE) : null;
        }

        // Si l'image est fournie, l'inclure dans l'UPDATE
        if ($image !== null) {
            if ($includes !== null) {
                $stmt = $pdo->prepare("
                    UPDATE formules
                    SET name = ?, description = ?, price = ?, original_price = ?,
                        status = ?, image = ?, includes = ?
                    WHERE id = ? AND restaurant_id = ?
                ");

                return $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $originalPrice,
                    $status,
                    $image,
                    $includesJson,
                    $formuleId,
                    self::getRestaurantId()
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE formules
                    SET name = ?, description = ?, price = ?, original_price = ?,
                        status = ?, image = ?
                    WHERE id = ? AND restaurant_id = ?
                ");

                return $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $originalPrice,
                    $status,
                    $image,
                    $formuleId,
                    self::getRestaurantId()
                ]);
            }
        } else {
            // Pas d'image, ne pas modifier le champ image
            if ($includes !== null) {
                $stmt = $pdo->prepare("
                    UPDATE formules
                    SET name = ?, description = ?, price = ?, original_price = ?,
                        status = ?, includes = ?
                    WHERE id = ? AND restaurant_id = ?
                ");

                return $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $originalPrice,
                    $status,
                    $includesJson,
                    $formuleId,
                    self::getRestaurantId()
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE formules
                    SET name = ?, description = ?, price = ?, original_price = ?,
                        status = ?
                    WHERE id = ? AND restaurant_id = ?
                ");

                return $stmt->execute([
                    $name,
                    $description,
                    $price,
                    $originalPrice,
                    $status,
                    $formuleId,
                    self::getRestaurantId()
                ]);
            }
        }
    }

    /**
     * Supprime une formule (désactivation)
     */
    public static function deleteFormule($formuleId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE formules
            SET status = 'unavailable'
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$formuleId, self::getRestaurantId()]);
    }

    /**
     * Génère un slug unique depuis un nom
     */
    private static function generateSlug($name, $table = 'categories', $existingId = null) {
        // Normaliser le nom en slug
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        // Vérifier l'unicité dans la table
        $pdo = Database::getInstance();
        $baseSlug = $slug;
        $counter = 1;

        while (true) {
            $checkSql = "SELECT COUNT(*) FROM $table WHERE slug = ? AND restaurant_id = ?";
            $params = [$slug, self::getRestaurantId()];

            // Si on modifie une entrée existante, exclure son propre ID
            if ($existingId) {
                $checkSql .= " AND id != ?";
                $params[] = $existingId;
            }

            $stmt = $pdo->prepare($checkSql);
            $stmt->execute($params);

            if ($stmt->fetchColumn() == 0) {
                break; // Slug disponible
            }

            // Slug existe déjà, essayer avec un suffixe
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
