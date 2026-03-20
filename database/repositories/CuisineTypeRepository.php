<?php
/**
 * SnackApp v2 - Cuisine Type Repository
 * Gestion du système de types de cuisine universel (tacos, burger, kebab, etc.)
 */

require_once __DIR__ . '/../Database.php';

class CuisineTypeRepository {

    // ========================================================================
    // TYPES DE CUISINE GLOBAUX (lecture seule)
    // ========================================================================

    /**
     * Récupère tous les types de cuisine disponibles
     */
    public static function getAllTypes(): array {
        return Database::fetchAll(
            "SELECT * FROM cuisine_types ORDER BY sort_order ASC",
            []
        );
    }

    /**
     * Récupère un type de cuisine par ID
     */
    public static function getTypeById(int $id): ?array {
        return Database::fetchOne(
            "SELECT * FROM cuisine_types WHERE id = ?",
            [$id]
        );
    }

    /**
     * Récupère un type de cuisine par slug
     */
    public static function getTypeBySlug(string $slug): ?array {
        return Database::fetchOne(
            "SELECT * FROM cuisine_types WHERE slug = ?",
            [$slug]
        );
    }

    // ========================================================================
    // TYPES ACTIVÉS PAR RESTAURANT
    // ========================================================================

    /**
     * Récupère tous les types activés pour un restaurant
     */
    public static function getRestaurantTypes(int $restaurantId, bool $includeInactive = false): array {
        $sql = "SELECT rct.*, ct.name, ct.slug, ct.icon_slug, ct.description
                FROM restaurant_cuisine_types rct
                JOIN cuisine_types ct ON rct.cuisine_type_id = ct.id
                WHERE rct.restaurant_id = ?";

        if (!$includeInactive) {
            $sql .= " AND rct.is_active = 1 AND rct.deleted_at IS NULL";
        }

        $sql .= " ORDER BY rct.sort_order ASC";

        return Database::fetchAll($sql, [$restaurantId]);
    }

    /**
     * Vérifie si un restaurant a activé un type de cuisine
     */
    public static function hasRestaurantType(int $restaurantId, int $cuisineTypeId): bool {
        $result = Database::fetchOne(
            "SELECT id FROM restaurant_cuisine_types
             WHERE restaurant_id = ? AND cuisine_type_id = ?
             AND deleted_at IS NULL",
            [$restaurantId, $cuisineTypeId]
        );

        return $result !== null;
    }

    /**
     * Active un type de cuisine pour un restaurant
     * COPIE AUTOMATIQUEMENT les étapes et options templates
     *
     * @return int ID du restaurant_cuisine_type créé
     */
    public static function activateCuisineType(int $restaurantId, int $cuisineTypeId, int $sortOrder = 0): int {
        return Database::transaction(function() use ($restaurantId, $cuisineTypeId, $sortOrder) {

            // 1. Vérifier que le type existe
            $cuisineType = self::getTypeById($cuisineTypeId);
            if (!$cuisineType) {
                throw new Exception("Type de cuisine non trouvé");
            }

            // 2. Vérifier que le restaurant n'a pas déjà ce type
            if (self::hasRestaurantType($restaurantId, $cuisineTypeId)) {
                throw new Exception("Ce type de cuisine est déjà activé pour ce restaurant");
            }

            // 3. Créer l'activation dans restaurant_cuisine_types
            $restaurantCuisineTypeId = Database::insert('restaurant_cuisine_types', [
                'restaurant_id' => $restaurantId,
                'cuisine_type_id' => $cuisineTypeId,
                'sort_order' => $sortOrder,
                'is_active' => 1
            ]);

            // 4. Récupérer toutes les étapes template de ce type
            $steps = Database::fetchAll(
                "SELECT * FROM cuisine_type_steps
                 WHERE cuisine_type_id = ?
                 ORDER BY sort_order ASC",
                [$cuisineTypeId]
            );

            // 5. Pour chaque étape template, créer une config restaurant
            foreach ($steps as $step) {
                // Copier l'étape avec les valeurs par défaut
                $restaurantStepId = Database::insert('restaurant_cuisine_steps', [
                    'restaurant_cuisine_type_id' => $restaurantCuisineTypeId,
                    'cuisine_type_step_id' => $step['id'],
                    'custom_name' => null, // NULL = utiliser le nom du template
                    'is_active' => 1,
                    'is_required' => $step['is_required_default'],      // ✅ Copie
                    'min_choices' => $step['min_choices_default'],      // ✅ Copie
                    'max_choices' => $step['max_choices_default'],      // ✅ Copie
                    'allow_removal' => $step['allow_removal_default'],  // ✅ Copie
                    'has_price_modifier' => $step['has_price_modifier_default'], // ✅ Copie
                    'sort_order' => $step['sort_order']
                ]);

                // 6. Récupérer les options exemples de cette étape
                $options = Database::fetchAll(
                    "SELECT * FROM cuisine_type_step_options
                     WHERE cuisine_type_step_id = ?
                     AND is_active = 1
                     AND deleted_at IS NULL
                     ORDER BY sort_order ASC",
                    [$step['id']]
                );

                // 7. Copier chaque option
                foreach ($options as $option) {
                    Database::insert('restaurant_step_options', [
                        'restaurant_cuisine_step_id' => $restaurantStepId,
                        'source_option_id' => $option['id'], // ✅ Lien vers le template
                        'name' => $option['name'],           // ✅ Copie
                        'price_modifier' => $option['price_modifier'], // ✅ Copie
                        'sort_order' => $option['sort_order'], // ✅ Copie
                        'is_active' => 1
                    ]);
                }
            }

            return $restaurantCuisineTypeId;
        });
    }

    /**
     * Désactive un type de cuisine pour un restaurant (soft delete)
     */
    public static function deactivateCuisineType(int $restaurantId, int $cuisineTypeId): bool {
        $rowsAffected = Database::update(
            'restaurant_cuisine_types',
            [
                'is_active' => 0,
                'deleted_at' => date('Y-m-d H:i:s')
            ],
            [
                'restaurant_id' => $restaurantId,
                'cuisine_type_id' => $cuisineTypeId
            ]
        );

        return $rowsAffected > 0;
    }

    // ========================================================================
    // ÉTAPES DE COMPOSITION
    // ========================================================================

    /**
     * Récupère toutes les étapes d'un type activé pour un restaurant
     */
    public static function getRestaurantSteps(int $restaurantCuisineTypeId, bool $includeInactive = false): array {
        $sql = "SELECT rcs.*, cts.name as template_name, cts.slug, cts.description
                FROM restaurant_cuisine_steps rcs
                JOIN cuisine_type_steps cts ON rcs.cuisine_type_step_id = cts.id
                WHERE rcs.restaurant_cuisine_type_id = ?";

        if (!$includeInactive) {
            $sql .= " AND rcs.is_active = 1 AND rcs.deleted_at IS NULL";
        }

        $sql .= " ORDER BY rcs.sort_order ASC";

        return Database::fetchAll($sql, [$restaurantCuisineTypeId]);
    }

    /**
     * Récupère une étape par ID
     */
    public static function getRestaurantStepById(int $id): ?array {
        return Database::fetchOne(
            "SELECT rcs.*, cts.name as template_name, cts.slug
             FROM restaurant_cuisine_steps rcs
             JOIN cuisine_type_steps cts ON rcs.cuisine_type_step_id = cts.id
             WHERE rcs.id = ?",
            [$id]
        );
    }

    /**
     * Met à jour une étape restaurant
     */
    public static function updateRestaurantStep(int $id, array $data): bool {
        $updateData = [];
        $allowedFields = [
            'custom_name', 'is_active', 'is_required',
            'min_choices', 'max_choices', 'allow_removal',
            'has_price_modifier', 'sort_order'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = Database::update('restaurant_cuisine_steps', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Réorganise l'ordre des étapes
     */
    public static function reorderSteps(int $restaurantCuisineTypeId, array $stepIds): bool {
        return Database::transaction(function() use ($restaurantCuisineTypeId, $stepIds) {
            foreach ($stepIds as $index => $stepId) {
                Database::update(
                    'restaurant_cuisine_steps',
                    ['sort_order' => $index],
                    ['id' => $stepId, 'restaurant_cuisine_type_id' => $restaurantCuisineTypeId]
                );
            }
            return true;
        });
    }

    // ========================================================================
    // OPTIONS DES ÉTAPES
    // ========================================================================

    /**
     * Récupère toutes les options d'une étape restaurant
     */
    public static function getStepOptions(int $restaurantCuisineStepId, bool $includeInactive = false): array {
        $sql = "SELECT * FROM restaurant_step_options
                WHERE restaurant_cuisine_step_id = ?";

        if (!$includeInactive) {
            $sql .= " AND is_active = 1 AND deleted_at IS NULL";
        }

        $sql .= " ORDER BY sort_order ASC";

        return Database::fetchAll($sql, [$restaurantCuisineStepId]);
    }

    /**
     * Crée une nouvelle option personnalisée
     */
    public static function createStepOption(int $restaurantCuisineStepId, array $data): int {
        return Database::insert('restaurant_step_options', [
            'restaurant_cuisine_step_id' => $restaurantCuisineStepId,
            'source_option_id' => null, // NULL = option 100% custom
            'name' => $data['name'],
            'price_modifier' => $data['price_modifier'] ?? 0.00,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ]);
    }

    /**
     * Met à jour une option
     */
    public static function updateStepOption(int $id, array $data): bool {
        $updateData = [];
        $allowedFields = ['name', 'price_modifier', 'sort_order', 'is_active'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $rowsAffected = Database::update('restaurant_step_options', $updateData, ['id' => $id]);
        return $rowsAffected > 0;
    }

    /**
     * Supprime une option (soft delete)
     */
    public static function deleteStepOption(int $id): bool {
        $rowsAffected = Database::update(
            'restaurant_step_options',
            ['deleted_at' => date('Y-m-d H:i:s')],
            ['id' => $id]
        );

        return $rowsAffected > 0;
    }

    /**
     * Réorganise l'ordre des options
     */
    public static function reorderOptions(int $restaurantCuisineStepId, array $optionIds): bool {
        return Database::transaction(function() use ($restaurantCuisineStepId, $optionIds) {
            foreach ($optionIds as $index => $optionId) {
                Database::update(
                    'restaurant_step_options',
                    ['sort_order' => $index],
                    ['id' => $optionId, 'restaurant_cuisine_step_id' => $restaurantCuisineStepId]
                );
            }
            return true;
        });
    }

    // ========================================================================
    // CONFIGURATION COMPLÈTE POUR CLIENT
    // ========================================================================

    /**
     * Récupère la configuration complète pour afficher le composeur au client
     * Retourne types > étapes > options
     */
    public static function getRestaurantMenuConfig(int $restaurantId): array {
        $types = self::getRestaurantTypes($restaurantId);

        foreach ($types as &$type) {
            $steps = self::getRestaurantSteps($type['id']);

            foreach ($steps as &$step) {
                // Utiliser custom_name si défini, sinon le template_name
                $step['display_name'] = $step['custom_name'] ?? $step['template_name'];
                $step['options'] = self::getStepOptions($step['id']);
            }

            $type['steps'] = $steps;
        }

        return $types;
    }

    /**
     * Récupère la configuration pour un type spécifique
     */
    public static function getTypeConfig(int $restaurantId, string $cuisineTypeSlug): ?array {
        $type = Database::fetchOne(
            "SELECT rct.*, ct.name, ct.slug, ct.icon_slug, ct.description
             FROM restaurant_cuisine_types rct
             JOIN cuisine_types ct ON rct.cuisine_type_id = ct.id
             WHERE rct.restaurant_id = ? AND ct.slug = ?
             AND rct.is_active = 1 AND rct.deleted_at IS NULL",
            [$restaurantId, $cuisineTypeSlug]
        );

        if (!$type) {
            return null;
        }

        $steps = self::getRestaurantSteps($type['id']);

        foreach ($steps as &$step) {
            $step['display_name'] = $step['custom_name'] ?? $step['template_name'];
            $step['options'] = self::getStepOptions($step['id']);
        }

        $type['steps'] = $steps;

        return $type;
    }

    // ========================================================================
    // STATISTIQUES
    // ========================================================================

    /**
     * Statistiques d'utilisation des types de cuisine
     */
    public static function getStats(int $restaurantId): array {
        return Database::fetchOne(
            "SELECT
                COUNT(*) as total_types,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_types,
                SUM(CASE WHEN is_active = 0 OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) as inactive_types
             FROM restaurant_cuisine_types
             WHERE restaurant_id = ?",
            [$restaurantId]
        ) ?? [
            'total_types' => 0,
            'active_types' => 0,
            'inactive_types' => 0
        ];
    }
}
