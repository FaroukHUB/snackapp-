<?php
/**
 * ⚡ CONVERSION IMAGES EXISTANTES EN WEBP
 *
 * Ce script convertit toutes les images PNG/JPG/JPEG existantes en WebP
 * - Crée un backup de chaque original
 * - Convertit en WebP optimisé (qualité 85%, max 800px)
 * - Met à jour la base de données MySQL
 * - Permet un rollback si nécessaire
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== CONVERSION IMAGES EXISTANTES EN WEBP ===\n\n";

// Configuration
$uploadsDir = __DIR__ . '/../images/uploads';
$backupDir = __DIR__ . '/../images/uploads_backup_original';
$quality = 85;
$maxWidth = 800;

// Statistiques
$stats = [
    'total' => 0,
    'converted' => 0,
    'skipped' => 0,
    'errors' => 0,
    'size_before' => 0,
    'size_after' => 0
];

// 1. Créer dossier de backup
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "✅ Dossier backup créé: {$backupDir}\n\n";
} else {
    echo "✅ Dossier backup existe déjà\n\n";
}

// 2. Lister toutes les images
$imageExtensions = ['jpg', 'jpeg', 'png'];
$images = [];

foreach ($imageExtensions as $ext) {
    $found = glob($uploadsDir . '/*.' . $ext);
    $images = array_merge($images, $found);
}

$stats['total'] = count($images);

echo "📊 Images trouvées: {$stats['total']}\n";
echo "📁 Dossier source: {$uploadsDir}\n";
echo "💾 Dossier backup: {$backupDir}\n\n";

if ($stats['total'] === 0) {
    echo "✅ Aucune image à convertir (toutes déjà en WebP?)\n";
    exit(0);
}

echo "⚠️  ATTENTION:\n";
echo "   - Les images originales seront sauvegardées dans {$backupDir}\n";
echo "   - Les images seront converties en WebP (qualité {$quality}%, max {$maxWidth}px)\n";
echo "   - La base de données sera mise à jour automatiquement\n\n";

echo "Appuyez sur ENTRÉE pour continuer ou Ctrl+C pour annuler...\n";
if (php_sapi_name() === 'cli') {
    fgets(STDIN);
}

echo "\n🔄 Début de la conversion...\n\n";

// 3. Convertir chaque image
foreach ($images as $imagePath) {
    $basename = basename($imagePath);
    $pathinfo = pathinfo($imagePath);
    $filenameWithoutExt = $pathinfo['filename'];
    $ext = strtolower($pathinfo['extension']);

    echo "📸 {$basename}...";

    try {
        // Backup de l'original
        $backupPath = $backupDir . '/' . $basename;
        if (!copy($imagePath, $backupPath)) {
            throw new Exception('Échec backup');
        }

        // Taille avant
        $sizeBefore = filesize($imagePath);
        $stats['size_before'] += $sizeBefore;

        // Charger l'image
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            throw new Exception('Impossible de lire l\'image');
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mime = $imageInfo['mime'];

        // Créer ressource image
        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($imagePath);
                break;
            default:
                throw new Exception('Type non supporté: ' . $mime);
        }

        if ($sourceImage === false) {
            throw new Exception('Échec chargement image');
        }

        // Redimensionner si nécessaire
        if ($sourceWidth > $maxWidth) {
            $ratio = $maxWidth / $sourceWidth;
            $newWidth = $maxWidth;
            $newHeight = (int)($sourceHeight * $ratio);

            // Utiliser imagecopyresampled au lieu de imagescale (plus compatible)
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Préserver la transparence pour PNG
            if ($mime === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            $success = imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
            imagedestroy($sourceImage);

            if (!$success) {
                imagedestroy($resizedImage);
                throw new Exception('Échec redimensionnement');
            }

            $sourceImage = $resizedImage;
        }

        // Nouveau chemin WebP (remplace l'extension)
        $webpPath = $uploadsDir . '/' . $filenameWithoutExt . '.webp';

        // Convertir en WebP
        $success = imagewebp($sourceImage, $webpPath, $quality);
        imagedestroy($sourceImage);

        if (!$success || !file_exists($webpPath)) {
            throw new Exception('Échec création WebP');
        }

        chmod($webpPath, 0644);

        // Taille après
        $sizeAfter = filesize($webpPath);

        // Calculer la réduction
        $reduction = round((($sizeBefore - $sizeAfter) / $sizeBefore) * 100, 1);
        $sizeBeforeKB = round($sizeBefore / 1024, 1);
        $sizeAfterKB = round($sizeAfter / 1024, 1);

        // ⚠️ Si le WebP est plus gros, garder l'original
        if ($sizeAfter >= $sizeBefore) {
            unlink($webpPath); // Supprimer le WebP trop gros
            echo " ⏭️  {$sizeBeforeKB} KB → {$sizeAfterKB} KB (+{$reduction}%) - GARDÉ ORIGINAL\n";
            $stats['skipped']++;
            $stats['size_after'] += $sizeBefore;
            continue;
        }

        $stats['size_after'] += $sizeAfter;

        echo " ✅ {$sizeBeforeKB} KB → {$sizeAfterKB} KB (-{$reduction}%)\n";

        // Supprimer l'ancien fichier (on a le backup)
        unlink($imagePath);

        // Mettre à jour la base de données
        $oldRelativePath = 'images/uploads/' . $basename;
        $newRelativePath = 'images/uploads/' . $filenameWithoutExt . '.webp';

        $updated = Database::execute(
            "UPDATE products SET image = ? WHERE restaurant_id = ? AND image = ?",
            [$newRelativePath, SNACK_RESTAURANT_ID, $oldRelativePath]
        );

        $stats['converted']++;

    } catch (Exception $e) {
        echo " ❌ ERREUR: {$e->getMessage()}\n";
        $stats['errors']++;
    }
}

// 4. Rapport final
echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 RAPPORT FINAL:\n\n";

echo "   Total images: {$stats['total']}\n";
echo "   ✅ Converties: {$stats['converted']}\n";
echo "   ⏭️  Ignorées: {$stats['skipped']}\n";
echo "   ❌ Erreurs: {$stats['errors']}\n\n";

$totalSizeBefore = round($stats['size_before'] / 1024 / 1024, 2);
$totalSizeAfter = round($stats['size_after'] / 1024 / 1024, 2);
$totalSaved = round(($stats['size_before'] - $stats['size_after']) / 1024 / 1024, 2);
$totalReduction = $stats['size_before'] > 0 ? round((($stats['size_before'] - $stats['size_after']) / $stats['size_before']) * 100, 1) : 0;

echo "   📦 Taille avant: {$totalSizeBefore} MB\n";
echo "   📦 Taille après: {$totalSizeAfter} MB\n";
echo "   💾 Économie: {$totalSaved} MB (-{$totalReduction}%)\n\n";

echo "💡 PROCHAINES ÉTAPES:\n";
echo "   1. Testez votre site pour vérifier que les images s'affichent\n";
echo "   2. Si tout fonctionne: les originaux sont dans {$backupDir}\n";
echo "   3. Si problème: utilisez le script de rollback (à créer)\n\n";

echo "✅ Conversion terminée!\n";
