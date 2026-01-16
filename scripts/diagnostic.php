#!/usr/bin/env php
<?php
/**
 * Script de diagnostic générique multi-instance
 *
 * Usage:
 *   php scripts/diagnostic.php [instance-name]
 *   php scripts/diagnostic.php atelier-pizza
 *   php scripts/diagnostic.php marvelous
 *
 * Si aucune instance n'est spécifiée, diagnostic de toutes les instances
 */

require_once __DIR__ . '/../snackup/backend/InstanceManager.php';

function printSection($title) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "  $title\n";
    echo str_repeat('=', 70) . "\n\n";
}

function printSuccess($message) {
    echo "✓ $message\n";
}

function printError($message) {
    echo "✗ $message\n";
}

function printInfo($message) {
    echo "ℹ $message\n";
}

function diagnosticInstance($instanceName) {
    printSection("DIAGNOSTIC : " . strtoupper($instanceName));

    try {
        // Simuler le domaine pour cette instance
        $instanceData = InstanceManager::getInstanceData($instanceName);
        if (!$instanceData) {
            printError("Instance '$instanceName' introuvable dans instances.json");
            return false;
        }

        if (!empty($instanceData['domains'])) {
            $_SERVER['HTTP_HOST'] = $instanceData['domains'][0];
        }

        InstanceManager::reset();

        // 1. Configuration
        echo "1. Configuration\n";
        echo "   " . str_repeat('-', 50) . "\n";

        try {
            $config = InstanceManager::loadConfig();
            printSuccess("Configuration chargée");

            $restaurantId = InstanceManager::getRestaurantId();
            $instanceId = InstanceManager::getInstanceId();
            $currency = InstanceManager::getCurrency();

            echo "   - Restaurant ID: $restaurantId\n";
            echo "   - Instance ID: $instanceId\n";
            echo "   - Devise: $currency\n";
            echo "   - Domaines: " . implode(', ', $instanceData['domains']) . "\n";

        } catch (Exception $e) {
            printError("Erreur de configuration: " . $e->getMessage());
            return false;
        }

        // 2. Base de données
        echo "\n2. Base de données\n";
        echo "   " . str_repeat('-', 50) . "\n";

        try {
            require_once __DIR__ . '/../snackup/backend/Database.php';
            $dbConfig = InstanceManager::getDatabaseConfig();

            echo "   - Host: " . $dbConfig['host'] . "\n";
            echo "   - Database: " . $dbConfig['name'] . "\n";
            echo "   - User: " . $dbConfig['user'] . "\n";

            Database::init($dbConfig);
            $pdo = Database::getInstance();

            printSuccess("Connexion à la base de données OK");

            // Vérifier les tables essentielles
            $tables = ['restaurants', 'menu_categories', 'menu_items', 'supplements', 'orders'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    // Compter les enregistrements
                    $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                    printSuccess("Table '$table' existe ($count enregistrements)");
                } else {
                    printError("Table '$table' manquante");
                }
            }

        } catch (Exception $e) {
            printError("Erreur base de données: " . $e->getMessage());
            return false;
        }

        // 3. Restaurant
        echo "\n3. Restaurant\n";
        echo "   " . str_repeat('-', 50) . "\n";

        try {
            require_once __DIR__ . '/../snackup/backend/repositories/RestaurantRepository.php';

            $restaurant = RestaurantRepository::getById($restaurantId);
            if ($restaurant) {
                printSuccess("Restaurant trouvé: " . $restaurant['name']);
                echo "   - Slug: " . $restaurant['slug'] . "\n";
                echo "   - Téléphone: " . $restaurant['phone'] . "\n";
                echo "   - Adresse: " . $restaurant['address'] . "\n";
            } else {
                printError("Restaurant ID $restaurantId introuvable en base");
                return false;
            }

            $settings = RestaurantRepository::getSettings($restaurantId);
            if ($settings) {
                printSuccess("Paramètres restaurant chargés");
                echo "   - Halal: " . ($settings['is_halal'] ?? 'Non défini') . "\n";
                echo "   - WhatsApp: " . ($settings['whatsapp_orders_number'] ?? 'Non configuré') . "\n";
            }

        } catch (Exception $e) {
            printError("Erreur restaurant: " . $e->getMessage());
        }

        // 4. Menu
        echo "\n4. Menu\n";
        echo "   " . str_repeat('-', 50) . "\n";

        try {
            require_once __DIR__ . '/../snackup/backend/repositories/MenuRepository.php';
            MenuRepository::$restaurantId = $restaurantId;

            $categories = MenuRepository::getAllCategories();
            if (!empty($categories)) {
                printSuccess(count($categories) . " catégories trouvées");

                $totalItems = 0;
                foreach ($categories as $category) {
                    $itemCount = count($category['items'] ?? []);
                    $totalItems += $itemCount;
                    echo "   - " . $category['name'] . " ($itemCount articles)\n";
                }

                printSuccess("Total: $totalItems articles");
            } else {
                printError("Aucune catégorie trouvée");
            }

            $supplements = MenuRepository::getAllSupplements();
            if (!empty($supplements)) {
                printSuccess(count($supplements) . " suppléments trouvés");
            } else {
                printInfo("Aucun supplément configuré");
            }

        } catch (Exception $e) {
            printError("Erreur menu: " . $e->getMessage());
        }

        // 5. APIs
        echo "\n5. APIs Publiques\n";
        echo "   " . str_repeat('-', 50) . "\n";

        $baseUrl = 'http://localhost';
        $domain = $instanceData['domains'][0] ?? 'localhost';

        // Test restaurant.php
        $cmd = "curl -s -H 'Host: $domain' $baseUrl/config/restaurant.php 2>&1";
        $output = shell_exec($cmd);
        $data = json_decode($output, true);

        if ($data && !isset($data['error'])) {
            printSuccess("API restaurant.php fonctionne");
            echo "   - Nom: " . ($data['name'] ?? 'N/A') . "\n";
            echo "   - Devise: " . ($data['_jsConfig']['currency'] ?? 'N/A') . "\n";
        } else {
            printError("API restaurant.php en erreur: " . ($data['error'] ?? 'Réponse invalide'));
        }

        // Test menu.php
        $cmd = "curl -s -H 'Host: $domain' $baseUrl/config/menu.php 2>&1";
        $output = shell_exec($cmd);
        $data = json_decode($output, true);

        if ($data && !isset($data['error'])) {
            printSuccess("API menu.php fonctionne");
            $catCount = count($data['menu']['categories'] ?? []);
            echo "   - Catégories: $catCount\n";
        } else {
            printError("API menu.php en erreur: " . ($data['error'] ?? 'Réponse invalide'));
        }

        // 6. Fichiers de configuration
        echo "\n6. Fichiers\n";
        echo "   " . str_repeat('-', 50) . "\n";

        $configPath = __DIR__ . '/../' . $instanceData['config_path'];
        if (file_exists($configPath)) {
            printSuccess("backend-config.php existe");
        } else {
            printError("backend-config.php manquant: $configPath");
        }

        echo "\n";
        printSuccess("Diagnostic terminé pour $instanceName");
        return true;

    } catch (Exception $e) {
        printError("Erreur fatale: " . $e->getMessage());
        return false;
    }
}

// Main
echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║         DIAGNOSTIC MULTI-INSTANCE - ARCHITECTURE SCALABLE         ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";

// Déterminer quelle(s) instance(s) diagnostiquer
$targetInstance = $argv[1] ?? null;

try {
    InstanceManager::init();
    $allInstances = InstanceManager::getAllInstances();

    if ($targetInstance) {
        // Diagnostic d'une instance spécifique
        if (!isset($allInstances[$targetInstance])) {
            printError("Instance '$targetInstance' introuvable");
            echo "\nInstances disponibles: " . implode(', ', array_keys($allInstances)) . "\n\n";
            exit(1);
        }

        $success = diagnosticInstance($targetInstance);
        exit($success ? 0 : 1);

    } else {
        // Diagnostic de toutes les instances
        printInfo("Aucune instance spécifiée, diagnostic de toutes les instances\n");

        $results = [];
        foreach (array_keys($allInstances) as $instanceName) {
            $results[$instanceName] = diagnosticInstance($instanceName);
        }

        // Résumé
        printSection("RÉSUMÉ");
        foreach ($results as $instanceName => $success) {
            if ($success) {
                printSuccess("$instanceName: OK");
            } else {
                printError("$instanceName: ERREURS");
            }
        }

        $allSuccess = !in_array(false, $results, true);
        exit($allSuccess ? 0 : 1);
    }

} catch (Exception $e) {
    printError("Erreur d'initialisation: " . $e->getMessage());
    exit(1);
}
