

#!/usr/bin/env php
<?php
/**
 * Script de migration : Changer restaurant_id de 2 vers 3
 * Base de données : zajr1824_atelierpizza
 *
 * Ce script met à jour tous les restaurant_id=2 vers restaurant_id=3
 * pour corriger la migration incomplète depuis Marvelous vers Atelier Pizza
 */

echo "=== MIGRATION RESTAURANT_ID : 2 → 3 ===\n";
echo "Base de données : zajr1824_atelierpizza\n";
echo "Date : " . date('Y-m-d H:i:s') . "\n\n";

// Charger la configuration
require_once __DIR__ . '/snackup/backend/Database.php';
$config = require __DIR__ . '/database/config.php';

try {
    Database::init($config['database']);
    $pdo = Database::getInstance();

    echo "✅ Connexion à la base de données OK\n\n";

    // Liste des tables à migrer
    $tables = [
        'admin_users',
        'categories',
        'products',
        'supplements',
        'orders',
        'customers',
        'promo_codes',
        'restaurant_settings',
        'restaurant_admins'
    ];

    $totalUpdated = 0;

    // Commencer une transaction
    $pdo->beginTransaction();

    foreach ($tables as $table) {
        // Vérifier si la table existe
        $checkTable = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($checkTable->rowCount() === 0) {
            echo "⚠️  Table '$table' n'existe pas, ignorée\n";
            continue;
        }

        // Vérifier si la colonne restaurant_id existe
        $checkColumn = $pdo->query("SHOW COLUMNS FROM $table LIKE 'restaurant_id'");
        if ($checkColumn->rowCount() === 0) {
            echo "⏭️  Table '$table' n'a pas de colonne restaurant_id, ignorée\n";
            continue;
        }

        // Compter les lignes à migrer
        $count = $pdo->query("SELECT COUNT(*) FROM $table WHERE restaurant_id = 2")->fetchColumn();

        if ($count == 0) {
            echo "✓ Table '$table' : 0 ligne à migrer\n";
            continue;
        }

        // Effectuer la migration
        $stmt = $pdo->prepare("UPDATE $table SET restaurant_id = 3 WHERE restaurant_id = 2");
        $stmt->execute();
        $affected = $stmt->rowCount();

        echo "✅ Table '$table' : $affected lignes migrées (2 → 3)\n";
        $totalUpdated += $affected;
    }

    // Valider la transaction
    $pdo->commit();

    echo "\n=== MIGRATION TERMINÉE ===\n";
    echo "Total : $totalUpdated lignes mises à jour\n";
    echo "Statut : ✅ SUCCÈS\n\n";

    // Vérification finale
    echo "=== VÉRIFICATION FINALE ===\n";

    $verifications = [
        'admin_users' => "SELECT COUNT(*) FROM admin_users WHERE restaurant_id = 3",
        'categories' => "SELECT COUNT(*) FROM categories WHERE restaurant_id = 3",
        'products' => "SELECT COUNT(*) FROM products p INNER JOIN categories c ON p.category_id = c.id WHERE c.restaurant_id = 3"
    ];

    foreach ($verifications as $table => $query) {
        $count = $pdo->query($query)->fetchColumn();
        echo "✓ $table avec restaurant_id=3 : $count\n";
    }

    echo "\n✅ Migration réussie ! Vous pouvez maintenant vous connecter avec restaurant_id=3\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    echo "La migration a été annulée (rollback)\n";
    exit(1);
}
