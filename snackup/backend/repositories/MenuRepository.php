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
            SELECT id, name, slug, description, icon, icon_image, flavor, sort_order
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
            SELECT id, name, description, image, badge,
                   price_solo as priceSolo, price_menu as priceMenu,
                   status, sort_order, base_ingredients as baseIngredients
            FROM products
            WHERE category_id = ? AND restaurant_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$categoryId, self::$restaurantId]);

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
            SELECT id, name, flavor, COALESCE(group_name, 'autres') as group_name, price, status
            FROM supplements
            WHERE restaurant_id = ? AND flavor != 'sucre'
            ORDER BY group_name ASC, sort_order ASC
        ");
        $stmt->execute([self::$restaurantId]);

        $supplements = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $supp) {
            $supplements[$supp['id']] = [
                'id' => $supp['id'],
                'name' => $supp['name'],
                'flavor' => $supp['flavor'],
                'group_name' => $supp['group_name'],
                'category' => $supp['group_name'], // Alias pour compatibilité admin
                'price' => (float)$supp['price'],
                'status' => $supp['status']
            ];
        }

        return $supplements;
    }

    /**
     * Récupère les suppléments groupés par group_name
     */
    public static function getSupplementsGrouped() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, flavor, COALESCE(group_name, 'autres') as group_name, price, status
            FROM supplements
            WHERE restaurant_id = ? AND status = 'available' AND flavor != 'sucre'
            ORDER BY group_name ASC, sort_order ASC
        ");
        $stmt->execute([self::$restaurantId]);

        $grouped = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $supp) {
            $group = $supp['group_name'];
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = [
                'id' => $supp['id'],
                'name' => $supp['name'],
                'price' => (float)$supp['price']
            ];
        }

        return $grouped;
    }

    /**
     * Récupère les groupes de suppléments distincts depuis la DB
     */
    public static function getSupplementGroups() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT DISTINCT COALESCE(group_name, 'autres') as group_name
            FROM supplements
            WHERE restaurant_id = ? AND flavor != 'sucre' AND status = 'available'
            ORDER BY group_name ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'group_name');
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
    public static function addCategory($name, $description, $icon, $flavor, $iconImage = null) {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

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
                (restaurant_id, name, description, icon, icon_image, flavor, sort_order, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                self::$restaurantId,
                $name,
                $description,
                $icon,
                $iconImage,
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
                'icon_image' => $iconImage,
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
    public static function editCategory($categoryId, $name, $description, $icon, $flavor, $iconImage = null) {
        $pdo = Database::getInstance();

        // Si icon_image est fourni (même vide string pour supprimer), l'inclure
        if ($iconImage !== null) {
            $stmt = $pdo->prepare("
                UPDATE categories
                SET name = ?, description = ?, icon = ?, icon_image = ?, flavor = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $description,
                $icon,
                $iconImage ?: null,
                $flavor ?: null,
                $categoryId,
                self::$restaurantId
            ]);
        }

        // Sinon, ne pas modifier icon_image
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
     * Génère un slug unique pour un produit
     */
    private static function generateProductSlug($name) {
        $pdo = Database::getInstance();

        // Générer le slug de base
        $baseSlug = strtolower(trim($name));
        $baseSlug = preg_replace('/[àáâãäå]/u', 'a', $baseSlug);
        $baseSlug = preg_replace('/[èéêë]/u', 'e', $baseSlug);
        $baseSlug = preg_replace('/[ìíîï]/u', 'i', $baseSlug);
        $baseSlug = preg_replace('/[òóôõö]/u', 'o', $baseSlug);
        $baseSlug = preg_replace('/[ùúûü]/u', 'u', $baseSlug);
        $baseSlug = preg_replace('/[ç]/u', 'c', $baseSlug);
        $baseSlug = preg_replace('/[^a-z0-9]+/', '-', $baseSlug);
        $baseSlug = trim($baseSlug, '-');

        if (empty($baseSlug)) {
            $baseSlug = 'produit';
        }

        // Vérifier unicité et ajouter suffix si nécessaire
        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE restaurant_id = ? AND slug = ?");
            $stmt->execute([self::$restaurantId, $slug]);
            if ($stmt->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Ajoute un produit
     */
    public static function addProduct($categoryId, $name, $description, $image, $priceSolo, $priceMenu = null, $baseIngredients = []) {
        $pdo = Database::getInstance();

        // Générer un slug unique
        $slug = self::generateProductSlug($name);

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
            'name' => $name
        ];
    }

    /**
     * Modifie un produit
     */
    public static function editProduct($productId, $name, $description, $image, $priceSolo, $priceMenu, $status, $baseIngredients = null, $badge = null) {
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
                SET name = ?, description = ?, image = ?, badge = ?,
                    price_solo = ?, price_menu = ?, status = ?, base_ingredients = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $description,
                $image,
                $badge,
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
                SET name = ?, description = ?, image = ?, badge = ?,
                    price_solo = ?, price_menu = ?, status = ?
                WHERE id = ? AND restaurant_id = ?
            ");

            return $stmt->execute([
                $name,
                $description,
                $image,
                $badge,
                $priceSolo,
                $priceMenu,
                $status,
                $productId,
                self::$restaurantId
            ]);
        }
    }

    /**
     * Supprime un produit définitivement
     */
    public static function deleteProduct($productId) {
        $pdo = Database::getInstance();

        // Suppression définitive (hard delete)
        $stmt = $pdo->prepare("
            DELETE FROM products
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$productId, self::$restaurantId]);
    }

    /**
     * Récupère toutes les formules actives
     */
    public static function getAllFormules() {
        $pdo = Database::getInstance();

        // Vérifier si la colonne badge existe
        $hasBadgeColumn = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM formules LIKE 'badge'");
            $hasBadgeColumn = $checkStmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasBadgeColumn = false;
        }

        // Construire la requête selon les colonnes disponibles
        $selectFields = "id, name, description, image, price, original_price as originalPrice, includes, status, sort_order";
        if ($hasBadgeColumn) {
            $selectFields = "id, name, description, image, badge, price, original_price as originalPrice, includes, status, sort_order";
        }

        $stmt = $pdo->prepare("
            SELECT {$selectFields}
            FROM formules
            WHERE restaurant_id = ? AND status = 'available'
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([self::$restaurantId]);

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

            // S'assurer que badge existe (même vide)
            if (!isset($formule['badge'])) {
                $formule['badge'] = null;
            }
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
        $stmt->execute([self::$restaurantId]);
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
                self::$restaurantId,
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
                self::$restaurantId,
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
                    self::$restaurantId
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
                    self::$restaurantId
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
                    self::$restaurantId
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
                    self::$restaurantId
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

        return $stmt->execute([$formuleId, self::$restaurantId]);
    }

    /**
     * Récupère les paramètres de la section Featured
     */
    public static function getFeaturedSettings() {
        $pdo = Database::getInstance();

        try {
            $stmt = $pdo->prepare("
                SELECT enabled, title, subtitle
                FROM featured_settings
                WHERE restaurant_id = ?
            ");
            $stmt->execute([self::$restaurantId]);
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$settings) {
                // Paramètres par défaut si non configurés
                return [
                    'enabled' => true,
                    'title' => 'Sélection pour vous',
                    'subtitle' => 'Nos produits les plus appréciés'
                ];
            }

            return [
                'enabled' => (bool)$settings['enabled'],
                'title' => $settings['title'],
                'subtitle' => $settings['subtitle']
            ];
        } catch (PDOException $e) {
            // Table n'existe pas encore
            return [
                'enabled' => true,
                'title' => 'Sélection pour vous',
                'subtitle' => 'Nos produits les plus appréciés'
            ];
        }
    }

    /**
     * Met à jour les paramètres de la section Featured
     */
    public static function updateFeaturedSettings($enabled, $title, $subtitle) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            INSERT INTO featured_settings (restaurant_id, enabled, title, subtitle)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE enabled = ?, title = ?, subtitle = ?
        ");

        return $stmt->execute([
            self::$restaurantId,
            $enabled ? 1 : 0,
            $title,
            $subtitle,
            $enabled ? 1 : 0,
            $title,
            $subtitle
        ]);
    }

    /**
     * Récupère les produits featured (sélection pour vous)
     */
    public static function getFeaturedProducts() {
        $pdo = Database::getInstance();

        try {
            $stmt = $pdo->prepare("
                SELECT p.id, p.name, p.description, p.image,
                       p.price_solo as priceSolo, p.price_menu as priceMenu,
                       p.status, c.slug as categorySlug
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.restaurant_id = ? AND p.is_featured = 1 AND p.status = 'available'
                ORDER BY p.sort_order ASC
            ");
            $stmt->execute([self::$restaurantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Colonne is_featured n'existe pas encore
            return [];
        }
    }

    /**
     * Récupère les IDs des produits featured
     */
    public static function getFeaturedProductIds() {
        $pdo = Database::getInstance();

        try {
            $stmt = $pdo->prepare("
                SELECT id FROM products
                WHERE restaurant_id = ? AND is_featured = 1
            ");
            $stmt->execute([self::$restaurantId]);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Active/désactive le statut featured d'un produit
     */
    public static function setProductFeatured($productId, $isFeatured) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE products
            SET is_featured = ?
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([
            $isFeatured ? 1 : 0,
            $productId,
            self::$restaurantId
        ]);
    }

    /**
     * Met à jour tous les produits featured en une fois
     */
    public static function updateFeaturedProducts($productIds) {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // D'abord, retirer featured de tous les produits
            $stmt = $pdo->prepare("
                UPDATE products SET is_featured = 0
                WHERE restaurant_id = ?
            ");
            $stmt->execute([self::$restaurantId]);

            // Ensuite, marquer les produits sélectionnés comme featured
            if (!empty($productIds)) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare("
                    UPDATE products SET is_featured = 1
                    WHERE id IN ($placeholders) AND restaurant_id = ?
                ");
                $params = array_merge($productIds, [self::$restaurantId]);
                $stmt->execute($params);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
