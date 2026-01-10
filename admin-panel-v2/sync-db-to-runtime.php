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

    // Récupérer TOUS les produits de la base
    $products = Database::fetchAll(
        "SELECT id, name, description, category, price, price_menu, image, status, supplements
         FROM products
         WHERE restaurant_id = ?
         ORDER BY id",
        [SNACK_RESTAURANT_ID]
    );

    echo "📦 Produits trouvés dans la database: " . count($products) . "\n\n";

    $updated = 0;
    $withImages = 0;

    foreach ($products as $product) {
        $productId = $product['id'];
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

        if (!empty($product['price'])) {
            $productData['priceSolo'] = (int)$product['price'];
        }

        if (!empty($product['price_menu'])) {
            $productData['priceMenu'] = (int)$product['price_menu'];
        }

        // Suppléments (décodé depuis JSON)
        if (!empty($product['supplements'])) {
            $supplements = json_decode($product['supplements'], true);
            if (is_array($supplements)) {
                $productData['supplements'] = $supplements;
            }
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
