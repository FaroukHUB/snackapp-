<?php
/**
 * Test de configuration - Sans output HTML avant les headers
 * Usage: php snackup/admin/test-config.php
 */

// Pas d'output avant bootstrap !
require_once __DIR__ . '/bootstrap.php';

// Maintenant on peut afficher
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Configuration</title>
</head>
<body>
<h1>✅ Test Configuration Snackup</h1>

<h2>1. Instance</h2>
<table border="1" cellpadding="5">
    <tr><td>Instance Name</td><td><?= INSTANCE_NAME ?></td></tr>
    <tr><td>App Name</td><td><?= APP_NAME ?></td></tr>
    <tr><td>Restaurant ID</td><td><?= RESTAURANT_ID ?></td></tr>
</table>

<h2>2. Database</h2>
<table border="1" cellpadding="5">
    <tr><td>Host</td><td><?= DB_HOST ?></td></tr>
    <tr><td>Database</td><td><?= DB_NAME ?></td></tr>
    <tr><td>User</td><td><?= DB_USER ?></td></tr>
    <tr><td>Charset</td><td><?= DB_CHARSET ?></td></tr>
</table>

<h2>3. Session</h2>
<?php if (session_status() === PHP_SESSION_ACTIVE): ?>
    <p style="color: green;">✅ Session active (ID: <?= session_id() ?>)</p>
<?php else: ?>
    <p style="color: red;">❌ Pas de session active</p>
<?php endif; ?>

<h2>4. Database Connection</h2>
<?php
try {
    $pdo = Database::getInstance();
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM restaurants WHERE id = " . RESTAURANT_ID);
    $result = $stmt->fetch();

    if ($result && $result['count'] > 0) {
        echo '<p style="color: green;">✅ Connexion DB réussie - Restaurant trouvé</p>';

        // Récupérer les infos du restaurant
        $restaurant = getCurrentRestaurant();
        if ($restaurant) {
            echo '<h3>Restaurant Info:</h3>';
            echo '<table border="1" cellpadding="5">';
            echo '<tr><td>ID</td><td>' . htmlspecialchars($restaurant['id']) . '</td></tr>';
            echo '<tr><td>Name</td><td>' . htmlspecialchars($restaurant['name']) . '</td></tr>';
            echo '<tr><td>Slug</td><td>' . htmlspecialchars($restaurant['slug']) . '</td></tr>';
            echo '</table>';
        }
    } else {
        echo '<p style="color: orange;">⚠️ Connexion DB réussie mais restaurant introuvable (ID: ' . RESTAURANT_ID . ')</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">❌ Erreur DB: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

<h2>✅ Test terminé</h2>
<p><a href="index.php">→ Aller à l'admin</a></p>
</body>
</html>
