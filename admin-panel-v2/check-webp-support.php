<?php
/**
 * Vérifier que le serveur supporte WebP
 */

echo "=== VÉRIFICATION SUPPORT WEBP ===\n\n";

// 1. Fonction imagewebp
echo "1️⃣ Fonction imagewebp():\n";
if (function_exists('imagewebp')) {
    echo "   ✅ imagewebp() disponible\n";
} else {
    echo "   ❌ imagewebp() manquante - WebP non supporté!\n";
    echo "   → Contactez o2switch pour activer le support WebP\n";
    exit(1);
}

// 2. GD Library
echo "\n2️⃣ GD Library:\n";
if (extension_loaded('gd')) {
    echo "   ✅ Extension GD chargée\n";

    $info = gd_info();
    echo "   Version GD: {$info['GD Version']}\n";

    if (isset($info['WebP Support'])) {
        if ($info['WebP Support']) {
            echo "   ✅ WebP Support: OUI\n";
        } else {
            echo "   ❌ WebP Support: NON\n";
            exit(1);
        }
    }
} else {
    echo "   ❌ Extension GD non chargée!\n";
    exit(1);
}

// 3. Test pratique
echo "\n3️⃣ Test de conversion:\n";

try {
    // Créer une image test
    $testImage = imagecreatetruecolor(100, 100);
    $white = imagecolorallocate($testImage, 255, 255, 255);
    imagefill($testImage, 0, 0, $white);

    // Essayer de sauver en WebP
    $testFile = sys_get_temp_dir() . '/test_webp_' . uniqid() . '.webp';
    $result = imagewebp($testImage, $testFile, 85);

    if ($result && file_exists($testFile)) {
        $size = filesize($testFile);
        echo "   ✅ Conversion WebP réussie!\n";
        echo "   Fichier test créé: {$size} bytes\n";
        unlink($testFile);
    } else {
        echo "   ❌ Échec création WebP\n";
        exit(1);
    }

    imagedestroy($testImage);

} catch (Exception $e) {
    echo "   ❌ Erreur: {$e->getMessage()}\n";
    exit(1);
}

echo "\n✅ LE SERVEUR SUPPORTE WEBP!\n";
echo "→ La conversion automatique peut être activée\n";
