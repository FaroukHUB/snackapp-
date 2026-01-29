<?php
/**
 * Debug Script: Vérifier la structure de la table orders
 * SUPPRIMER CE FICHIER APRÈS UTILISATION!
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG: Table Orders ===\n\n";

// Charger la config
require_once __DIR__ . '/config.php';

echo "1. Configuration chargée\n";
echo "   - Instance: " . INSTANCE_NAME . "\n";
echo "   - Database: " . DB_NAME . "\n";
echo "   - Restaurant ID: " . RESTAURANT_ID . "\n\n";

// Test connexion
try {
    $db = getDatabase();
    echo "2. Connexion DB: OK\n\n";
} catch (Exception $e) {
    echo "2. Connexion DB: ERREUR - " . $e->getMessage() . "\n";
    exit;
}

// Liste des colonnes de la table orders
echo "3. Structure de la table 'orders':\n";
echo str_repeat('-', 60) . "\n";

try {
    $stmt = $db->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $columnNames = [];
    foreach ($columns as $col) {
        $columnNames[] = $col['Field'];
        printf("   %-25s %-20s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ERREUR: " . $e->getMessage() . "\n\n";
    exit;
}

// Colonnes requises par OrderRepository::createOrder
$requiredColumns = [
    'restaurant_id',
    'customer_id',
    'order_number',
    'customer_name',
    'customer_phone',
    'subtotal',
    'total',
    'delivery_fee',
    'status',
    'notes',
    'pickup_time',
    'preorder_date',
    'preorder_time',
    'mode_notes',
    'delivery_address',
    'delivery_instructions',
    'loyalty_reward_id',
    'loyalty_points_used',
    'loyalty_redeemed'
];

echo "4. Vérification des colonnes requises:\n";
echo str_repeat('-', 60) . "\n";

$missing = [];
foreach ($requiredColumns as $col) {
    $exists = in_array($col, $columnNames);
    $status = $exists ? "✓" : "✗ MANQUANTE";
    echo "   $col: $status\n";

    if (!$exists) {
        $missing[] = $col;
    }
}

echo "\n";

if (empty($missing)) {
    echo "5. Résultat: TOUTES LES COLONNES SONT PRÉSENTES ✓\n\n";
} else {
    echo "5. Résultat: " . count($missing) . " COLONNE(S) MANQUANTE(S) ✗\n";
    echo "   Colonnes manquantes: " . implode(', ', $missing) . "\n\n";

    echo "6. SQL pour ajouter les colonnes manquantes:\n";
    echo str_repeat('-', 60) . "\n";

    $sqlTypes = [
        'delivery_fee' => 'DECIMAL(10,2) DEFAULT 0.00',
        'preorder_date' => 'DATE DEFAULT NULL',
        'preorder_time' => 'TIME DEFAULT NULL',
        'mode_notes' => 'VARCHAR(255) DEFAULT NULL',
        'delivery_address' => 'TEXT DEFAULT NULL',
        'delivery_instructions' => 'TEXT DEFAULT NULL'
    ];

    foreach ($missing as $col) {
        $type = $sqlTypes[$col] ?? 'VARCHAR(255) DEFAULT NULL';
        echo "ALTER TABLE orders ADD COLUMN $col $type;\n";
    }
    echo "\n";
}

// Test d'insertion simulé
echo "7. Test requête INSERT (simulation):\n";
echo str_repeat('-', 60) . "\n";

$testData = [
    'restaurant_id' => RESTAURANT_ID,
    'customer_id' => null,
    'order_number' => 'TEST-' . date('YmdHis'),
    'customer_name' => 'Test Client',
    'customer_phone' => '0600000000',
    'subtotal' => 10.00,
    'total' => 10.00,
    'delivery_fee' => 0,
    'status' => 'pending',
    'notes' => null,
    'pickup_time' => null,
    'preorder_date' => null,
    'preorder_time' => null,
    'mode_notes' => null,
    'delivery_address' => 'Test Address',
    'delivery_instructions' => null,
    'loyalty_reward_id' => null,
    'loyalty_points_used' => 0,
    'loyalty_redeemed' => 0
];

$columns = implode(', ', array_keys($testData));
$placeholders = implode(', ', array_fill(0, count($testData), '?'));
$sql = "INSERT INTO orders ($columns) VALUES ($placeholders)";

echo "   SQL généré:\n";
echo "   $sql\n\n";

// Vérifier si toutes les colonnes de la requête existent
$missingInQuery = array_diff(array_keys($testData), $columnNames);
if (!empty($missingInQuery)) {
    echo "   ERREUR: Ces colonnes dans la requête n'existent pas dans la table:\n";
    echo "   " . implode(', ', $missingInQuery) . "\n\n";
} else {
    echo "   Toutes les colonnes de la requête existent dans la table. ✓\n\n";
}

echo "=== FIN DEBUG ===\n";
echo "\n⚠️  SUPPRIMER CE FICHIER APRÈS UTILISATION!\n";
