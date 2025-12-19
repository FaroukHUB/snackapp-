<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Charger SEULEMENT 5 commandes max
$orders = loadData('orders.json') ?? [];
usort($orders, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$orders = array_slice($orders, 0, 5); // SEULEMENT 5 !

// Traiter changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $allOrders = loadData('orders.json') ?? [];
    foreach ($allOrders as &$order) {
        if ($order['id'] === $_POST['order_id']) {
            $order['status'] = $_POST['status'];
            break;
        }
    }
    saveData('orders.json', $allOrders);
    header('Location: index-minimal.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Minimal</title>
</head>
<body>
    <h1>Admin Panel - Version Minimal</h1>
    <p><a href="index-minimal.php">Actualiser</a> | <a href="logout.php">Déconnexion</a></p>

    <h2>Commandes (5 max)</h2>

    <?php if (empty($orders)): ?>
        <p>Aucune commande</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0">
            <tr>
                <th>ID</th>
                <th>Téléphone</th>
                <th>Total</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                    <td><?php echo number_format($order['total'], 2); ?>€</td>
                    <td><?php echo date('d/m H:i', strtotime($order['created_at'])); ?></td>
                    <td><strong><?php echo htmlspecialchars($order['status']); ?></strong></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
                            <button type="submit" name="status" value="received">Reçue</button>
                            <button type="submit" name="status" value="preparing">Prép.</button>
                            <button type="submit" name="status" value="ready">Prête</button>
                            <button type="submit" name="status" value="delivered">Livrée</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <hr>
    <p><small>Version HTML pur - Zéro CSS, Zéro JS - 5 commandes max</small></p>
</body>
</html>
