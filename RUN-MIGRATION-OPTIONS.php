<?php
/**
 * Script pour exécuter la migration add_item_options.sql
 * À exécuter UNE SEULE FOIS sur o2switch
 */

// Charger la configuration via l'admin bootstrap
require_once __DIR__ . '/admin-panel-v2/bootstrap.php';

echo "<pre>";
echo "===========================================\n";
echo " MIGRATION: Ajout colonnes options items\n";
echo "===========================================\n\n";

try {
    // Vérifier si les colonnes existent déjà
    $columns = Database::fetchAll("SHOW COLUMNS FROM order_items");
    $columnNames = array_column($columns, 'Field');

    echo "Colonnes actuelles dans order_items:\n";
    print_r($columnNames);
    echo "\n";

    if (in_array('selected_options', $columnNames)) {
        echo "✅ Les colonnes existent déjà. Migration déjà appliquée.\n";
        exit;
    }

    echo "📝 Application de la migration...\n\n";

    // Exécuter la migration
    $sql = "
        ALTER TABLE `order_items`
        ADD COLUMN `removed_ingredients` TEXT DEFAULT NULL COMMENT 'JSON array des ingrédients retirés',
        ADD COLUMN `selected_drink` VARCHAR(100) DEFAULT NULL COMMENT 'Nom de la boisson sélectionnée',
        ADD COLUMN `selected_options` TEXT DEFAULT NULL COMMENT 'JSON object des options (boisson, viennoiserie, patisserie, etc.)';
    ";

    Database::execute($sql);

    echo "✅ Migration appliquée avec succès!\n\n";

    // Vérifier les nouvelles colonnes
    $columnsAfter = Database::fetchAll("SHOW COLUMNS FROM order_items");
    echo "Nouvelles colonnes:\n";
    foreach ($columnsAfter as $col) {
        if (in_array($col['Field'], ['removed_ingredients', 'selected_drink', 'selected_options'])) {
            echo "  ✅ {$col['Field']} - {$col['Type']}\n";
        }
    }

    echo "\n===========================================\n";
    echo " ✅ MIGRATION TERMINÉE\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
