<?php
/**
 * SnackApp - Carte de Fidélité Client
 * Page publique pour consulter ses points fidélité
 */

require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/database/repositories/CustomerRepository.php';
require_once __DIR__ . '/database/repositories/LoyaltyRepository.php';
require_once __DIR__ . '/database/repositories/RestaurantRepository.php';

// Configuration restaurant (à adapter selon votre setup)
define('SNACK_RESTAURANT_ID', 1);

$restaurant = RestaurantRepository::getById(SNACK_RESTAURANT_ID);
$restaurantName = $restaurant['name'] ?? 'SnackApp';
$primaryColor = $restaurant['primary_color'] ?? '#c58a3a';

$customer = null;
$rewards = [];
$error = '';
$loyaltyConfig = LoyaltyRepository::getConfig(SNACK_RESTAURANT_ID);

// Recherche par code ou téléphone
if (isset($_GET['code']) || isset($_POST['search'])) {
    $searchTerm = $_GET['code'] ?? $_POST['search'] ?? '';
    $searchTerm = trim($searchTerm);

    if (!empty($searchTerm)) {
        // Essayer par code fidélité
        if (strpos(strtoupper($searchTerm), 'SNACK-') === 0) {
            $customer = CustomerRepository::getByLoyaltyCode(strtoupper($searchTerm));
        }

        // Sinon par téléphone
        if (!$customer) {
            $customer = CustomerRepository::getByPhone(SNACK_RESTAURANT_ID, $searchTerm);
        }

        if (!$customer) {
            $error = "Aucun compte trouvé. Vérifiez votre code ou numéro de téléphone.";
        } else {
            $rewards = LoyaltyRepository::getRewards(SNACK_RESTAURANT_ID);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Carte Fidélité - <?php echo htmlspecialchars($restaurantName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
            min-height: 100vh;
            color: white;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
        }
        .header {
            text-align: center;
            padding: 30px 0;
        }
        .header h1 {
            color: <?php echo $primaryColor; ?>;
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        .header p {
            color: #9ca3af;
            font-size: 14px;
        }

        /* Search Form */
        .search-card {
            background: #2a2a3e;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .search-card h2 {
            font-size: 1.1em;
            margin-bottom: 15px;
            color: #f59e0b;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-form input {
            flex: 1;
            padding: 14px;
            border: 2px solid #374151;
            border-radius: 12px;
            background: #1e293b;
            color: white;
            font-size: 16px;
        }
        .search-form input:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
        }
        .search-form button {
            padding: 14px 20px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, #d97706);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }
        .error {
            background: #dc262622;
            border: 1px solid #dc2626;
            color: #fca5a5;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 14px;
        }

        /* Loyalty Card */
        .loyalty-card {
            background: linear-gradient(135deg, #2a2a3e 0%, #1e293b 100%);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            border: 2px solid <?php echo $primaryColor; ?>44;
            position: relative;
            overflow: hidden;
        }
        .loyalty-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, <?php echo $primaryColor; ?>22 0%, transparent 70%);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            position: relative;
        }
        .customer-info h3 {
            font-size: 1.3em;
            margin-bottom: 5px;
        }
        .customer-info p {
            color: #9ca3af;
            font-size: 13px;
        }
        .loyalty-code {
            background: <?php echo $primaryColor; ?>;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 1px;
        }

        /* Points Display */
        .points-display {
            text-align: center;
            padding: 30px 0;
            position: relative;
        }
        .points-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, #d97706);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            box-shadow: 0 10px 40px <?php echo $primaryColor; ?>44;
        }
        .points-number {
            font-size: 42px;
            font-weight: bold;
        }
        .points-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 20px;
            position: relative;
        }
        .stat-item {
            text-align: center;
            padding: 15px 10px;
            background: #1e293b;
            border-radius: 12px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: <?php echo $primaryColor; ?>;
        }
        .stat-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* Rewards */
        .rewards-section {
            background: #2a2a3e;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .rewards-section h3 {
            font-size: 1.1em;
            margin-bottom: 15px;
            color: #f59e0b;
        }
        .reward-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #1e293b;
            border-radius: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #374151;
        }
        .reward-item.available {
            border-left-color: #10b981;
        }
        .reward-info h4 {
            font-size: 14px;
            margin-bottom: 4px;
        }
        .reward-info p {
            color: #6b7280;
            font-size: 12px;
        }
        .reward-points {
            text-align: right;
        }
        .reward-points .points {
            font-weight: bold;
            color: #f59e0b;
        }
        .reward-points .status {
            font-size: 11px;
            margin-top: 4px;
        }
        .status.available {
            color: #10b981;
        }
        .status.locked {
            color: #6b7280;
        }

        /* QR Code Section */
        .qr-section {
            background: #2a2a3e;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
        }
        .qr-section h3 {
            font-size: 1em;
            margin-bottom: 15px;
            color: #9ca3af;
        }
        .qr-code {
            background: white;
            padding: 15px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 15px;
        }
        .qr-code img {
            display: block;
        }
        .qr-note {
            color: #6b7280;
            font-size: 12px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: #6b7280;
            font-size: 12px;
        }
        .footer a {
            color: <?php echo $primaryColor; ?>;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gift"></i> <?php echo htmlspecialchars($restaurantName); ?></h1>
            <p>Programme Fidélité</p>
        </div>

        <?php if (!$customer): ?>
        <!-- Search Form -->
        <div class="search-card">
            <h2><i class="fas fa-search"></i> Consulter mes points</h2>
            <form method="POST" class="search-form">
                <input type="text" name="search" placeholder="Code fidélité ou n° téléphone"
                       value="<?php echo htmlspecialchars($_POST['search'] ?? ''); ?>" required>
                <button type="submit"><i class="fas fa-arrow-right"></i></button>
            </form>
            <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <p style="color: #6b7280; font-size: 12px; margin-top: 15px; text-align: center;">
                Entrez votre code fidélité (ex: SNACK-A3X7) ou votre numéro de téléphone
            </p>
        </div>

        <?php else: ?>
        <!-- Loyalty Card -->
        <div class="loyalty-card">
            <div class="card-header">
                <div class="customer-info">
                    <h3><?php echo htmlspecialchars($customer['name']); ?></h3>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></p>
                </div>
                <div class="loyalty-code"><?php echo htmlspecialchars($customer['loyalty_code'] ?? 'N/A'); ?></div>
            </div>

            <div class="points-display">
                <div class="points-circle">
                    <div class="points-number"><?php echo number_format($customer['loyalty_points'] ?? 0); ?></div>
                    <div class="points-label">points</div>
                </div>
            </div>

            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $customer['orders_count'] ?? 0; ?></div>
                    <div class="stat-label">Commandes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo number_format($customer['total_spent'] ?? 0, 0); ?>€</div>
                    <div class="stat-label">Dépensé</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $loyaltyConfig['points_per_euro'] ?? 1; ?></div>
                    <div class="stat-label">Pts/€</div>
                </div>
            </div>
        </div>

        <!-- Available Rewards -->
        <?php if (!empty($rewards)): ?>
        <div class="rewards-section">
            <h3><i class="fas fa-trophy"></i> Récompenses disponibles</h3>
            <?php foreach ($rewards as $reward):
                $customerPoints = $customer['loyalty_points'] ?? 0;
                $canRedeem = $customerPoints >= $reward['points_required'];
                $remaining = $reward['points_required'] - $customerPoints;
            ?>
            <div class="reward-item <?php echo $canRedeem ? 'available' : ''; ?>">
                <div class="reward-info">
                    <h4><?php echo htmlspecialchars($reward['name']); ?></h4>
                    <p><?php echo htmlspecialchars($reward['description'] ?? ''); ?></p>
                </div>
                <div class="reward-points">
                    <div class="points"><?php echo $reward['points_required']; ?> pts</div>
                    <div class="status <?php echo $canRedeem ? 'available' : 'locked'; ?>">
                        <?php if ($canRedeem): ?>
                            <i class="fas fa-check-circle"></i> Disponible
                        <?php else: ?>
                            <i class="fas fa-lock"></i> Encore <?php echo $remaining; ?> pts
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- QR Code -->
        <div class="qr-section">
            <h3><i class="fas fa-qrcode"></i> Montrez ce code en caisse</h3>
            <div class="qr-code">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($customer['loyalty_code'] ?? ''); ?>"
                     alt="QR Code Fidélité" width="150" height="150">
            </div>
            <p class="qr-note">
                Code: <strong><?php echo htmlspecialchars($customer['loyalty_code'] ?? 'N/A'); ?></strong><br>
                Montrez ce QR code pour utiliser vos points
            </p>
        </div>

        <!-- Back Button -->
        <div style="text-align: center; margin-top: 20px;">
            <a href="loyalty-card.php" style="color: #9ca3af; text-decoration: none; font-size: 14px;">
                <i class="fas fa-arrow-left"></i> Changer de compte
            </a>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Programme fidélité <?php echo htmlspecialchars($restaurantName); ?></p>
            <p style="margin-top: 5px;">
                <a href="/">Retour au menu</a>
            </p>
        </div>
    </div>
</body>
</html>
