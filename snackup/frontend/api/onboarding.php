<?php
/**
 * API Onboarding - Public (no admin auth required)
 * Permet de charger les types de cuisine et d'enregistrer les sélections
 */

// Charger le bootstrap depuis l'admin
require_once __DIR__ . '/../../admin/bootstrap.php';
require_once __DIR__ . '/../../../database/repositories/CuisineTypeRepository.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$restaurantId = SNACK_RESTAURANT_ID;

try {
    // GET - Récupérer les types de cuisine disponibles
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get_cuisine_types') {
            // Récupérer tous les types de cuisine avec leurs étapes
            $types = CuisineTypeRepository::getAllTypes();

            // Pour chaque type, récupérer les étapes par défaut (templates)
            foreach ($types as &$type) {
                $templateSteps = CuisineTypeRepository::getTemplateSteps($type['id']);
                $type['steps_count'] = count($templateSteps);

                // Calculer le nombre d'options total
                $optionsCount = 0;
                foreach ($templateSteps as $step) {
                    $options = CuisineTypeRepository::getTemplateOptions($step['id']);
                    $optionsCount += count($options);
                }
                $type['options_count'] = $optionsCount;
            }

            echo json_encode([
                'success' => true,
                'types' => $types
            ]);
            exit;
        }

        if ($action === 'get_selected_types') {
            // Récupérer les types déjà activés pour ce restaurant
            $activeTypes = CuisineTypeRepository::getRestaurantTypes($restaurantId, false);
            $selectedIds = array_map(function($type) {
                return $type['cuisine_type_id'];
            }, $activeTypes);

            echo json_encode([
                'success' => true,
                'selected' => $selectedIds
            ]);
            exit;
        }
    }

    // POST - Enregistrer les sélections
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'save_selections') {
            $selectedCuisineIds = $input['cuisine_ids'] ?? [];
            $onboardingConfig = $input['onboarding_config'] ?? [];

            if (empty($selectedCuisineIds)) {
                echo json_encode(['success' => false, 'error' => 'Aucun type sélectionné']);
                exit;
            }

            // Obtenir les types déjà activés
            $existingTypes = CuisineTypeRepository::getRestaurantTypes($restaurantId, false);
            $existingCuisineIds = array_map(function($type) {
                return $type['cuisine_type_id'];
            }, $existingTypes);

            $activated = [];
            $alreadyActive = [];

            // Activer chaque type sélectionné
            foreach ($selectedCuisineIds as $cuisineTypeId) {
                $cuisineTypeId = (int) $cuisineTypeId;

                // Vérifier si déjà activé
                if (in_array($cuisineTypeId, $existingCuisineIds)) {
                    $alreadyActive[] = $cuisineTypeId;
                    continue;
                }

                // Activer le type (avec copie automatique des étapes et options)
                try {
                    $restaurantCuisineTypeId = CuisineTypeRepository::activateCuisineType($restaurantId, $cuisineTypeId);
                    $activated[] = [
                        'cuisine_type_id' => $cuisineTypeId,
                        'restaurant_cuisine_type_id' => $restaurantCuisineTypeId
                    ];
                } catch (Exception $e) {
                    error_log("Erreur activation type $cuisineTypeId: " . $e->getMessage());
                }
            }

            // Enregistrer la config de l'onboarding dans les settings du restaurant
            // (optionnel - vous pouvez stocker deliveryMode, hours, etc. dans une table de settings)

            echo json_encode([
                'success' => true,
                'activated' => $activated,
                'already_active' => $alreadyActive,
                'total_activated' => count($activated),
                'message' => count($activated) . ' type(s) de cuisine activé(s) avec succès'
            ]);
            exit;
        }
    }

    // Action non reconnue
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action non reconnue']);

} catch (Exception $e) {
    error_log('Erreur API onboarding: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
