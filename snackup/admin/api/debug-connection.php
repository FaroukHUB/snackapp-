<?php
/**
 * DEBUG: Trace exacte de la connexion Database pour orders.php
 * SUPPRIMER APRES UTILISATION!
 */

header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG CONNEXION DATABASE ===\n\n";

// 1. Charger exactement comme orders.php
echo "1. Chargement de bootstrap.php...\n";
require_once __DIR__ . '/../bootstrap.php';
echo "   OK - Bootstrap chargé\n\n";

// 2. Afficher les constantes
echo "2. Constantes définies:\n";
echo "   - INSTANCE_NAME: " . (defined('INSTANCE_NAME') ? INSTANCE_NAME : 'NON DEFINI') . "\n";
echo "   - DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NON DEFINI') . "\n";
echo "   - DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NON DEFINI') . "\n";
echo "   - RESTAURANT_ID: " . (defined('RESTAURANT_ID') ? RESTAURANT_ID : 'NON DEFINI') . "\n";
echo "   - SNACK_RESTAURANT_ID: " . (defined('SNACK_RESTAURANT_ID') ? SNACK_RESTAURANT_ID : 'NON DEFINI') . "\n";
echo "\n";

// 3. Tester la connexion via Database::getInstance()
echo "3. Test connexion via Database class:\n";
try {
    $pdo = Database::getInstance();
    echo "   Connexion: OK\n";

    // Quelle database est utilisée?
    $result = $pdo->query("SELECT DATABASE() as db")->fetch();
    echo "   Database active: " . ($result['db'] ?? 'NULL') . "\n";

    // Vérifier que c'est la bonne
    if ($result['db'] !== DB_NAME) {
        echo "   *** ERREUR: Mauvaise database! Attendu: " . DB_NAME . " ***\n";
    } else {
        echo "   Database correcte!\n";
    }
} catch (Exception $e) {
    echo "   ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Vérifier la structure de orders via Database class
echo "4. Structure table 'orders' via Database class:\n";
try {
    $columns = Database::fetchAll("DESCRIBE orders");
    $columnNames = array_column($columns, 'Field');

    $required = ['delivery_address', 'delivery_fee', 'preorder_date', 'preorder_time', 'mode_notes'];
    foreach ($required as $col) {
        $status = in_array($col, $columnNames) ? "OK" : "MANQUANTE!";
        echo "   - $col: $status\n";
    }
} catch (Exception $e) {
    echo "   ERREUR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Tester un INSERT simulé
echo "5. Test INSERT simulé:\n";
try {
    $testData = [
        'restaurant_id' => SNACK_RESTAURANT_ID,
        'customer_id' => null,
        'order_number' => 'DEBUG-' . time(),
        'customer_name' => 'Test Debug',
        'customer_phone' => '0000000000',
        'subtotal' => 10.00,
        'total' => 10.00,
        'delivery_fee' => 0,
        'status' => 'pending',
        'notes' => 'Test debug',
        'pickup_time' => null,
        'preorder_date' => null,
        'preorder_time' => null,
        'mode_notes' => 'Test',
        'delivery_address' => '123 Test Street',
        'delivery_instructions' => null,
        'loyalty_reward_id' => null,
        'loyalty_points_used' => 0,
        'loyalty_redeemed' => 0
    ];

    $columns = implode(', ', array_keys($testData));
    $placeholders = implode(', ', array_fill(0, count($testData), '?'));
    $sql = "INSERT INTO orders ($columns) VALUES ($placeholders)";

    echo "   SQL: $sql\n\n";

    // Exécuter l'insert
    $stmt = Database::getInstance()->prepare($sql);
    $stmt->execute(array_values($testData));

    $insertedId = Database::getInstance()->lastInsertId();
    echo "   INSERT REUSSI! ID: $insertedId\n";

    // Supprimer la commande de test
    Database::getInstance()->exec("DELETE FROM orders WHERE id = $insertedId");
    echo "   Commande de test supprimée.\n";

} catch (PDOException $e) {
    echo "   ERREUR PDO: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "   ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
echo "\n!!! SUPPRIMER CE FICHIER APRES UTILISATION !!!\n";
