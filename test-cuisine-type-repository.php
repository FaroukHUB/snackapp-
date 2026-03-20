<?php
/**
 * Script de test du CuisineTypeRepository
 * Teste l'activation d'un type de cuisine avec copie automatique
 */

// Charger la configuration de la base de données
$config = require __DIR__ . '/database/config.php';

// Initialiser la connexion Database
require_once __DIR__ . '/database/Database.php';
Database::init($config['database']);

// Charger le repository
require_once __DIR__ . '/database/repositories/CuisineTypeRepository.php';

echo "=== TEST CUISINE TYPE REPOSITORY ===\n\n";

try {
    // 1. Afficher tous les types disponibles
    echo "1. Types de cuisine disponibles:\n";
    echo str_repeat("-", 80) . "\n";
    $types = CuisineTypeRepository::getAllTypes();
    foreach ($types as $type) {
        echo sprintf("  [%2d] %-15s (%s)\n", $type['id'], $type['name'], $type['slug']);
    }
    echo "\n";

    // 2. Choisir un restaurant de test (ID 2 = restaurant demo)
    $restaurantId = 2;
    echo "2. Restaurant de test: ID = $restaurantId\n\n";

    // 3. Vérifier les types déjà activés
    echo "3. Types déjà activés pour ce restaurant:\n";
    echo str_repeat("-", 80) . "\n";
    $activeTypes = CuisineTypeRepository::getRestaurantTypes($restaurantId);
    if (empty($activeTypes)) {
        echo "  Aucun type activé\n";
    } else {
        foreach ($activeTypes as $type) {
            echo sprintf("  - %s (sort_order: %d)\n", $type['name'], $type['sort_order']);
        }
    }
    echo "\n";

    // 4. Activer le type "Tacos" (ID = 1)
    $cuisineTypeId = 1; // Tacos
    echo "4. Activation du type 'Tacos' (ID = $cuisineTypeId)...\n";
    echo str_repeat("-", 80) . "\n";

    if (CuisineTypeRepository::hasRestaurantType($restaurantId, $cuisineTypeId)) {
        echo "  ⚠️  Type déjà activé, on passe au suivant\n\n";

        // Essayer avec Burger
        $cuisineTypeId = 2;
        echo "  Essai avec 'Burger' (ID = $cuisineTypeId)...\n";
    }

    if (!CuisineTypeRepository::hasRestaurantType($restaurantId, $cuisineTypeId)) {
        $restaurantCuisineTypeId = CuisineTypeRepository::activateCuisineType($restaurantId, $cuisineTypeId, 10);
        echo "  ✅ Type activé ! restaurant_cuisine_type_id = $restaurantCuisineTypeId\n\n";

        // 5. Vérifier que les étapes ont été copiées
        echo "5. Étapes copiées:\n";
        echo str_repeat("-", 80) . "\n";
        $steps = CuisineTypeRepository::getRestaurantSteps($restaurantCuisineTypeId);
        echo sprintf("  Total: %d étapes\n\n", count($steps));

        foreach ($steps as $step) {
            $displayName = $step['custom_name'] ?? $step['template_name'];
            echo sprintf("  📋 %s (slug: %s)\n", $displayName, $step['slug']);
            echo sprintf("      - Obligatoire: %s\n", $step['is_required'] ? 'OUI' : 'NON');
            echo sprintf("      - Choix: min=%d, max=%d\n", $step['min_choices'], $step['max_choices']);
            echo sprintf("      - Prix: %s\n", $step['has_price_modifier'] ? 'OUI' : 'NON');

            // Afficher les options
            $options = CuisineTypeRepository::getStepOptions($step['id']);
            echo sprintf("      - Options: %d\n", count($options));
            foreach ($options as $option) {
                $price = $option['price_modifier'] > 0 ? sprintf("+%.2f€", $option['price_modifier']) : "inclus";
                echo sprintf("         • %s (%s)\n", $option['name'], $price);
            }
            echo "\n";
        }
    } else {
        echo "  ⚠️  Type déjà activé\n\n";
    }

    // 6. Afficher la configuration complète
    echo "6. Configuration complète du restaurant:\n";
    echo str_repeat("-", 80) . "\n";
    $config = CuisineTypeRepository::getRestaurantMenuConfig($restaurantId);
    echo sprintf("  Total types activés: %d\n\n", count($config));

    foreach ($config as $type) {
        echo sprintf("  🍽️  %s (%d étapes)\n", $type['name'], count($type['steps']));
    }
    echo "\n";

    // 7. Statistiques
    echo "7. Statistiques:\n";
    echo str_repeat("-", 80) . "\n";
    $stats = CuisineTypeRepository::getStats($restaurantId);
    echo sprintf("  Total types: %d\n", $stats['total_types']);
    echo sprintf("  Types actifs: %d\n", $stats['active_types']);
    echo sprintf("  Types inactifs: %d\n", $stats['inactive_types']);
    echo "\n";

    echo "=== TEST TERMINÉ AVEC SUCCÈS ===\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
