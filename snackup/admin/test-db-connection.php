<?php
/**
 * Script de test de connexion à la base de données
 * Usage: php test-db-connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 Test de connexion base de données...\n\n";

// Charger le gestionnaire d'instances
require_once __DIR__ . '/../backend/InstanceManager.php';

try {
    // Charger la configuration
    $instanceConfig = InstanceManager::loadConfig();
    $instanceName = InstanceManager::getCurrentInstance();

    echo "✓ Instance détectée: $instanceName\n";
    echo "✓ Configuration chargée\n\n";

    // Afficher les paramètres de connexion (sans le mot de passe)
    echo "Paramètres de connexion:\n";
    echo "  - Host: " . $instanceConfig['database']['host'] . "\n";
    echo "  - Database: " . ($instanceConfig['database']['dbname'] ?? $instanceConfig['database']['name']) . "\n";
    echo "  - User: " . ($instanceConfig['database']['user'] ?? $instanceConfig['database']['username']) . "\n";
    echo "  - Charset: " . $instanceConfig['database']['charset'] . "\n";
    echo "  - Password: " . (strlen($instanceConfig['database']['password']) > 0 ? '***' . substr($instanceConfig['database']['password'], -3) : 'VIDE!') . "\n\n";

    // Vérifier si le mot de passe est toujours le placeholder
    if ($instanceConfig['database']['password'] === 'CHANGE_ME') {
        echo "❌ ERREUR: Le mot de passe est toujours configuré sur 'CHANGE_ME'\n";
        echo "   Vous devez éditer le fichier de configuration de l'instance et mettre le vrai mot de passe.\n";
        exit(1);
    }

    // Tenter la connexion
    echo "🔌 Tentative de connexion...\n";

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $instanceConfig['database']['host'],
        $instanceConfig['database']['dbname'] ?? $instanceConfig['database']['name'],
        $instanceConfig['database']['charset']
    );

    $pdo = new PDO(
        $dsn,
        $instanceConfig['database']['user'] ?? $instanceConfig['database']['username'],
        $instanceConfig['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✅ CONNEXION RÉUSSIE!\n\n";

    // Vérifier que la table restaurants existe
    echo "🔍 Vérification des tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredTables = ['restaurants', 'categories', 'products', 'orders', 'customers'];
    $missingTables = [];

    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✓ Table '$table' trouvée\n";
        } else {
            echo "  ❌ Table '$table' MANQUANTE\n";
            $missingTables[] = $table;
        }
    }

    if (!empty($missingTables)) {
        echo "\n⚠️ ATTENTION: Tables manquantes. Vous devez importer le schéma de la base de données.\n";
    } else {
        echo "\n✅ Toutes les tables nécessaires sont présentes!\n";
    }

    // Vérifier le restaurant
    echo "\n🍽️  Vérification du restaurant...\n";
    $restaurantId = $instanceConfig['app']['restaurant_id'] ?? 1;
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurantId]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($restaurant) {
        echo "✅ Restaurant trouvé: " . $restaurant['name'] . " (ID: $restaurantId)\n";
    } else {
        echo "❌ Restaurant introuvable avec l'ID: $restaurantId\n";
    }

    echo "\n✅ TEST TERMINÉ AVEC SUCCÈS!\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
