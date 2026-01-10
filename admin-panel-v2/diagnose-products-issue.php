<?php
/**
 * Diagnostiquer pourquoi les produits ont prix=0 et status=unavailable
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== DIAGNOSTIC PRODUITS ===\n\n";

try {
    // 1. Compter par statut
    echo "1️⃣ Répartition par statut:\n";

    $available = Database::fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND status = 'available' AND deleted_at IS NULL",
        [SNACK_RESTAURANT_ID]
    );

    $unavailable = Database::fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND status = 'unavailable' AND deleted_at IS NULL",
        [SNACK_RESTAURANT_ID]
    );

    $deleted = Database::fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND deleted_at IS NOT NULL",
        [SNACK_RESTAURANT_ID]
    );

    echo "   ✅ Disponibles: {$available['count']}\n";
    echo "   ❌ Indisponibles: {$unavailable['count']}\n";
    echo "   🗑️  Supprimés: {$deleted['count']}\n";

    echo "\n";

    // 2. Compter par prix
    echo "2️⃣ Répartition par prix:\n";

    $withPrice = Database::fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND price_solo > 0 AND deleted_at IS NULL",
        [SNACK_RESTAURANT_ID]
    );

    $withoutPrice = Database::fetchOne(
        "SELECT COUNT(*) as count FROM products WHERE restaurant_id = ? AND (price_solo = 0 OR price_solo IS NULL) AND deleted_at IS NULL",
        [SNACK_RESTAURANT_ID]
    );

    echo "   ✅ Avec prix (>0): {$withPrice['count']}\n";
    echo "   ❌ Sans prix (=0): {$withoutPrice['count']}\n";

    echo "\n";

    // 3. Vérifier s'il y a eu une corruption ou réinitialisation
    echo "3️⃣ Analyse temporelle:\n";

    $recentUpdates = Database::fetchAll(
        "SELECT DATE(updated_at) as date, COUNT(*) as count
         FROM products
         WHERE restaurant_id = ?
         GROUP BY DATE(updated_at)
         ORDER BY date DESC
         LIMIT 5",
        [SNACK_RESTAURANT_ID]
    );

    if (count($recentUpdates) > 0) {
        echo "   Mises à jour récentes:\n";
        foreach ($recentUpdates as $update) {
            echo "      {$update['date']}: {$update['count']} produits modifiés\n";
        }
    }

    echo "\n";

    // 4. Recommandations
    echo "4️⃣ DIAGNOSTIC:\n";

    if ($withoutPrice['count'] > 50 && $unavailable['count'] > 50) {
        echo "   🚨 PROBLÈME CRITIQUE:\n";
        echo "      → {$withoutPrice['count']} produits ont un prix = 0\n";
        echo "      → {$unavailable['count']} produits sont marqués indisponibles\n";
        echo "      → C'est pour ça que le site affiche un menu vide!\n\n";

        echo "   💡 SOLUTIONS:\n";
        echo "      Option 1: Restaurer depuis une sauvegarde de la base de données\n";
        echo "      Option 2: Réactiver et repriser les produits manuellement\n";
        echo "      Option 3: Lancer un script de correction automatique\n\n";

        echo "   🔧 SCRIPT DE CORRECTION RAPIDE:\n";
        echo "      Je peux créer un script pour:\n";
        echo "      1. Marquer tous les produits comme 'available'\n";
        echo "      2. Assigner des prix par défaut temporaires\n";
        echo "      3. Tu pourras ensuite corriger les prix manuellement\n";
    } elseif ($unavailable['count'] > 50) {
        echo "   ⚠️  Les produits ont des prix mais sont marqués indisponibles\n";
        echo "      → Lancer un script pour les réactiver?\n";
    } elseif ($withoutPrice['count'] > 50) {
        echo "   ⚠️  Les produits sont disponibles mais n'ont pas de prix\n";
        echo "      → Les prix doivent être ressaisis\n";
    } else {
        echo "   ✅ La configuration semble correcte\n";
    }

} catch (Exception $e) {
    echo "❌ ERREUR: {$e->getMessage()}\n";
}

echo "\n✅ Diagnostic terminé\n";
