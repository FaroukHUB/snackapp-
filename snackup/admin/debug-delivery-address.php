<?php
/**
 * Script de diagnostic pour vérifier la capture des adresses de livraison
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== DIAGNOSTIC ADRESSE DE LIVRAISON ===\n\n";

// 1. Vérifier que les colonnes existent
echo "1️⃣ Vérification structure table orders:\n";
try {
    $columns = Database::fetchAll("SHOW COLUMNS FROM orders LIKE 'delivery_%'");
    if (count($columns) > 0) {
        echo "   ✅ Colonnes trouvées:\n";
        foreach ($columns as $col) {
            echo "      - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ❌ Aucune colonne delivery_* trouvée!\n";
        echo "   → Vous devez appliquer la migration 2026-01-10-add-delivery-address.sql\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: {$e->getMessage()}\n";
    exit(2);
}

echo "\n";

// 2. Vérifier les 5 dernières commandes
echo "2️⃣ Les 5 dernières commandes:\n";
try {
    $orders = Database::fetchAll(
        "SELECT id, order_number, customer_name, delivery_address, delivery_instructions, created_at
         FROM orders
         ORDER BY created_at DESC
         LIMIT 5"
    );

    if (count($orders) > 0) {
        foreach ($orders as $order) {
            $hasAddress = !empty($order['delivery_address']);
            $icon = $hasAddress ? '✅' : '❌';
            echo "   {$icon} Commande #{$order['order_number']} - {$order['customer_name']} ({$order['created_at']})\n";
            if ($hasAddress) {
                echo "      📍 Adresse: {$order['delivery_address']}\n";
                if (!empty($order['delivery_instructions'])) {
                    echo "      ℹ️  Instructions: {$order['delivery_instructions']}\n";
                }
            } else {
                echo "      ⚠️  Pas d'adresse enregistrée\n";
            }
            echo "\n";
        }
    } else {
        echo "   ℹ️  Aucune commande trouvée\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: {$e->getMessage()}\n";
    exit(3);
}

// 3. Statistiques
echo "3️⃣ Statistiques:\n";
try {
    $stats = Database::fetchOne(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN delivery_address IS NOT NULL THEN 1 ELSE 0 END) as with_address,
            SUM(CASE WHEN delivery_address IS NULL THEN 1 ELSE 0 END) as without_address
         FROM orders"
    );

    echo "   📊 Total commandes: {$stats['total']}\n";
    echo "   ✅ Avec adresse: {$stats['with_address']}\n";
    echo "   ❌ Sans adresse: {$stats['without_address']}\n";

    if ($stats['total'] > 0) {
        $percentage = round(($stats['with_address'] / $stats['total']) * 100, 1);
        echo "   📈 Taux de capture: {$percentage}%\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erreur: {$e->getMessage()}\n";
    exit(4);
}

echo "\n✅ Diagnostic terminé\n";
