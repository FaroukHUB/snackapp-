<?php
/**
 * Script de diagnostic pour déboguer les formules
 * À exécuter via le navigateur
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/snackup/backend/InstanceManager.php';
require_once __DIR__ . '/snackup/backend/Database.php';

try {
    // Charger la config de l'instance
    $instanceConfig = InstanceManager::loadConfig();
    $instanceName = InstanceManager::getCurrentInstance();
    $restaurantId = InstanceManager::getRestaurantId();

    // Initialiser la DB
    Database::init(InstanceManager::getDatabaseConfig());
    $pdo = Database::getInstance();

    // 1. Vérifier la structure de la table
    $stmt = $pdo->query("SHOW CREATE TABLE formules");
    $createTable = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Extraire le type de la colonne id
    $stmt = $pdo->query("DESCRIBE formules");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $idColumn = null;
    foreach ($columns as $col) {
        if ($col['Field'] === 'id') {
            $idColumn = $col;
            break;
        }
    }

    // 3. Compter les formules
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM formules WHERE restaurant_id = ?");
    $stmt->execute([$restaurantId]);
    $totalCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM formules WHERE restaurant_id = ? AND status = 'available'");
    $stmt->execute([$restaurantId]);
    $availableCount = $stmt->fetchColumn();

    // 4. Lister les formules existantes
    $stmt = $pdo->prepare("
        SELECT id, name, status, sort_order, created_at
        FROM formules
        WHERE restaurant_id = ?
        ORDER BY sort_order, id
        LIMIT 20
    ");
    $stmt->execute([$restaurantId]);
    $formules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Récupérer les infos de connexion DB (sans password)
    $dbConfig = InstanceManager::getDatabaseConfig();

    // Résultat
    echo json_encode([
        'success' => true,
        'instance' => [
            'name' => $instanceName,
            'restaurant_id' => $restaurantId,
        ],
        'database' => [
            'host' => $dbConfig['host'] ?? 'unknown',
            'database' => $dbConfig['database'] ?? 'unknown',
            'user' => $dbConfig['username'] ?? 'unknown',
        ],
        'table_structure' => [
            'id_column' => $idColumn,
            'create_statement' => $createTable['Create Table'] ?? null,
        ],
        'counts' => [
            'total' => (int)$totalCount,
            'available' => (int)$availableCount,
        ],
        'formules' => $formules,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ], JSON_PRETTY_PRINT);
}
