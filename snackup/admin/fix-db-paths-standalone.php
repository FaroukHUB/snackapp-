<?php
/**
 * Script standalone pour corriger les chemins WebP
 * Connexion directe à MySQL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CORRECTION CHEMINS DATABASE WEBP ===\n\n";

// Configuration MySQL
$host = '127.0.0.1';  // TCP au lieu de socket
$dbname = 'zajr1824_marvelous';
$username = 'zajr1824_marvelous';
$password = 'Mariagor6!';
$restaurantId = 2;

try {
    // Connexion PDO
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "✅ Connexion MySQL réussie\n\n";

    // Récupérer tous les produits
    $stmt = $pdo->prepare('SELECT id, name, image FROM products WHERE restaurant_id = ? ORDER BY id');
    $stmt->execute([$restaurantId]);
    $products = $stmt->fetchAll();

    echo "📊 Total produits: " . count($products) . "\n\n";

    $stats = [
        'total' => count($products),
        'corrected' => 0,
        'already_webp' => 0,
        'webp_not_found' => 0,
        'no_image' => 0
    ];

    $uploadsDir = __DIR__ . '/../images/uploads';

    foreach ($products as $product) {
        $id = $product['id'];
        $name = $product['name'];
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
        $fullPath = __DIR__ . '/../' . $newImage;

        echo "#{$id} {$name}:\n";
        echo "  Ancien: {$currentImage}\n";
        echo "  Nouveau: {$newImage}\n";

        // Vérifier que le fichier WebP existe
        if (!file_exists($fullPath)) {
            echo "  ❌ WebP introuvable\n\n";
            $stats['webp_not_found']++;
            continue;
        }

        // Mettre à jour la base de données
        $updateStmt = $pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
        $updateStmt->execute([$newImage, $id]);

        echo "  ✅ Corrigé\n\n";
        $stats['corrected']++;
    }

    // Rapport final
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RAPPORT FINAL:\n\n";
    echo "   Total produits: {$stats['total']}\n";
    echo "   ✅ Corrigés: {$stats['corrected']}\n";
    echo "   ✓  Déjà en WebP: {$stats['already_webp']}\n";
    echo "   ⚠️  WebP manquant: {$stats['webp_not_found']}\n";
    echo "   -  Sans image: {$stats['no_image']}\n\n";

    if ($stats['corrected'] > 0) {
        echo "✅ BASE DE DONNÉES MISE À JOUR!\n\n";
        echo "💡 PROCHAINES ÉTAPES:\n";
        echo "   1. Ouvrez votre site: https://marvelous.mon-agenceweb.fr\n";
        echo "   2. Faites Ctrl+Shift+R pour vider le cache\n";
        echo "   3. Vérifiez que les images s'affichent correctement\n";
    } else {
        echo "ℹ️  Aucune correction nécessaire - tous les chemins sont déjà corrects\n";
    }

} catch (PDOException $e) {
    echo "❌ Erreur MySQL: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
