<?php
/**
 * Exécution simple des requêtes SQL de correction WebP
 */

// Configuration MySQL directe
$host = '127.0.0.1';
$dbname = 'zajr1824_marvelous';
$username = 'zajr1824_marvelous';
$password = 'Mariagor6!';

// Lire le fichier SQL
$sqlFile = __DIR__ . '/fix-webp-paths.sql';
$sqlContent = file_get_contents($sqlFile);

// Extraire les requêtes UPDATE (ignorer commentaires et lignes vides)
$queries = array_filter(
    explode("\n", $sqlContent),
    function($line) {
        $line = trim($line);
        return !empty($line) && !str_starts_with($line, '--');
    }
);

echo "=== EXÉCUTION CORRECTION WEBP ===\n\n";
echo "Requêtes à exécuter: " . count($queries) . "\n\n";

try {
    // Essayer connexion TCP
    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        // Fallback: connexion socket
        $host = 'localhost';
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    echo "✅ Connexion MySQL OK\n\n";

    $updated = 0;
    $errors = 0;

    foreach ($queries as $sql) {
        try {
            $stmt = $pdo->query($sql);
            $rows = $stmt->rowCount();
            if ($rows > 0) {
                echo "✅ {$rows} ligne(s) mise(s) à jour\n";
                $updated += $rows;
            }
        } catch (PDOException $e) {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
            $errors++;
        }
    }

    echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RÉSULTAT:\n";
    echo "   Lignes mises à jour: {$updated}\n";
    echo "   Erreurs: {$errors}\n\n";

    if ($updated > 0) {
        echo "✅ CORRECTION TERMINÉE!\n";
        echo "→ Les images WebP devraient maintenant s'afficher\n";
        echo "→ Faites Ctrl+Shift+R sur le site pour vider le cache\n";
    }

} catch (PDOException $e) {
    echo "❌ Erreur MySQL: " . $e->getMessage() . "\n";
    exit(1);
}
