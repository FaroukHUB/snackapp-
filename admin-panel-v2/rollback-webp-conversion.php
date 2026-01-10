<?php
/**
 * 🔄 ROLLBACK CONVERSION WEBP
 *
 * Restaure les images originales depuis le backup
 * et remet à jour la base de données
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== ROLLBACK CONVERSION WEBP ===\n\n";

$uploadsDir = __DIR__ . '/../images/uploads';
$backupDir = __DIR__ . '/../images/uploads_backup_original';

// Vérifier que le backup existe
if (!is_dir($backupDir)) {
    echo "❌ Dossier de backup introuvable: {$backupDir}\n";
    echo "   → Aucune restauration possible\n";
    exit(1);
}

// Lister les backups
$backups = glob($backupDir . '/*');

if (count($backups) === 0) {
    echo "❌ Aucun backup trouvé dans {$backupDir}\n";
    exit(1);
}

echo "📁 Backups trouvés: " . count($backups) . "\n\n";

echo "⚠️  ATTENTION:\n";
echo "   Cette opération va:\n";
echo "   1. Supprimer toutes les images WebP de {$uploadsDir}\n";
echo "   2. Restaurer les images originales depuis {$backupDir}\n";
echo "   3. Mettre à jour la base de données\n\n";

echo "Appuyez sur ENTRÉE pour continuer ou Ctrl+C pour annuler...\n";
if (php_sapi_name() === 'cli') {
    fgets(STDIN);
}

echo "\n🔄 Début du rollback...\n\n";

$restored = 0;
$errors = 0;

foreach ($backups as $backupPath) {
    $basename = basename($backupPath);
    echo "📸 {$basename}...";

    try {
        $destPath = $uploadsDir . '/' . $basename;

        // Restaurer le fichier original
        if (!copy($backupPath, $destPath)) {
            throw new Exception('Échec copie');
        }

        chmod($destPath, 0644);

        // Supprimer le WebP correspondant si il existe
        $pathinfo = pathinfo($basename);
        $webpPath = $uploadsDir . '/' . $pathinfo['filename'] . '.webp';

        if (file_exists($webpPath)) {
            unlink($webpPath);
        }

        // Mettre à jour la BDD
        $webpRelativePath = 'images/uploads/' . $pathinfo['filename'] . '.webp';
        $originalRelativePath = 'images/uploads/' . $basename;

        Database::query(
            "UPDATE products SET image = ? WHERE restaurant_id = ? AND image = ?",
            [$originalRelativePath, SNACK_RESTAURANT_ID, $webpRelativePath]
        );

        echo " ✅\n";
        $restored++;

    } catch (Exception $e) {
        echo " ❌ {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RAPPORT:\n";
echo "   ✅ Restaurées: {$restored}\n";
echo "   ❌ Erreurs: {$errors}\n\n";

if ($errors === 0) {
    echo "✅ Rollback terminé avec succès!\n";
    echo "   → Les images originales sont restaurées\n";
    echo "   → Vous pouvez supprimer le dossier {$backupDir} si vous voulez\n";
} else {
    echo "⚠️  Rollback terminé avec des erreurs\n";
    echo "   → Vérifiez manuellement les fichiers\n";
}
