#!/usr/bin/env php
<?php
/**
 * Script de test du fix "Unknown column type"
 * Usage: php database/test_flavor_fix.php
 */

echo "\n";
echo "=================================================\n";
echo "TEST FIX: Unknown column 'type' → flavor\n";
echo "=================================================\n\n";

// Vérification syntaxe PHP
echo "✓ Vérification syntaxe MenuRepository.php...\n";
$output = [];
$return = 0;
exec('php -l snackup/backend/repositories/MenuRepository.php 2>&1', $output, $return);

if ($return !== 0) {
    echo "❌ ERREUR DE SYNTAXE:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
echo "  → Syntaxe OK\n\n";

// Vérifier que la migration existe
echo "✓ Vérification migration SQL...\n";
$migrationFile = 'database/migrations/2026_01_17_add_flavor_to_supplements.sql';
if (!file_exists($migrationFile)) {
    echo "❌ Fichier migration introuvable: $migrationFile\n";
    exit(1);
}
echo "  → Migration trouvée: $migrationFile\n";

// Vérifier contenu migration
$migrationContent = file_get_contents($migrationFile);
$checks = [
    "ADD COLUMN `flavor`" => "Ajout colonne flavor",
    "ENUM('sale', 'sucre', 'both')" => "Type ENUM correct",
    "DEFAULT 'both'" => "Valeur par défaut",
    "idx_restaurant_flavor" => "Index de performance"
];

foreach ($checks as $pattern => $label) {
    if (strpos($migrationContent, $pattern) === false) {
        echo "❌ MANQUANT dans migration: $label\n";
        exit(1);
    }
    echo "  → $label ✓\n";
}

echo "\n✓ Vérification schema.sql...\n";
$schemaContent = file_get_contents('database/schema.sql');
if (strpos($schemaContent, "`flavor` ENUM('sale', 'sucre', 'both')") === false) {
    echo "❌ schema.sql ne contient pas la colonne flavor\n";
    exit(1);
}
echo "  → schema.sql mis à jour ✓\n";

echo "\n✓ Vérification correction MenuRepository.php...\n";
$repoContent = file_get_contents('snackup/backend/repositories/MenuRepository.php');

// Vérifier qu'on utilise bien 'flavor' et pas 'type'
if (strpos($repoContent, "(flavor = ? OR flavor = 'both')") === false) {
    echo "❌ MenuRepository.php n'utilise pas la colonne flavor\n";
    exit(1);
}
echo "  → Utilise 'flavor' au lieu de 'type' ✓\n";

// Vérifier qu'il n'y a plus de référence à 'type ='
if (preg_match("/type\s*=\s*\?/", $repoContent)) {
    echo "❌ MenuRepository.php contient encore 'type = ?'\n";
    exit(1);
}
echo "  → Aucune référence à 'type' trouvée ✓\n";

echo "\n";
echo "=================================================\n";
echo "✅ TOUS LES TESTS STATIQUES PASSÉS\n";
echo "=================================================\n\n";

echo "PROCHAINES ÉTAPES:\n";
echo "─────────────────────────────────────────────────\n";
echo "1. Appliquer la migration:\n";
echo "   mysql -u USER -p DB_NAME < $migrationFile\n\n";
echo "2. Vérifier la colonne:\n";
echo "   DESCRIBE supplements;\n\n";
echo "3. Tester création catégorie salée:\n";
echo "   Voir database/migrations/TEST_FLAVOR_FIX.md\n\n";
echo "4. Tester création catégorie sucrée:\n";
echo "   Voir database/migrations/TEST_FLAVOR_FIX.md\n\n";
echo "5. Vérifier CRUD produits inchangé:\n";
echo "   Voir database/migrations/TEST_FLAVOR_FIX.md\n\n";

echo "=================================================\n\n";

exit(0);
