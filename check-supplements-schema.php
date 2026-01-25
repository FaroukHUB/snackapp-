<?php
require_once __DIR__ . '/snackup/admin/bootstrap.php';

$pdo = Database::getInstance();
$stmt = $pdo->query("DESCRIBE supplements");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Colonnes de la table supplements:\n";
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

// Vérifier aussi combien de suppléments existent
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM supplements WHERE restaurant_id = 3");
$stmt->execute();
$count = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nNombre de suppléments pour restaurant_id=3: " . $count['count'] . "\n";
?>
