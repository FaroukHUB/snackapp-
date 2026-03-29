<?php
/**
 * Script pour exécuter la migration SQL: Ajout colonne snackup_context
 */

// Charger la configuration
$dbConfig = require __DIR__ . '/database/config.php';

$host = $dbConfig['database']['host'];
$port = $dbConfig['database']['port'];
$dbname = $dbConfig['database']['dbname'];
$username = $dbConfig['database']['username'];
$password = $dbConfig['database']['password'];
$charset = $dbConfig['database']['charset'];

try {
    // Connexion à la base de données
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Connexion à la base de données réussie\n";
    echo "Base de données: {$dbname}\n\n";

    // Lire le fichier de migration
    $migrationFile = __DIR__ . '/database/migrations/2026-01-26-add-snackup-context.sql';
    if (!file_exists($migrationFile)) {
        throw new Exception("Fichier de migration introuvable: {$migrationFile}");
    }

    $sql = file_get_contents($migrationFile);
    echo "📄 Fichier de migration chargé: {$migrationFile}\n\n";

    // Extraire les commandes SQL (ignorer les commentaires et lignes vides)
    $lines = explode("\n", $sql);
    $commands = [];
    $currentCommand = '';

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignorer les commentaires et lignes vides
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }

        $currentCommand .= $line . ' ';

        // Si la ligne se termine par un point-virgule, c'est la fin de la commande
        if (substr(rtrim($line), -1) === ';') {
            $commands[] = trim($currentCommand);
            $currentCommand = '';
        }
    }

    echo "🔧 Exécution de la migration...\n\n";

    // Exécuter chaque commande SQL
    foreach ($commands as $command) {
        if (empty($command)) continue;

        echo "Exécution: " . substr($command, 0, 100) . "...\n";

        try {
            $pdo->exec($command);
            echo "✅ Succès\n\n";
        } catch (PDOException $e) {
            // Si l'erreur est "column already exists", continuer
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  La colonne existe déjà - ignoré\n\n";
            } else {
                throw $e;
            }
        }
    }

    // Vérifier que la colonne a été ajoutée
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'snackup_context'");
    $column = $stmt->fetch();

    if ($column) {
        echo "✅ Migration réussie !\n";
        echo "✅ Colonne 'snackup_context' ajoutée à la table 'products'\n";
        echo "\nDétails de la colonne:\n";
        print_r($column);
    } else {
        echo "❌ Erreur: La colonne 'snackup_context' n'a pas été trouvée\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
