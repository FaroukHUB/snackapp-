<?php
/**
 * SnackApp v1 - Script de Migration JSON → MySQL
 *
 * Usage:
 *   php migrate.php
 *
 * Ce script:
 * 1. Crée les tables si elles n'existent pas
 * 2. Importe les données depuis les fichiers JSON existants
 * 3. Préserve toutes les données (commandes, clients, menu)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "  SnackApp - Migration JSON → MySQL\n";
echo "========================================\n\n";

// Charger la config
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    die("❌ Erreur: config.php introuvable.\n   Copiez config.example.php vers config.php et configurez vos accès MySQL.\n");
}

$config = require $configFile;
require_once __DIR__ . '/Database.php';

// Chemins des fichiers JSON
$basePath = dirname(__DIR__);
$jsonPaths = [
    'menu_runtime' => $basePath . '/config/menu.runtime.json',
    'menu' => $basePath . '/config/menu.json',
    'restaurant' => $basePath . '/config/restaurant.json',
    'orders' => $basePath . '/admin-panel-v2/data/orders.json',
    'orders_archive' => $basePath . '/admin-panel-v2/data/orders-archive.json',
    'customers' => $basePath . '/admin-panel-v2/data/customers.json',
    'settings' => $basePath . '/admin-panel-v2/data/settings.json',
    'restaurant_status' => $basePath . '/admin-panel-v2/data/restaurant-status.json',
];

// Helper: Charger JSON
function loadJson(string $path): ?array {
    if (!file_exists($path)) return null;
    $content = file_get_contents($path);
    return json_decode($content, true);
}

// Helper: Générer un slug
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text) ?: 'item';
}

try {
    // Connexion
    echo "📡 Connexion à MySQL...\n";
    Database::init($config['database']);
    $pdo = Database::getInstance();
    echo "✅ Connecté à {$config['database']['dbname']}\n\n";

    // Créer les tables
    echo "📦 Création des tables...\n";
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($schema);
    echo "✅ Tables créées/vérifiées\n\n";

    // Démarrer la migration
    Database::transaction(function($pdo) use ($config, $jsonPaths) {

        $restaurantSlug = $config['default_restaurant']['slug'] ?? 'fabrik-burger';
        $restaurantName = $config['default_restaurant']['name'] ?? 'Fabrik Burger';

        // 1. Créer le restaurant
        echo "🏪 Migration du restaurant...\n";

        $existing = Database::fetchOne("SELECT id FROM restaurants WHERE slug = ?", [$restaurantSlug]);

        if ($existing) {
            $restaurantId = $existing['id'];
            echo "   Restaurant existant (ID: {$restaurantId})\n";
        } else {
            $restaurantId = Database::insert('restaurants', [
                'slug' => $restaurantSlug,
                'name' => $restaurantName,
                'primary_color' => '#c58a3a',
                'is_active' => 1
            ]);
            echo "   Restaurant créé (ID: {$restaurantId})\n";
        }

        // 2. Settings du restaurant
        $restaurantJson = loadJson($jsonPaths['restaurant']);
        $statusJson = loadJson($jsonPaths['restaurant_status']);

        if ($restaurantJson) {
            echo "⚙️  Migration des paramètres...\n";

            $settingsData = [
                'restaurant_id' => $restaurantId,
                'phone' => $restaurantJson['contact']['phone'] ?? null,
                'whatsapp_number' => $restaurantJson['contact']['whatsappOrdersNumber'] ?? null,
                'whatsapp_token' => $restaurantJson['whatsapp']['token'] ?? null,
                'whatsapp_phone_id' => $restaurantJson['whatsapp']['phoneNumberId'] ?? null,
                'whatsapp_business_id' => $restaurantJson['whatsapp']['businessAccountId'] ?? null,
                'address' => $restaurantJson['location']['address'] ?? null,
                'city' => $restaurantJson['location']['city'] ?? null,
                'postal_code' => $restaurantJson['location']['postalCode'] ?? null,
                'instagram' => $restaurantJson['social']['instagram'] ?? null,
                'facebook' => $restaurantJson['social']['facebook'] ?? null,
                'accepting_orders' => ($statusJson['accepting_orders'] ?? true) ? 1 : 0
            ];

            // Upsert settings
            $existingSettings = Database::fetchOne(
                "SELECT id FROM restaurant_settings WHERE restaurant_id = ?",
                [$restaurantId]
            );

            if ($existingSettings) {
                Database::update('restaurant_settings', $settingsData, ['restaurant_id' => $restaurantId]);
            } else {
                Database::insert('restaurant_settings', $settingsData);
            }
            echo "   ✅ Paramètres importés\n";

            // Horaires
            if (!empty($restaurantJson['openingHours'])) {
                echo "🕐 Migration des horaires...\n";
                Database::query("DELETE FROM opening_hours WHERE restaurant_id = ?", [$restaurantId]);

                $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
                foreach ($restaurantJson['openingHours'] as $i => $hour) {
                    Database::insert('opening_hours', [
                        'restaurant_id' => $restaurantId,
                        'day_of_week' => $i,
                        'opens' => $hour['opens'] ?? '18:30',
                        'closes' => $hour['closes'] ?? '23:30',
                        'is_closed' => 0
                    ]);
                }
                echo "   ✅ " . count($restaurantJson['openingHours']) . " jours importés\n";
            }

            // FAQ
            if (!empty($restaurantJson['faq']['items'])) {
                echo "❓ Migration FAQ...\n";
                Database::query("DELETE FROM faq WHERE restaurant_id = ?", [$restaurantId]);

                foreach ($restaurantJson['faq']['items'] as $i => $faq) {
                    Database::insert('faq', [
                        'restaurant_id' => $restaurantId,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                        'sort_order' => $i
                    ]);
                }
                echo "   ✅ " . count($restaurantJson['faq']['items']) . " questions importées\n";
            }
        }

        // 3. Menu (catégories, produits, suppléments)
        $menuJson = loadJson($jsonPaths['menu_runtime']) ?? loadJson($jsonPaths['menu']);

        if ($menuJson && !empty($menuJson['menu']['categories'])) {
            echo "🍔 Migration du menu...\n";

            // Suppléments d'abord
            $supplementMap = []; // ancien ID => nouveau ID
            if (!empty($menuJson['supplements']['catalog'])) {
                echo "   📦 Suppléments...\n";

                foreach ($menuJson['supplements']['catalog'] as $supId => $sup) {
                    $existingSup = Database::fetchOne(
                        "SELECT id FROM supplements WHERE restaurant_id = ? AND name = ?",
                        [$restaurantId, $sup['name']]
                    );

                    if ($existingSup) {
                        $supplementMap[$supId] = $existingSup['id'];
                    } else {
                        $newId = Database::insert('supplements', [
                            'restaurant_id' => $restaurantId,
                            'name' => $sup['name'],
                            'price' => $sup['price'] ?? 0,
                            'status' => $sup['status'] ?? 'available'
                        ]);
                        $supplementMap[$supId] = $newId;
                    }
                }
                echo "      ✅ " . count($supplementMap) . " suppléments\n";
            }

            // Catégories et produits
            $categoryCount = 0;
            $productCount = 0;

            foreach ($menuJson['menu']['categories'] as $catIndex => $category) {
                $catSlug = slugify($category['name']);

                $existingCat = Database::fetchOne(
                    "SELECT id FROM categories WHERE restaurant_id = ? AND slug = ?",
                    [$restaurantId, $catSlug]
                );

                if ($existingCat) {
                    $categoryId = $existingCat['id'];
                } else {
                    $categoryId = Database::insert('categories', [
                        'restaurant_id' => $restaurantId,
                        'name' => $category['name'],
                        'slug' => $catSlug,
                        'description' => $category['description'] ?? null,
                        'image' => $category['image'] ?? null,
                        'sort_order' => $catIndex
                    ]);
                    $categoryCount++;
                }

                // Produits de cette catégorie
                if (!empty($category['items'])) {
                    foreach ($category['items'] as $prodIndex => $product) {
                        $prodSlug = slugify($product['name']);

                        $existingProd = Database::fetchOne(
                            "SELECT id FROM products WHERE restaurant_id = ? AND slug = ?",
                            [$restaurantId, $prodSlug]
                        );

                        if (!$existingProd) {
                            $productId = Database::insert('products', [
                                'restaurant_id' => $restaurantId,
                                'category_id' => $categoryId,
                                'name' => $product['name'],
                                'slug' => $prodSlug,
                                'description' => $product['description'] ?? null,
                                'image' => $product['image'] ?? null,
                                'price_solo' => $product['priceSolo'] ?? $product['price'] ?? 0,
                                'price_menu' => $product['priceMenu'] ?? null,
                                'status' => $product['status'] ?? 'available',
                                'sort_order' => $prodIndex
                            ]);
                            $productCount++;

                            // Lier les suppléments au produit
                            if (!empty($product['supplements'])) {
                                foreach ($product['supplements'] as $supId) {
                                    if (isset($supplementMap[$supId])) {
                                        Database::query(
                                            "INSERT IGNORE INTO product_supplements (product_id, supplement_id) VALUES (?, ?)",
                                            [$productId, $supplementMap[$supId]]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            echo "   ✅ {$categoryCount} catégories, {$productCount} produits importés\n";
        }

        // 4. Clients
        $customersJson = loadJson($jsonPaths['customers']);
        $customerMap = []; // phone => id

        if ($customersJson && !empty($customersJson)) {
            echo "👥 Migration des clients...\n";

            foreach ($customersJson as $customer) {
                $phone = $customer['phone'] ?? '';
                if (empty($phone)) continue;

                $existing = Database::fetchOne(
                    "SELECT id FROM customers WHERE restaurant_id = ? AND phone = ?",
                    [$restaurantId, $phone]
                );

                if ($existing) {
                    $customerMap[$phone] = $existing['id'];
                } else {
                    $customerId = Database::insert('customers', [
                        'restaurant_id' => $restaurantId,
                        'name' => $customer['name'] ?? 'Client',
                        'phone' => $phone,
                        'orders_count' => $customer['orders_count'] ?? 0,
                        'total_spent' => $customer['total_spent'] ?? 0,
                        'last_order_at' => !empty($customer['last_order']) ? $customer['last_order'] : null
                    ]);
                    $customerMap[$phone] = $customerId;
                }
            }
            echo "   ✅ " . count($customerMap) . " clients importés\n";
        }

        // 5. Commandes actives
        $ordersJson = loadJson($jsonPaths['orders']);

        if ($ordersJson && !empty($ordersJson)) {
            echo "📋 Migration des commandes actives...\n";
            $orderCount = 0;

            foreach ($ordersJson as $order) {
                // Vérifier si déjà importée
                $existingOrder = Database::fetchOne(
                    "SELECT id FROM orders WHERE restaurant_id = ? AND order_number = ?",
                    [$restaurantId, $order['id']]
                );

                if ($existingOrder) continue;

                $phone = $order['customer_phone'] ?? '';
                $customerId = $customerMap[$phone] ?? null;

                $orderId = Database::insert('orders', [
                    'restaurant_id' => $restaurantId,
                    'customer_id' => $customerId,
                    'order_number' => $order['id'],
                    'customer_name' => $order['customer_name'] ?? 'Client',
                    'customer_phone' => $phone,
                    'subtotal' => $order['subtotal'] ?? $order['total'] ?? 0,
                    'total' => $order['total'] ?? 0,
                    'status' => $order['status'] ?? 'pending',
                    'notes' => $order['notes'] ?? null,
                    'is_archived' => 0,
                    'created_at' => $order['created_at'] ?? date('Y-m-d H:i:s'),
                    'completed_at' => $order['completed_at'] ?? null
                ]);

                // Items de la commande
                if (!empty($order['items'])) {
                    foreach ($order['items'] as $item) {
                        $orderItemId = Database::insert('order_items', [
                            'order_id' => $orderId,
                            'product_name' => $item['name'],
                            'variant' => $item['variant'] ?? 'solo',
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['price'] ?? 0,
                            'total_price' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                        ]);

                        // Suppléments de l'item
                        if (!empty($item['supplements'])) {
                            foreach ($item['supplements'] as $sup) {
                                Database::insert('order_item_supplements', [
                                    'order_item_id' => $orderItemId,
                                    'supplement_name' => $sup['name'] ?? $sup,
                                    'price' => $sup['price'] ?? 0
                                ]);
                            }
                        }
                    }
                }
                $orderCount++;
            }
            echo "   ✅ {$orderCount} commandes actives importées\n";
        }

        // 6. Commandes archivées
        $archiveJson = loadJson($jsonPaths['orders_archive']);

        if ($archiveJson && !empty($archiveJson)) {
            echo "📦 Migration des archives...\n";
            $archiveCount = 0;

            foreach ($archiveJson as $order) {
                $existingOrder = Database::fetchOne(
                    "SELECT id FROM orders WHERE restaurant_id = ? AND order_number = ?",
                    [$restaurantId, $order['id']]
                );

                if ($existingOrder) continue;

                $phone = $order['customer_phone'] ?? '';
                $customerId = $customerMap[$phone] ?? null;

                $orderId = Database::insert('orders', [
                    'restaurant_id' => $restaurantId,
                    'customer_id' => $customerId,
                    'order_number' => $order['id'],
                    'customer_name' => $order['customer_name'] ?? 'Client',
                    'customer_phone' => $phone,
                    'subtotal' => $order['subtotal'] ?? $order['total'] ?? 0,
                    'total' => $order['total'] ?? 0,
                    'status' => 'completed',
                    'notes' => $order['notes'] ?? null,
                    'is_archived' => 1,
                    'created_at' => $order['created_at'] ?? date('Y-m-d H:i:s'),
                    'completed_at' => $order['completed_at'] ?? $order['created_at'] ?? null
                ]);

                if (!empty($order['items'])) {
                    foreach ($order['items'] as $item) {
                        Database::insert('order_items', [
                            'order_id' => $orderId,
                            'product_name' => $item['name'],
                            'variant' => $item['variant'] ?? 'solo',
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['price'] ?? 0,
                            'total_price' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
                        ]);
                    }
                }
                $archiveCount++;
            }
            echo "   ✅ {$archiveCount} commandes archivées importées\n";
        }

        // 7. Créer un admin par défaut
        $existingAdmin = Database::fetchOne(
            "SELECT id FROM admin_users WHERE restaurant_id = ?",
            [$restaurantId]
        );

        if (!$existingAdmin) {
            echo "🔐 Création admin par défaut...\n";
            Database::insert('admin_users', [
                'restaurant_id' => $restaurantId,
                'username' => 'admin',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'owner'
            ]);
            echo "   ✅ Admin créé (login: admin / pass: admin123)\n";
            echo "   ⚠️  CHANGEZ CE MOT DE PASSE !\n";
        }

        return $restaurantId;
    });

    echo "\n========================================\n";
    echo "  ✅ MIGRATION TERMINÉE AVEC SUCCÈS\n";
    echo "========================================\n";
    echo "\nProchaines étapes:\n";
    echo "1. Vérifier les données dans phpMyAdmin\n";
    echo "2. Tester l'admin panel\n";
    echo "3. Changer le mot de passe admin\n\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
