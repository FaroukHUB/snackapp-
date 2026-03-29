<?php
/**
 * API Cuisine Types
 * Gestion des types de cuisine pour l'admin
 */

require_once __DIR__ . '/../bootstrap.php';
requireAdmin();

require_once __DIR__ . '/../../../database/repositories/CuisineTypeRepository.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$restaurantId = SNACK_RESTAURANT_ID;

try {
    // GET - Récupérer les types
    if ($method === 'GET') {
        // Config complète d'un type (pour page de configuration)
        if (isset($_GET['type_id'])) {
            $typeId = (int) $_GET['type_id'];
            $steps = CuisineTypeRepository::getRestaurantSteps($typeId, true);
            foreach ($steps as &$step) {
                $step['options'] = CuisineTypeRepository::getStepOptions($step['id'], true);
            }
            echo json_encode(['success' => true, 'steps' => $steps]);
            exit;
        }

        $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] == '1';

        if ($activeOnly) {
            // Types activés pour le restaurant
            $types = CuisineTypeRepository::getRestaurantTypes($restaurantId, false);

            // Compter les étapes et options pour chaque type
            foreach ($types as &$type) {
                $steps = CuisineTypeRepository::getRestaurantSteps($type['id'], false);
                $type['steps'] = $steps;
                $type['steps_count'] = count($steps);

                $optionsCount = 0;
                foreach ($steps as $step) {
                    $options = CuisineTypeRepository::getStepOptions($step['id'], false);
                    $optionsCount += count($options);
                }
                $type['options_count'] = $optionsCount;
            }

            echo json_encode([
                'success' => true,
                'types' => $types
            ]);
        } else {
            // Tous les types disponibles
            $types = CuisineTypeRepository::getAllTypes();

            echo json_encode([
                'success' => true,
                'types' => $types
            ]);
        }
        exit;
    }

    // POST - Actions (activer, désactiver)
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Vérifier CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!$csrfToken) {
            $csrfToken = $input['csrf_token'] ?? null;
        }
        if (!$csrfToken || !validateCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
            exit;
        }

        $action = $input['action'] ?? '';

        // Activer un type
        if ($action === 'activate') {
            $cuisineTypeId = (int) ($input['cuisine_type_id'] ?? 0);

            if (!$cuisineTypeId) {
                echo json_encode(['success' => false, 'error' => 'ID type manquant']);
                exit;
            }

            // Vérifier que le type n'est pas déjà activé
            if (CuisineTypeRepository::hasRestaurantType($restaurantId, $cuisineTypeId)) {
                echo json_encode(['success' => false, 'error' => 'Ce type est déjà activé']);
                exit;
            }

            // Activer le type (avec copie automatique des étapes et options)
            $restaurantCuisineTypeId = CuisineTypeRepository::activateCuisineType($restaurantId, $cuisineTypeId);

            echo json_encode([
                'success' => true,
                'restaurant_cuisine_type_id' => $restaurantCuisineTypeId,
                'message' => 'Type activé avec succès'
            ]);
            exit;
        }

        // Désactiver un type
        if ($action === 'deactivate') {
            $restaurantCuisineTypeId = (int) ($input['restaurant_cuisine_type_id'] ?? 0);

            if (!$restaurantCuisineTypeId) {
                echo json_encode(['success' => false, 'error' => 'ID manquant']);
                exit;
            }

            // Récupérer le type pour obtenir le cuisine_type_id
            $types = CuisineTypeRepository::getRestaurantTypes($restaurantId, true);
            $type = array_filter($types, function($t) use ($restaurantCuisineTypeId) {
                return $t['id'] == $restaurantCuisineTypeId;
            });

            if (empty($type)) {
                echo json_encode(['success' => false, 'error' => 'Type non trouvé']);
                exit;
            }

            $type = array_values($type)[0];
            $cuisineTypeId = $type['cuisine_type_id'];

            // Désactiver
            CuisineTypeRepository::deactivateCuisineType($restaurantId, $cuisineTypeId);

            echo json_encode([
                'success' => true,
                'message' => 'Type désactivé avec succès'
            ]);
            exit;
        }

        // Mettre à jour une étape
        if ($action === 'update_step') {
            $stepId = (int) ($input['step_id'] ?? 0);
            if (!$stepId) {
                echo json_encode(['success' => false, 'error' => 'ID étape manquant']);
                exit;
            }
            $data = [];
            foreach (['custom_name', 'is_active', 'is_required', 'min_choices', 'max_choices', 'allow_removal', 'has_price_modifier'] as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
            CuisineTypeRepository::updateRestaurantStep($stepId, $data);
            echo json_encode(['success' => true, 'message' => 'Étape mise à jour']);
            exit;
        }

        // Créer une option
        if ($action === 'create_option') {
            $stepId = (int) ($input['step_id'] ?? 0);
            $name = trim($input['name'] ?? '');
            if (!$stepId || !$name) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
                exit;
            }
            $optionId = CuisineTypeRepository::createStepOption($stepId, [
                'name' => $name,
                'price_modifier' => (float) ($input['price_modifier'] ?? 0),
                'sort_order' => (int) ($input['sort_order'] ?? 99)
            ]);
            echo json_encode(['success' => true, 'option_id' => $optionId, 'message' => 'Option créée']);
            exit;
        }

        // Mettre à jour une option
        if ($action === 'update_option') {
            $optionId = (int) ($input['option_id'] ?? 0);
            if (!$optionId) {
                echo json_encode(['success' => false, 'error' => 'ID option manquant']);
                exit;
            }
            $data = [];
            foreach (['name', 'price_modifier', 'sort_order', 'is_active'] as $field) {
                if (array_key_exists($field, $input)) {
                    $data[$field] = $input[$field];
                }
            }
            CuisineTypeRepository::updateStepOption($optionId, $data);
            echo json_encode(['success' => true, 'message' => 'Option mise à jour']);
            exit;
        }

        // Supprimer une option
        if ($action === 'delete_option') {
            $optionId = (int) ($input['option_id'] ?? 0);
            if (!$optionId) {
                echo json_encode(['success' => false, 'error' => 'ID option manquant']);
                exit;
            }
            CuisineTypeRepository::deleteStepOption($optionId);
            echo json_encode(['success' => true, 'message' => 'Option supprimée']);
            exit;
        }

        // Réorganiser les étapes
        if ($action === 'reorder_steps') {
            $typeId = (int) ($input['type_id'] ?? 0);
            $stepIds = $input['step_ids'] ?? [];
            if (!$typeId || empty($stepIds)) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
                exit;
            }
            CuisineTypeRepository::reorderSteps($typeId, $stepIds);
            echo json_encode(['success' => true, 'message' => 'Ordre mis à jour']);
            exit;
        }

        // Réorganiser les options
        if ($action === 'reorder_options') {
            $stepId = (int) ($input['step_id'] ?? 0);
            $optionIds = $input['option_ids'] ?? [];
            if (!$stepId || empty($optionIds)) {
                echo json_encode(['success' => false, 'error' => 'Données manquantes']);
                exit;
            }
            CuisineTypeRepository::reorderOptions($stepId, $optionIds);
            echo json_encode(['success' => true, 'message' => 'Ordre mis à jour']);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
        exit;
    }

    // Méthode non supportée
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
