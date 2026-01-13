<?php
/**
 * Script pour exécuter la migration delivery_address
 * À exécuter UNE SEULE FOIS sur le serveur de production
 */

require_once __DIR__ . '/../admin-panel-v2/bootstrap.php';

echo "🔧 Migration: Ajout colonne delivery_address\n\n";

try {
    // Database::getInstance() retourne directement l'instance PDO
    $pdo = Database::getInstance();

    // Vérifier si la colonne existe déjà
    $check = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_address'")->fetch();

    if ($check) {
        echo "✅ La colonne delivery_address existe déjà. Migration déjà effectuée.\n";
        exit(0);
    }

    echo "📝 Exécution de la migration...\n";

    // Ajouter les colonnes
    $pdo->exec("
        ALTER TABLE orders
        ADD COLUMN delivery_address VARCHAR(500) DEFAULT NULL AFTER customer_phone,
        ADD COLUMN delivery_instructions TEXT DEFAULT NULL AFTER delivery_address
    ");

    echo "✅ Colonnes ajoutées avec succès\n";

    // Créer l'index
    $pdo->exec("CREATE INDEX idx_delivery_address ON orders(delivery_address(255))");

    echo "✅ Index créé avec succès\n";
    echo "\n🎉 Migration terminée avec succès!\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
    exit(1);
}
