<?php
/**
 * Exécution de la correction des chemins WebP
 * Appelé via AJAX depuis fix-webp.php
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

echo "<span class='success'>✅ Connexion base de données OK</span>\n\n";

if (SNACK_USE_JSON) {
    echo "<span class='error'>❌ Mode JSON activé - base de données non disponible</span>\n";
    exit(1);
}

try {
    // Récupérer tous les produits
    $products = Database::fetchAll(
        'SELECT id, name, image FROM products WHERE restaurant_id = ? ORDER BY id',
        [SNACK_RESTAURANT_ID]
    );

    echo "<span class='success'>📊 Total produits: " . count($products) . "</span>\n\n";

    $stats = [
        'total' => count($products),
        'corrected' => 0,
        'already_webp' => 0,
        'webp_not_found' => 0,
        'no_image' => 0
    ];

    foreach ($products as $product) {
        $id = $product['id'];
        $name = htmlspecialchars($product['name']);
        $currentImage = $product['image'];

        // Pas d'image
        if (empty($currentImage)) {
            $stats['no_image']++;
            continue;
        }

        // Déjà en WebP
        if (preg_match('/\.webp$/i', $currentImage)) {
            $stats['already_webp']++;
            continue;
        }

        // Convertir le chemin vers .webp
        $newImage = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $currentImage);
        $fullPath = SNACK_ROOT . '/' . $newImage;

        echo "#{$id} {$name}:\n";
        echo "  Ancien: {$currentImage}\n";
        echo "  Nouveau: {$newImage}\n";

        // Vérifier que le fichier WebP existe
        if (!file_exists($fullPath)) {
            echo "  <span class='error'>❌ WebP introuvable</span>\n\n";
            $stats['webp_not_found']++;
            continue;
        }

        // Mettre à jour la base de données
        Database::query(
            'UPDATE products SET image = ? WHERE id = ?',
            [$newImage, $id]
        );

        echo "  <span class='success'>✅ Corrigé</span>\n\n";
        $stats['corrected']++;
    }

    // Rapport final
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "<span class='success'>📊 RAPPORT FINAL:</span>\n\n";
    echo "   Total produits: {$stats['total']}\n";
    echo "   <span class='success'>✅ Corrigés: {$stats['corrected']}</span>\n";
    echo "   ✓  Déjà en WebP: {$stats['already_webp']}\n";
    echo "   <span class='warning'>⚠️  WebP manquant: {$stats['webp_not_found']}</span>\n";
    echo "   -  Sans image: {$stats['no_image']}\n\n";

    if ($stats['corrected'] > 0) {
        echo "<span class='success'>✅ BASE DE DONNÉES MISE À JOUR!</span>\n\n";
        echo "💡 <strong>PROCHAINES ÉTAPES:</strong>\n";
        echo "   1. Ouvrez votre site: <a href='https://marvelous.mon-agenceweb.fr' target='_blank'>marvelous.mon-agenceweb.fr</a>\n";
        echo "   2. Faites <strong>Ctrl+Shift+R</strong> pour vider le cache\n";
        echo "   3. Vérifiez que les images s'affichent correctement\n";
    } else {
        echo "<span class='success'>ℹ️  Aucune correction nécessaire - tous les chemins sont déjà corrects</span>\n";
    }

} catch (Exception $e) {
    echo "<span class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "Trace:\n" . htmlspecialchars($e->getTraceAsString()) . "\n";
    exit(1);
}
