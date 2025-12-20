<?php
/**
 * Script de test pour le système de fidélité
 * Accéder via: http://localhost/test-loyalty.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bootstrap - utiliser le même que l'admin
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/repositories/CustomerRepository.php';
require_once __DIR__ . '/database/repositories/LoyaltyRepository.php';
require_once __DIR__ . '/database/repositories/RestaurantRepository.php';

// Charger la config depuis le fichier
$configFile = __DIR__ . '/database/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
    Database::init($config['database']);
} else {
    // Fallback MAMP
    Database::init([
        'host' => 'localhost',
        'dbname' => 'snackapp',
        'username' => 'root',
        'password' => 'root',
    ]);
}

define('SNACK_RESTAURANT_ID', 1);

$message = '';
$error = '';

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create_tables':
                // Créer les tables manquantes
                $pdo = Database::getInstance();

                // Ajouter colonnes à customers si besoin
                try {
                    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS loyalty_code VARCHAR(10) DEFAULT NULL");
                    $pdo->exec("ALTER TABLE customers ADD COLUMN IF NOT EXISTS loyalty_points INT UNSIGNED DEFAULT 0");
                } catch (Exception $e) {}

                // Ajouter colonnes à restaurant_settings si besoin
                try {
                    $pdo->exec("ALTER TABLE restaurant_settings ADD COLUMN IF NOT EXISTS loyalty_enabled TINYINT(1) DEFAULT 1");
                    $pdo->exec("ALTER TABLE restaurant_settings ADD COLUMN IF NOT EXISTS loyalty_points_per_euro INT UNSIGNED DEFAULT 1");
                } catch (Exception $e) {}

                // Créer loyalty_rewards
                $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_rewards (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    restaurant_id INT UNSIGNED NOT NULL,
                    name VARCHAR(100) NOT NULL,
                    description TEXT DEFAULT NULL,
                    points_required INT UNSIGNED NOT NULL,
                    reward_type ENUM('discount_percent', 'discount_amount', 'free_item') DEFAULT 'discount_percent',
                    reward_value DECIMAL(8,2) DEFAULT 0.00,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_restaurant (restaurant_id, is_active)
                )");

                // Créer loyalty_transactions
                $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_transactions (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT UNSIGNED NOT NULL,
                    restaurant_id INT UNSIGNED NOT NULL,
                    order_id INT UNSIGNED DEFAULT NULL,
                    points INT NOT NULL,
                    type ENUM('earn', 'redeem', 'bonus', 'adjustment') NOT NULL,
                    description VARCHAR(255) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_customer (customer_id),
                    INDEX idx_restaurant (restaurant_id)
                )");

                $message = "Tables créées avec succès!";
                break;

            case 'create_test_data':
                // Créer des récompenses test
                $rewards = [
                    ['name' => 'Boisson offerte', 'description' => 'Une boisson au choix', 'points_required' => 50, 'reward_type' => 'free_item'],
                    ['name' => '-10% sur commande', 'description' => 'Réduction de 10%', 'points_required' => 100, 'reward_type' => 'discount_percent', 'reward_value' => 10],
                    ['name' => '-5€ sur commande', 'description' => 'Réduction de 5 euros', 'points_required' => 150, 'reward_type' => 'discount_amount', 'reward_value' => 5],
                    ['name' => 'Dessert offert', 'description' => 'Un dessert au choix', 'points_required' => 200, 'reward_type' => 'free_item'],
                ];

                foreach ($rewards as $r) {
                    LoyaltyRepository::addReward(SNACK_RESTAURANT_ID, $r);
                }

                $message = "Récompenses test créées!";
                break;

            case 'create_test_customer':
                $phone = '0612345678';
                $name = 'Client Test';

                try {
                    $customerId = CustomerRepository::addCustomer(SNACK_RESTAURANT_ID, [
                        'name' => $name,
                        'phone' => $phone
                    ]);

                    // Ajouter des points
                    LoyaltyRepository::addPoints($customerId, SNACK_RESTAURANT_ID, 120, null, 'Points test');

                    $customer = CustomerRepository::getById($customerId);
                    $message = "Client créé: {$customer['name']} - Code: {$customer['loyalty_code']} - Points: {$customer['loyalty_points']}";
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                break;

            case 'add_points':
                $customerId = (int) $_POST['customer_id'];
                $points = (int) $_POST['points'];
                LoyaltyRepository::addPoints($customerId, SNACK_RESTAURANT_ID, $points, null, 'Ajout test');
                $customer = CustomerRepository::getById($customerId);
                $message = "Points ajoutés! Nouveau solde: {$customer['loyalty_points']}";
                break;

            case 'generate_codes':
                // Générer des codes pour clients existants
                $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
                $count = 0;
                foreach ($customers as $c) {
                    if (empty($c['loyalty_code'])) {
                        $code = CustomerRepository::generateLoyaltyCode();
                        Database::update('customers', ['loyalty_code' => $code], ['id' => $c['id']]);
                        $count++;
                    }
                }
                $message = "$count codes fidélité générés!";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Données pour affichage
try {
    $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    $rewards = LoyaltyRepository::getAllRewards(SNACK_RESTAURANT_ID);
    $config = LoyaltyRepository::getConfig(SNACK_RESTAURANT_ID);
} catch (Exception $e) {
    $customers = [];
    $rewards = [];
    $config = ['enabled' => false, 'points_per_euro' => 1];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Système Fidélité</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #1a1a2e; color: #eee; padding: 20px; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #f59e0b; }
        h2 { color: #10b981; margin-top: 30px; }
        .card { background: #2a2a4e; border-radius: 12px; padding: 20px; margin: 15px 0; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; margin: 5px; }
        .btn-primary { background: #f59e0b; color: #000; }
        .btn-green { background: #10b981; color: #fff; }
        .btn-blue { background: #3b82f6; color: #fff; }
        .success { background: #10b981; color: #fff; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #ef4444; color: #fff; padding: 15px; border-radius: 8px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #1e293b; color: #f59e0b; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; }
        .badge-gold { background: #f59e0b; color: #000; }
        .badge-green { background: #10b981; color: #fff; }
        input[type="number"] { padding: 8px; border-radius: 6px; border: 1px solid #444; background: #1e293b; color: #fff; width: 80px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        a { color: #3b82f6; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test Système de Fidélité</h1>

        <?php if ($message): ?>
            <div class="success">✓ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">✗ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>1. Configuration Base de Données</h2>
            <div class="grid">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="create_tables">
                    <button type="submit" class="btn btn-primary">Créer les tables manquantes</button>
                </form>

                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="create_test_data">
                    <button type="submit" class="btn btn-green">Créer récompenses test</button>
                </form>

                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="generate_codes">
                    <button type="submit" class="btn btn-blue">Générer codes fidélité manquants</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h2>2. Créer un Client Test</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_test_customer">
                <button type="submit" class="btn btn-green">Créer "Client Test" (0612345678)</button>
            </form>
        </div>

        <div class="card">
            <h2>3. Configuration Fidélité</h2>
            <p><strong>Fidélité activée:</strong> <?= $config['enabled'] ? '✓ Oui' : '✗ Non' ?></p>
            <p><strong>Points par euro:</strong> <?= $config['points_per_euro'] ?></p>
        </div>

        <div class="card">
            <h2>4. Récompenses (<?= count($rewards) ?>)</h2>
            <?php if (empty($rewards)): ?>
                <p style="color: #666;">Aucune récompense. Cliquez sur "Créer récompenses test".</p>
            <?php else: ?>
                <table>
                    <tr><th>Nom</th><th>Points requis</th><th>Type</th><th>Valeur</th></tr>
                    <?php foreach ($rewards as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><span class="badge badge-gold"><?= $r['points_required'] ?> pts</span></td>
                        <td><?= $r['reward_type'] ?></td>
                        <td><?= $r['reward_value'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>5. Clients (<?= count($customers) ?>)</h2>
            <?php if (empty($customers)): ?>
                <p style="color: #666;">Aucun client.</p>
            <?php else: ?>
                <table>
                    <tr><th>Code</th><th>Nom</th><th>Téléphone</th><th>Points</th><th>Commandes</th><th>Actions</th></tr>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><span class="badge badge-gold"><?= htmlspecialchars($c['loyalty_code'] ?? 'N/A') ?></span></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><strong style="color: #f59e0b;"><?= $c['loyalty_points'] ?? 0 ?></strong></td>
                        <td><?= $c['orders_count'] ?></td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="add_points">
                                <input type="hidden" name="customer_id" value="<?= $c['id'] ?>">
                                <input type="number" name="points" value="50" min="1">
                                <button type="submit" class="btn btn-green" style="padding: 5px 10px;">+ Points</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>6. Tester la Carte Fidélité Client</h2>
            <p>Ouvre cette page comme un client le ferait:</p>
            <a href="/loyalty-card.php" target="_blank" class="btn btn-primary">Ouvrir loyalty-card.php →</a>

            <?php if (!empty($customers)): ?>
                <p style="margin-top: 15px;"><strong>Codes de test:</strong></p>
                <ul>
                    <?php foreach (array_slice($customers, 0, 3) as $c): ?>
                        <li><?= htmlspecialchars($c['name']) ?>: <code style="background: #1e293b; padding: 2px 8px; border-radius: 4px;"><?= $c['loyalty_code'] ?? $c['phone'] ?></code></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>7. Tester l'Admin Panel</h2>
            <a href="/admin-panel-v2/index.php#loyalty" target="_blank" class="btn btn-primary">Ouvrir Admin → Fidélité</a>
            <a href="/admin-panel-v2/index.php#customers" target="_blank" class="btn btn-green">Ouvrir Admin → Clients</a>
        </div>
    </div>
</body>
</html>
