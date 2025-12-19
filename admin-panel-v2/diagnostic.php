<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$orders = loadData('orders.json') ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Diagnostic</title>
</head>
<body>
    <h1>Diagnostic Admin Panel</h1>

    <h2>1. Informations serveur</h2>
    <ul>
        <li>PHP Version: <?php echo phpversion(); ?></li>
        <li>Memory Limit: <?php echo ini_get('memory_limit'); ?></li>
        <li>Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s</li>
    </ul>

    <h2>2. Fichier orders.json</h2>
    <ul>
        <li>Nombre total de commandes: <strong><?php echo count($orders); ?></strong></li>
        <li>Taille du fichier: <strong><?php echo round(filesize(DATA_DIR . 'orders.json') / 1024, 2); ?> KB</strong></li>
    </ul>

    <h2>3. Échantillon de données</h2>
    <p>Première commande:</p>
    <pre><?php
    if (!empty($orders)) {
        print_r(array_slice($orders, 0, 1));
    } else {
        echo "Aucune commande";
    }
    ?></pre>

    <h2>4. Test de rendu</h2>
    <p>Si cette page s'affiche sans crasher, le problème vient probablement de l'affichage de multiples commandes.</p>

    <hr>
    <p><a href="index.php" style="display:inline-block;padding:10px 20px;background:#c58a3a;color:white;border-radius:8px;text-decoration:none;">← Retour à l'admin</a></p>
    <p style="margin-top:10px;"><a href="products-manager.php" style="display:inline-block;padding:10px 20px;background:#374151;color:white;border-radius:8px;text-decoration:none;">Gestion des produits</a></p>
</body>
</html>
