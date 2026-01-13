<?php
// Script pour exécuter la migration de précommande
require_once __DIR__ . '/../admin-panel-v2/bootstrap.php';

try {
    // Lire le fichier de migration
    $migrationFile = __DIR__ . '/migrations/add_preorder_fields.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Fichier de migration introuvable: $migrationFile");
    }

    $sql = file_get_contents($migrationFile);

    echo "📦 Exécution de la migration...\n\n";

    // Utiliser la connexion déjà initialisée par bootstrap
    $pdo = Database::getInstance();

    // Exécuter la migration
    $pdo->exec($sql);

    echo "✅ Migration exécutée avec succès!\n\n";
    echo "Colonnes ajoutées à la table 'orders':\n";
    echo "  - preorder_date (DATE)\n";
    echo "  - preorder_time (TIME)\n";
    echo "  - mode_notes (VARCHAR)\n";
    echo "  - delivery_fee (DECIMAL)\n";
    echo "  - delivery_address (TEXT)\n";
    echo "  - delivery_instructions (TEXT)\n";

} catch (Exception $e) {
    echo "❌ Erreur lors de la migration: " . $e->getMessage() . "\n";
    exit(1);
}
