<?php
/**
 * Migration: Ajouter les champs de précommande
 * À exécuter UNE SEULE FOIS via le navigateur
 */
require_once __DIR__ . '/bootstrap.php';

// Vérifier l'authentification admin
requireAdmin();

$migrationExecuted = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute'])) {
    try {
        $pdo = Database::getInstance();

        // Vérifier quelles colonnes existent déjà
        $existingColumns = [];
        $stmt = $pdo->query("SHOW COLUMNS FROM `orders`");
        while ($row = $stmt->fetch()) {
            $existingColumns[] = $row['Field'];
        }

        $columnsToAdd = [];

        // Vérifier chaque colonne individuellement
        if (!in_array('preorder_date', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `preorder_date` DATE DEFAULT NULL COMMENT 'Date de retrait pour précommande' AFTER `pickup_time`";
        }
        if (!in_array('preorder_time', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `preorder_time` TIME DEFAULT NULL COMMENT 'Heure de retrait pour précommande' AFTER `pickup_time`";
        }
        if (!in_array('mode_notes', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `mode_notes` VARCHAR(100) DEFAULT NULL COMMENT 'Mode de commande (À emporter, Sur place, Livraison)' AFTER `pickup_time`";
        }
        if (!in_array('delivery_fee', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0 COMMENT 'Frais de livraison' AFTER `total`";
        }
        if (!in_array('delivery_address', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `delivery_address` TEXT DEFAULT NULL COMMENT 'Adresse de livraison complète' AFTER `notes`";
        }
        if (!in_array('delivery_instructions', $existingColumns)) {
            $columnsToAdd[] = "ADD COLUMN `delivery_instructions` TEXT DEFAULT NULL COMMENT 'Instructions de livraison' AFTER `notes`";
        }

        if (empty($columnsToAdd)) {
            throw new Exception("Toutes les colonnes existent déjà. Migration déjà exécutée.");
        }

        // Exécuter la migration pour les colonnes manquantes
        $sql = "ALTER TABLE `orders` " . implode(", ", $columnsToAdd) . ";";
        $pdo->exec($sql);
        $migrationExecuted = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Base de Données</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 12px;
            font-size: 16px;
        }
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        .info-box li {
            color: #475569;
            margin-bottom: 6px;
            padding-left: 20px;
            position: relative;
        }
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        .success-box {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        .success-box h3 {
            color: #065f46;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .success-box p {
            color: #047857;
        }
        .error-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 4px;
        }
        .error-box h3 {
            color: #991b1b;
            margin-bottom: 8px;
            font-size: 18px;
        }
        .error-box p {
            color: #dc2626;
            font-family: monospace;
            font-size: 13px;
        }
        .btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn:active {
            transform: translateY(0);
        }
        .btn-back {
            background: #6b7280;
            margin-top: 12px;
        }
        .btn-back:hover {
            background: #4b5563;
        }
        .warning {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 4px;
            color: #92400e;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Migration Base de Données</h1>
        <p class="subtitle">Ajout des champs de précommande</p>

        <?php if ($migrationExecuted): ?>
            <div class="success-box">
                <h3>✅ Migration exécutée avec succès!</h3>
                <p>Les colonnes suivantes ont été ajoutées à la table 'orders':</p>
            </div>
            <div class="info-box">
                <ul>
                    <li>preorder_date (DATE)</li>
                    <li>preorder_time (TIME)</li>
                    <li>mode_notes (VARCHAR)</li>
                    <li>delivery_fee (DECIMAL)</li>
                    <li>delivery_address (TEXT)</li>
                    <li>delivery_instructions (TEXT)</li>
                </ul>
            </div>
            <button class="btn btn-back" onclick="window.location.href='index.php'">
                Retour au panneau d'administration
            </button>
        <?php elseif ($error): ?>
            <div class="error-box">
                <h3>❌ Erreur lors de la migration</h3>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
            <button class="btn btn-back" onclick="window.location.href='index.php'">
                Retour au panneau d'administration
            </button>
        <?php else: ?>
            <div class="warning">
                <strong>⚠️ Attention:</strong> Cette migration va modifier la structure de la base de données. Assurez-vous d'avoir une sauvegarde avant de continuer.
            </div>
            <div class="info-box">
                <h3>📋 Colonnes à ajouter:</h3>
                <ul>
                    <li>preorder_date - Date de retrait pour précommande</li>
                    <li>preorder_time - Heure de retrait pour précommande</li>
                    <li>mode_notes - Mode de commande</li>
                    <li>delivery_fee - Frais de livraison</li>
                    <li>delivery_address - Adresse de livraison</li>
                    <li>delivery_instructions - Instructions de livraison</li>
                </ul>
            </div>
            <form method="POST">
                <input type="hidden" name="execute" value="1">
                <button type="submit" class="btn">Exécuter la migration</button>
            </form>
            <button class="btn btn-back" onclick="window.location.href='index.php'">
                Annuler
            </button>
        <?php endif; ?>
    </div>
</body>
</html>
