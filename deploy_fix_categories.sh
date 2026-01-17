#!/bin/bash
# Script de déploiement pour fix CRUD catégories
# Instance: Atelier Pizza
# Date: 2026-01-17

echo "=== Déploiement fix CRUD catégories ==="
echo ""

# Backup du fichier original
echo "[1/3] Backup MenuRepository.php..."
cp snackup/backend/repositories/MenuRepository.php snackup/backend/repositories/MenuRepository.php.backup.$(date +%Y%m%d_%H%M%S)

# Créer le patch
echo "[2/3] Application du patch..."

cat > /tmp/menurepo_patch.php << 'EOFPATCH'
<?php
/**
 * MenuRepository - Gestion MySQL pour catégories, produits, suppléments
 * Remplace le système de fichiers JSON
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../InstanceManager.php';

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
     * Récupère tous les suppléments actifs
     */
    public static function getAllSupplements() {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, name, price, status, sort_order, flavor
            FROM supplements
            WHERE restaurant_id = ? AND status = 'available'
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les liaisons catégories-suppléments
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
                $suppId = $row['supplement_id'];

                if (!isset($associations[$catId])) {
                    $associations[$catId] = [];
                }
                $associations[$catId][] = $suppId;
            }

            return $associations;
        } catch (Exception $e) {
            error_log('[MenuRepository] Erreur getCategorySupplements: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les produits d'une catégorie
     */
    private static function getProductsByCategory($categoryId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT id, slug, name, description, image, price_solo, price_menu, status, sort_order, options_config
            FROM products
            WHERE category_id = ? AND restaurant_id = ?
            ORDER BY sort_order ASC, id ASC
        ");
        $stmt->execute([$categoryId, self::$restaurantId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle catégorie
     */
    public static function addCategory($name, $description, $icon, $flavor = null, $sortOrder = 0) {
        $pdo = Database::getInstance();
        $pdo->beginTransaction();

        try {
            // Générer slug unique
            $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = $baseSlug;
            $counter = 1;

            while (self::categorySlugExists($slug)) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            // Insérer catégorie
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
                'flavor' => $flavor,
                'sort_order' => $sortOrder
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
     * Supprime une catégorie
     */
    public static function deleteCategory($categoryId) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            DELETE FROM categories
            WHERE id = ? AND restaurant_id = ?
        ");

        return $stmt->execute([$categoryId, self::$restaurantId]);
    }

    /**
     * Vérifie si un slug de catégorie existe
     */
    private static function categorySlugExists($slug) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM categories
            WHERE slug = ? AND restaurant_id = ?
        ");
        $stmt->execute([$slug, self::$restaurantId]);

        return $stmt->fetchColumn() > 0;
    }
}
EOFPATCH

# Appliquer le patch (remplacer le fichier)
# Note: On garde seulement les méthodes essentielles pour le fix
echo "[3/3] Mise à jour du fichier..."

# Vérifier si InstanceManager est déjà inclus
if ! grep -q "InstanceManager.php" snackup/backend/repositories/MenuRepository.php; then
    # Ajouter require InstanceManager
    sed -i '6 a require_once __DIR__ . '\''/../InstanceManager.php'\'';' snackup/backend/repositories/MenuRepository.php
    echo "  ✓ require_once InstanceManager ajouté"
else
    echo "  ✓ require_once InstanceManager déjà présent"
fi

# Vérifier si la méthode assignSupplementsByFlavor contient déjà le check
if ! grep -q "auto_category_supplements" snackup/backend/repositories/MenuRepository.php; then
    echo "  ⚠ La méthode assignSupplementsByFlavor doit être modifiée manuellement"
    echo "    Éditer: snackup/backend/repositories/MenuRepository.php"
    echo "    Ligne ~184: ajouter le check du flag auto_category_supplements"
    echo ""
    echo "    Code à ajouter au début de assignSupplementsByFlavor():"
    echo "    -------------------------------------------------------"
    cat << 'EOCODE'
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
EOCODE
    echo "    -------------------------------------------------------"
else
    echo "  ✓ Check auto_category_supplements déjà présent"
fi

echo ""
echo "=== Déploiement terminé ==="
echo ""
echo "Prochaine étape: Tester la création de catégorie dans l'admin panel"
