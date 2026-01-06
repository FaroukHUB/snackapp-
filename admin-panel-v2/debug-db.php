<?php
/**
 * Script de diagnostic - État de la base de données
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Diagnostic DB</title>";
echo "<style>body{font-family:monospace;background:#1a1a2e;color:#fff;padding:20px}";
echo ".ok{color:#10b981}.error{color:#ef4444}.warning{color:#f59e0b}";
echo "pre{background:#0f0f1e;padding:15px;border-radius:8px;overflow-x:auto}</style></head><body>";

echo "<h1>🔍 Diagnostic Base de Données</h1>";
echo "<p>Timestamp: " . date('Y-m-d H:i:s') . "</p><hr>";

// 1. Mode actif
echo "<h2>1️⃣ Mode actif</h2>";
if (defined('SNACK_USE_JSON')) {
    if (SNACK_USE_JSON) {
        echo "<p class='error'>❌ Mode: JSON (fallback)</p>";
    } else {
        echo "<p class='ok'>✅ Mode: MySQL</p>";
    }
} else {
    echo "<p class='error'>❌ SNACK_USE_JSON non défini</p>";
}

// 2. Erreur DB
echo "<h2>2️⃣ Connexion Database</h2>";
if (defined('SNACK_DB_ERROR')) {
    echo "<p class='error'>❌ Erreur DB: " . SNACK_DB_ERROR . "</p>";
} else {
    echo "<p class='ok'>✅ Pas d'erreur DB détectée</p>";
}

// 3. Restaurant ID
echo "<h2>3️⃣ Restaurant ID</h2>";
if (defined('SNACK_RESTAURANT_ID')) {
    echo "<p class='ok'>✅ SNACK_RESTAURANT_ID = " . SNACK_RESTAURANT_ID . "</p>";
} else {
    echo "<p class='error'>❌ SNACK_RESTAURANT_ID non défini</p>";
}

// 4. Test connexion directe
echo "<h2>4️⃣ Test connexion MySQL</h2>";
try {
    $dbConfig = require __DIR__ . '/../database/config.php';
    echo "<pre>";
    echo "Host: " . $dbConfig['database']['host'] . "\n";
    echo "Database: " . $dbConfig['database']['dbname'] . "\n";
    echo "Username: " . $dbConfig['database']['username'] . "\n";
    echo "</pre>";

    Database::init($dbConfig['database']);
    echo "<p class='ok'>✅ Connexion Database OK</p>";

    // 5. Compter les clients
    echo "<h2>5️⃣ Clients en base</h2>";
    require_once __DIR__ . '/../database/repositories/CustomerRepository.php';
    $customers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    echo "<p class='ok'>✅ Total clients: <strong>" . count($customers) . "</strong></p>";

    if (count($customers) > 0) {
        echo "<h3>Premiers clients:</h3><pre>";
        foreach (array_slice($customers, 0, 5) as $c) {
            echo "- " . ($c['name'] ?? 'N/A') . " | " . ($c['phone'] ?? 'N/A') . " | Points: " . ($c['loyalty_points'] ?? 0) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Aucun client en base</p>";
    }

    // 6. Compter les récompenses
    echo "<h2>6️⃣ Récompenses fidélité</h2>";
    require_once __DIR__ . '/../database/repositories/LoyaltyRepository.php';
    $rewards = LoyaltyRepository::getAllRewards(SNACK_RESTAURANT_ID);
    echo "<p class='ok'>✅ Total récompenses: <strong>" . count($rewards) . "</strong></p>";

    if (count($rewards) > 0) {
        echo "<pre>";
        foreach ($rewards as $r) {
            echo "- " . $r['name'] . " (" . $r['points_required'] . " pts)\n";
        }
        echo "</pre>";
    }

    // 7. Compter les commandes
    echo "<h2>7️⃣ Commandes</h2>";
    require_once __DIR__ . '/../database/repositories/OrderRepository.php';
    $orders = OrderRepository::getActiveOrders(SNACK_RESTAURANT_ID, 10);
    echo "<p class='ok'>✅ Total commandes actives: <strong>" . count($orders) . "</strong></p>";

} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><a href='index.php' style='color:#10b981'>← Retour admin</a></p>";
echo "</body></html>";
