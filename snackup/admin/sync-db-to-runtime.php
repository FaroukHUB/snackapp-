<?php
/**
 * Synchroniser TOUS les produits de la base de données vers menu.runtime.json
 * À exécuter sur o2switch pour récupérer les images uploadées via l'admin
 */

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SYNCHRONISATION DATABASE → RUNTIME ===\n\n";

if (SNACK_USE_JSON) {
    echo "❌ MODE JSON ACTIVÉ - MySQL non disponible\n";
    echo "Ce script doit être exécuté sur o2switch où MySQL est actif.\n";
    exit(1);
}

try {
    // Charger le runtime actuel
    $runtime = loadMenuRuntime();
    if (!isset($runtime['products'])) {
        $runtime['products'] = [];
    }

    // Récupérer TOUS les produits de la base avec leurs catégories
    $products = Database::fetchAll(
        "SELECT p.id, p.slug, p.name, p.description, p.price_solo, p.price_menu, p.image, p.status,
                c.slug as category_slug
         FROM products p
         JOIN categories c ON p.category_id = c.id
         WHERE p.restaurant_id = ?
         ORDER BY p.id",
        [SNACK_RESTAURANT_ID]
    );

    echo "📦 Produits trouvés dans la database: " . count($products) . "\n\n";

    $updated = 0;
    $withImages = 0;

    foreach ($products as $product) {
        $productId = $product['slug']; // Utiliser le slug comme ID
        $image = $product['image'] ?? '';

        // Créer ou mettre à jour l'entrée dans le runtime
        if (!isset($runtime['products'][$productId])) {
            $runtime['products'][$productId] = [];
        }

        // Préparer les données du produit
        $productData = [
            'name' => $product['name'],
            'status' => $product['status'] ?? 'available'
        ];

        // Ajouter les champs optionnels seulement s'ils existent
        if (!empty($product['description'])) {
            $productData['description'] = $product['description'];
        }

        if (!empty($image)) {
            $productData['image'] = $image;
            $withImages++;
            echo "✅ {$productId}: {$product['name']} → {$image}\n";
        } else {
            echo "⚠️  {$productId}: {$product['name']} (pas d'image)\n";
        }

        if (!empty($product['price_solo'])) {
            $productData['priceSolo'] = (int)$product['price_solo'];
        }

        if (!empty($product['price_menu'])) {
            $productData['priceMenu'] = (int)$product['price_menu'];
        }

        // Merger avec les données existantes du runtime
        $runtime['products'][$productId] = array_merge(
            $runtime['products'][$productId],
            $productData
        );

        $updated++;
    }

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "RÉSULTAT:\n";
    echo "   Produits traités: {$updated}\n";
    echo "   Avec images: {$withImages}\n";
    echo "   Sans images: " . ($updated - $withImages) . "\n\n";

    // Sauvegarder le runtime
    echo "💾 Sauvegarde du runtime...\n";
    $saved = saveMenuRuntime($runtime, false);

    if ($saved) {
        echo "✅ Runtime sauvegardé avec succès\n\n";

        // Synchroniser vers menu.json
        echo "🔄 Synchronisation vers menu.json...\n";
        require_once __DIR__ . '/sync-menu.php';
        $syncResult = syncMenuStatuses();

        if ($syncResult['success']) {
            echo "✅ Menu.json synchronisé ({$syncResult['updated']} mises à jour)\n";
        } else {
            echo "❌ Erreur sync: {$syncResult['error']}\n";
        }
    } else {
        echo "❌ Erreur lors de la sauvegarde du runtime\n";
    }

    echo "\n✅ SYNCHRONISATION TERMINÉE\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
