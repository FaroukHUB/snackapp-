<?php
/**
 * Génère menu.json depuis MySQL
 * À appeler après chaque modification (add/edit/delete category/product)
 */

define('SNACK_ROOT', __DIR__ . '/..');
define('SNACK_RESTAURANT_ID', 2);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/repositories/MenuRepository.php';

// Charger config DB
$dbConfig = require __DIR__ . '/config.php';
Database::init($dbConfig['database']);

try {
    $pdo = Database::getInstance();

    // Charger l'ancien menu.json pour récupérer formules et options spéciales
    $menuJsonPath = SNACK_ROOT . '/config/menu.json';
    $oldMenuData = [];
    if (file_exists($menuJsonPath)) {
        $oldMenuData = json_decode(file_get_contents($menuJsonPath), true) ?: [];
    }

    // Récupérer toutes les données depuis MySQL
    $categories = MenuRepository::getAllCategories();
    $supplements = MenuRepository::getAllSupplements();
    $categorySupplements = MenuRepository::getCategorySupplements();

    // ✅ PRÉSERVER les icônes de l'ancien menu.json EN PRIORITÉ
    $categoryIcons = $oldMenuData['categoryIcons'] ?? [];

    // ✅ ENRICHIR avec options spéciales de l'ancien menu.json
    foreach ($categories as &$cat) {
        $catSlug = $cat['slug'] ?? '';

        // Ajouter icône depuis MySQL si pas dans categoryIcons
        if (isset($cat['icon']) && !empty($catSlug) && !isset($categoryIcons[$catSlug])) {
            $categoryIcons[$catSlug] = $cat['icon'];
        }

        foreach ($cat['items'] as &$item) {
            // Convertir prix en nombres (déjà en DA, pas besoin de /100)
            if (isset($item['priceSolo'])) {
                $item['priceSolo'] = floatval($item['priceSolo']);
            }
            if (isset($item['priceMenu'])) {
                $item['priceMenu'] = $item['priceMenu'] !== null ? floatval($item['priceMenu']) : null;
            }

            // ✅ PRÉSERVER options spéciales de l'ancien menu.json
            $itemSlug = $item['slug'] ?? '';
            $itemId = $item['id'] ?? '';

            // Chercher dans l'ancien menu.json (essayer plusieurs correspondances)
            if (!empty($oldMenuData['menu']['categories'])) {
                foreach ($oldMenuData['menu']['categories'] as $oldCat) {
                    if (!empty($oldCat['items'])) {
                        foreach ($oldCat['items'] as $oldItem) {
                            $oldItemId = $oldItem['id'] ?? '';
                            $oldItemSlug = $oldItem['slug'] ?? '';

                            // Essayer plusieurs correspondances
                            $match = false;
                            if ($oldItemSlug && ($oldItemSlug === $itemSlug || $oldItemSlug === $itemId)) {
                                $match = true;
                            } elseif ($oldItemId && ($oldItemId === $itemSlug || $oldItemId === $itemId)) {
                                $match = true;
                            }

                            if ($match) {
                                // Préserver pâtisserieOptions
                                if (isset($oldItem['pâtisserieOptions'])) {
                                    $item['pâtisserieOptions'] = $oldItem['pâtisserieOptions'];
                                }
                                // Préserver beverageOptions
                                if (isset($oldItem['beverageOptions'])) {
                                    $item['beverageOptions'] = $oldItem['beverageOptions'];
                                }
                                // Préserver customizationNote
                                if (isset($oldItem['customizationNote'])) {
                                    $item['customizationNote'] = $oldItem['customizationNote'];
                                }
                                // Préserver requiresChoice
                                if (isset($oldItem['requiresChoice'])) {
                                    $item['requiresChoice'] = $oldItem['requiresChoice'];
                                }
                                // Préserver badge
                                if (isset($oldItem['badge'])) {
                                    $item['badge'] = $oldItem['badge'];
                                }
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }

    // ✅ FORMATER prix suppléments (déjà en DA, pas de conversion)
    $supplementsFormatted = [];
    foreach ($supplements as $slug => $supp) {
        if (isset($supp['price'])) {
            $supp['price'] = floatval($supp['price']);
        }
        $supplementsFormatted[$slug] = $supp;
    }

    // ✅ PRÉSERVER formules et featured depuis ancien menu.json
    $formules = $oldMenuData['formules'] ?? [];
    $featured = $oldMenuData['featured'] ?? [
        'enabled' => true,
        'title' => 'Sélection pour vous',
        'subtitle' => 'Nos produits les plus appréciés',
        'items' => []
    ];

    // Formater pour le frontend
    $menuData = [
        'version' => 1,
        'lastUpdated' => date('c'),
        'menu' => [
            'categories' => $categories
        ],
        'supplements' => [
            'catalog' => $supplementsFormatted,
            'defaultForCategories' => $categorySupplements
        ],
        'categoryIcons' => $categoryIcons,
        'formules' => $formules,
        'featured' => $featured
    ];

    // Écrire menu.json
    $json = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        throw new Exception('Erreur encodage JSON: ' . json_last_error_msg());
    }

    if (file_put_contents($menuJsonPath, $json) === false) {
        throw new Exception('Impossible d\'écrire menu.json');
    }

    echo "✅ menu.json généré avec succès (" . strlen($json) . " octets)\n";
    echo "   - " . count($categories) . " catégories\n";
    echo "   - " . count($supplementsFormatted) . " suppléments\n";
    echo "   - " . count($formules) . " formules préservées\n";

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
