<?php
require_once 'snackup/backend/Database.php';
$pdo = Database::getInstance();
$stmt = $pdo->query('SELECT id, name FROM categories WHERE restaurant_id = 3 LIMIT 10');
echo "Catégories pour restaurant_id=3:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['id'] . ' - ' . $row['name'] . "\n";
}
?>
