#!/usr/bin/env php
<?php
/**
 * SnackApp v2 - Migration JSON → MySQL
 * Phase 3 : Migration catégories, produits, suppléments
 *
 * Usage:
 *   php database/migrate-json-to-mysql.php [--dry-run] [--restaurant-id=1]
 *
 * Options:
 *   --dry-run        Mode test (aucune écriture BDD)
 *   --restaurant-id  ID du restaurant cible (défaut: 1)
 *   --force          Écraser les données existantes
 */

// =============================================
// CONFIGURATION
// =============================================

define('ROOT_DIR', dirname(__DIR__));
define('BACKUP_DIR', ROOT_DIR . '/backup');
define('MENU_JSON', ROOT_DIR . '/config/menu.json');
define('MENU_RUNTIME_JSON', ROOT_DIR . '/config/menu.runtime.json');

// Couleurs pour le terminal
define('C_RESET', "\033[0m");
define('C_GREEN', "\033[32m");
define('C_YELLOW', "\033[33m");
define('C_RED', "\033[31m");
define('C_BLUE', "\033[34m");
define('C_CYAN', "\033[36m");
define('C_BOLD', "\033[1m");

// =============================================
// ARGUMENTS CLI
// =============================================

$options = [
    'dry_run' => false,
    'restaurant_id' => 1,
    'force' => false
];

foreach ($argv as $arg) {
    if ($arg === '--dry-run') {
        $options['dry_run'] = true;
    } elseif (strpos($arg, '--restaurant-id=') === 0) {
        $options['restaurant_id'] = (int) substr($arg, 16);
    } elseif ($arg === '--force') {
        $options['force'] = true;
    }
}

// =============================================
// CHARGEMENT DEPENDENCIES
// =============================================

require_once ROOT_DIR . '/database/Database.php';
require_once ROOT_DIR . '/database/repositories/CategoryRepository.php';
require_once ROOT_DIR . '/database/repositories/ProductRepository.php';
require_once ROOT_DIR . '/database/repositories/SupplementRepository.php';

// Initialisation Database (sauf en mode dry-run)
if (!$options['dry_run']) {
    $dbConfig = require ROOT_DIR . '/database/config.php';
    Database::init($dbConfig['database']);
}

// =============================================
// FONCTIONS UTILITAIRES
// =============================================

function log_info($message) {
    echo C_BLUE . "[INFO] " . C_RESET . $message . PHP_EOL;
}

function log_success($message) {
    echo C_GREEN . "[OK]   " . C_RESET . $message . PHP_EOL;
}

function log_warning($message) {
    echo C_YELLOW . "[WARN] " . C_RESET . $message . PHP_EOL;
}

function log_error($message) {
    echo C_RED . "[ERROR] " . C_RESET . $message . PHP_EOL;
}

function log_step($step, $message) {
    echo C_CYAN . C_BOLD . "\n=== ÉTAPE $step: $message ===" . C_RESET . PHP_EOL;
}

function format_price($cents) {
    return number_format($cents / 100, 2) . '€';
}

// =============================================
// CLASSE PRINCIPALE
// =============================================

class JsonToMySQLMigrator {
    private $restaurantId;
    private $dryRun;
    private $force;

    // Statistiques
    private $stats = [
        'categories_created' => 0,
        'categories_skipped' => 0,
        'products_created' => 0,
        'products_skipped' => 0,
        'supplements_created' => 0,
        'supplements_skipped' => 0,
        'links_created' => 0,
        'links_skipped' => 0
    ];

    // Mapping slug → ID pour les relations
    private $categoryMapping = [];
    private $productMapping = [];
    private $supplementMapping = [];

    public function __construct($restaurantId, $dryRun = false, $force = false) {
        $this->restaurantId = $restaurantId;
        $this->dryRun = $dryRun;
        $this->force = $force;
    }

    /**
     * Exécute la migration complète
     */
    public function run() {
        $this->printHeader();

        try {
            // Étape 0 : Backup
            log_step(0, "Sauvegarde des fichiers JSON");
            $this->backupJsonFiles();

            // Étape 1 : Lecture des fichiers
            log_step(1, "Lecture des fichiers JSON");
            $menuData = $this->loadMenuJson();

            // Étape 2 : Migration catégories
            log_step(2, "Migration des catégories");
            $this->migrateCategories($menuData['menu']['categories']);

            // Étape 3 : Migration produits
            log_step(3, "Migration des produits");
            $this->migrateProducts($menuData['menu']['categories']);

            // Étape 4 : Migration suppléments
            log_step(4, "Migration des suppléments");
            $this->migrateSupplements($menuData['supplements']['catalog']);

            // Étape 5 : Migration liaisons produits/suppléments
            log_step(5, "Migration des liaisons produits/suppléments");
            $this->migrateProductSupplements($menuData['menu']['categories']);

            // Étape 6 : Vérifications finales
            log_step(6, "Vérifications finales");
            $this->verifyMigration();

            // Résumé
            $this->printSummary();

        } catch (Exception $e) {
            log_error("Migration échouée : " . $e->getMessage());
            log_error("Trace: " . $e->getTraceAsString());
            exit(1);
        }
    }

    /**
     * Sauvegarde les fichiers JSON
     */
    private function backupJsonFiles() {
        if ($this->dryRun) {
            log_info("Mode dry-run : skip backup");
            return;
        }

        // Créer le dossier backup s'il n'existe pas
        if (!is_dir(BACKUP_DIR)) {
            mkdir(BACKUP_DIR, 0755, true);
            log_success("Dossier backup/ créé");
        }

        $timestamp = date('Y-m-d_H-i-s');

        // Backup menu.json
        if (file_exists(MENU_JSON)) {
            $backupPath = BACKUP_DIR . "/menu_{$timestamp}.json";
            copy(MENU_JSON, $backupPath);
            log_success("Sauvegardé : " . basename($backupPath));
        }

        // Backup menu.runtime.json
        if (file_exists(MENU_RUNTIME_JSON)) {
            $backupPath = BACKUP_DIR . "/menu.runtime_{$timestamp}.json";
            copy(MENU_RUNTIME_JSON, $backupPath);
            log_success("Sauvegardé : " . basename($backupPath));
        }
    }

    /**
     * Charge le fichier menu.json
     */
    private function loadMenuJson() {
        if (!file_exists(MENU_JSON)) {
            throw new Exception("Fichier menu.json introuvable : " . MENU_JSON);
        }

        $content = file_get_contents(MENU_JSON);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erreur parsing menu.json : " . json_last_error_msg());
        }

        log_success("Fichier menu.json chargé");
        log_info("  Version: " . ($data['version'] ?? 'N/A'));
        log_info("  Dernière mise à jour: " . ($data['lastUpdated'] ?? 'N/A'));

        return $data;
    }

    /**
     * Migre les catégories
     */
    private function migrateCategories($categories) {
        log_info("Nombre de catégories à migrer : " . count($categories));

        foreach ($categories as $index => $category) {
            $slug = $category['id'];

            if ($this->dryRun) {
                log_info("[DRY-RUN] Créerait catégorie : {$category['name']} ($slug)");
                $this->stats['categories_created']++;
                $this->categoryMapping[$slug] = 999; // Fake ID
                continue;
            }

            // Vérifier si existe déjà
            $existing = CategoryRepository::getBySlug($this->restaurantId, $slug);

            if ($existing && !$this->force) {
                log_warning("Catégorie existe déjà : $slug (ID: {$existing['id']})");
                $this->stats['categories_skipped']++;
                $this->categoryMapping[$slug] = $existing['id'];
                continue;
            }

            // Créer la catégorie
            try {
                $categoryId = CategoryRepository::create($this->restaurantId, [
                    'name' => $category['name'],
                    'slug' => $slug,
                    'description' => $category['description'] ?? null,
                    'image' => $category['image'] ?? null,
                    'sort_order' => $index,
                    'is_active' => 1
                ]);

                $this->categoryMapping[$slug] = $categoryId;
                $this->stats['categories_created']++;

                log_success("Catégorie créée : {$category['name']} (ID: $categoryId)");
            } catch (Exception $e) {
                log_error("Erreur création catégorie $slug : " . $e->getMessage());
            }
        }
    }

    /**
     * Migre les produits
     */
    private function migrateProducts($categories) {
        $totalProducts = 0;
        foreach ($categories as $category) {
            $totalProducts += count($category['items'] ?? []);
        }

        log_info("Nombre de produits à migrer : $totalProducts");

        foreach ($categories as $category) {
            $categorySlug = $category['id'];

            if (!isset($this->categoryMapping[$categorySlug])) {
                log_warning("Catégorie $categorySlug non mappée, skip produits");
                continue;
            }

            $categoryId = $this->categoryMapping[$categorySlug];

            foreach ($category['items'] as $index => $product) {
                $slug = $product['id'];

                // Préparer options_config si nécessaire
                $optionsConfig = null;
                if (isset($product['viennoiserieOptions']) ||
                    isset($product['beverageOptions']) ||
                    isset($product['sauceOptions']) ||
                    isset($product['accompagnementOptions'])) {

                    $optionsConfig = [];
                    if (isset($product['viennoiserieOptions'])) {
                        $optionsConfig['viennoiserie'] = $product['viennoiserieOptions'];
                    }
                    if (isset($product['beverageOptions'])) {
                        $optionsConfig['beverage'] = $product['beverageOptions'];
                    }
                    if (isset($product['sauceOptions'])) {
                        $optionsConfig['sauce'] = $product['sauceOptions'];
                    }
                    if (isset($product['accompagnementOptions'])) {
                        $optionsConfig['accompagnement'] = $product['accompagnementOptions'];
                    }
                }

                if ($this->dryRun) {
                    $price = format_price($product['priceSolo']);
                    log_info("[DRY-RUN] Créerait produit : {$product['name']} ($slug) - $price");
                    $this->stats['products_created']++;
                    $this->productMapping[$slug] = 999; // Fake ID
                    continue;
                }

                // Vérifier si existe déjà
                $existing = ProductRepository::getBySlug($this->restaurantId, $slug);

                if ($existing && !$this->force) {
                    log_warning("Produit existe déjà : $slug (ID: {$existing['id']})");
                    $this->stats['products_skipped']++;
                    $this->productMapping[$slug] = $existing['id'];
                    continue;
                }

                // Créer le produit
                try {
                    $productId = ProductRepository::create($this->restaurantId, $categoryId, [
                        'name' => $product['name'],
                        'slug' => $slug,
                        'description' => $product['description'] ?? null,
                        'image' => $product['image'] ?? null,
                        'price_solo' => $product['priceSolo'] / 100, // Centimes → euros
                        'price_menu' => isset($product['priceMenu']) ? $product['priceMenu'] / 100 : null,
                        'status' => $product['status'] ?? 'available',
                        'options_config' => $optionsConfig,
                        'sort_order' => $index
                    ]);

                    $this->productMapping[$slug] = $productId;
                    $this->stats['products_created']++;

                    $price = format_price($product['priceSolo']);
                    log_success("Produit créé : {$product['name']} (ID: $productId) - $price");
                } catch (Exception $e) {
                    log_error("Erreur création produit $slug : " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Migre les suppléments
     */
    private function migrateSupplements($supplementsCatalog) {
        log_info("Nombre de suppléments à migrer : " . count($supplementsCatalog));

        $index = 0;
        foreach ($supplementsCatalog as $supplementId => $supplement) {
            if ($this->dryRun) {
                $price = format_price($supplement['price']);
                log_info("[DRY-RUN] Créerait supplément : {$supplement['name']} - $price");
                $this->stats['supplements_created']++;
                $this->supplementMapping[$supplementId] = 999; // Fake ID
                $index++;
                continue;
            }

            // Vérifier si existe déjà (par nom car pas de slug unique dans supplements)
            $existingSupplements = SupplementRepository::getAll($this->restaurantId);
            $existing = null;
            foreach ($existingSupplements as $sup) {
                if ($sup['name'] === $supplement['name']) {
                    $existing = $sup;
                    break;
                }
            }

            if ($existing && !$this->force) {
                log_warning("Supplément existe déjà : {$supplement['name']} (ID: {$existing['id']})");
                $this->stats['supplements_skipped']++;
                $this->supplementMapping[$supplementId] = $existing['id'];
                $index++;
                continue;
            }

            // Créer le supplément
            try {
                $suppId = SupplementRepository::create($this->restaurantId, [
                    'name' => $supplement['name'],
                    'price' => $supplement['price'] / 100, // Centimes → euros
                    'status' => $supplement['status'] ?? 'available',
                    'sort_order' => $index
                ]);

                $this->supplementMapping[$supplementId] = $suppId;
                $this->stats['supplements_created']++;

                $price = format_price($supplement['price']);
                log_success("Supplément créé : {$supplement['name']} (ID: $suppId) - $price");
            } catch (Exception $e) {
                log_error("Erreur création supplément {$supplement['name']} : " . $e->getMessage());
            }

            $index++;
        }
    }

    /**
     * Migre les liaisons produits/suppléments
     */
    private function migrateProductSupplements($categories) {
        log_info("Migration des liaisons produits/suppléments...");

        foreach ($categories as $category) {
            foreach ($category['items'] as $product) {
                $productSlug = $product['id'];

                if (!isset($this->productMapping[$productSlug])) {
                    continue;
                }

                $productId = $this->productMapping[$productSlug];

                // Vérifier si le produit a des suppléments
                if (!isset($product['supplements']) || empty($product['supplements'])) {
                    continue;
                }

                foreach ($product['supplements'] as $supplementId) {
                    if (!isset($this->supplementMapping[$supplementId])) {
                        log_warning("Supplément $supplementId non mappé pour produit $productSlug");
                        continue;
                    }

                    $suppId = $this->supplementMapping[$supplementId];

                    if ($this->dryRun) {
                        log_info("[DRY-RUN] Créerait liaison : Produit $productSlug ↔ Supplément $supplementId");
                        $this->stats['links_created']++;
                        continue;
                    }

                    // Créer la liaison
                    try {
                        $success = SupplementRepository::attachToProduct($suppId, $productId);

                        if ($success) {
                            $this->stats['links_created']++;
                        } else {
                            $this->stats['links_skipped']++;
                        }
                    } catch (Exception $e) {
                        log_warning("Erreur liaison produit/supplément : " . $e->getMessage());
                    }
                }
            }
        }

        if ($this->stats['links_created'] > 0) {
            log_success("Liaisons créées : {$this->stats['links_created']}");
        }
        if ($this->stats['links_skipped'] > 0) {
            log_warning("Liaisons skipped : {$this->stats['links_skipped']}");
        }
    }

    /**
     * Vérifications finales
     */
    private function verifyMigration() {
        if ($this->dryRun) {
            log_info("Mode dry-run : skip vérifications");
            return;
        }

        // Compter les catégories
        $categories = CategoryRepository::getAll($this->restaurantId);
        log_info("✓ Catégories en BDD : " . count($categories));

        // Compter les produits
        $products = ProductRepository::getAll($this->restaurantId);
        log_info("✓ Produits en BDD : " . count($products));

        // Compter les suppléments
        $supplements = SupplementRepository::getAll($this->restaurantId);
        log_info("✓ Suppléments en BDD : " . count($supplements));

        // Compter les liaisons
        $links = SupplementRepository::getAllProductSupplements($this->restaurantId);
        log_info("✓ Liaisons produits/suppléments : " . count($links));

        log_success("Vérifications terminées");
    }

    /**
     * Affiche le header
     */
    private function printHeader() {
        echo C_CYAN . C_BOLD;
        echo "\n";
        echo "╔════════════════════════════════════════════════╗\n";
        echo "║   SNACKAPP - MIGRATION JSON → MYSQL (Phase 3)  ║\n";
        echo "╚════════════════════════════════════════════════╝\n";
        echo C_RESET;

        echo "\n";
        echo "Restaurant ID : " . C_BOLD . $this->restaurantId . C_RESET . "\n";
        echo "Mode          : " . ($this->dryRun ? C_YELLOW . "DRY-RUN" : C_GREEN . "PRODUCTION") . C_RESET . "\n";
        echo "Force         : " . ($this->force ? C_RED . "OUI" : "NON") . C_RESET . "\n";
        echo "\n";
    }

    /**
     * Affiche le résumé
     */
    private function printSummary() {
        echo "\n";
        echo C_CYAN . C_BOLD . "╔════════════════════════════════════════════════╗" . C_RESET . "\n";
        echo C_CYAN . C_BOLD . "║              RÉSUMÉ DE LA MIGRATION             ║" . C_RESET . "\n";
        echo C_CYAN . C_BOLD . "╚════════════════════════════════════════════════╝" . C_RESET . "\n";
        echo "\n";

        echo C_GREEN . "Catégories créées   : " . C_RESET . $this->stats['categories_created'] . "\n";
        echo C_YELLOW . "Catégories skipped  : " . C_RESET . $this->stats['categories_skipped'] . "\n";
        echo "\n";

        echo C_GREEN . "Produits créés      : " . C_RESET . $this->stats['products_created'] . "\n";
        echo C_YELLOW . "Produits skipped    : " . C_RESET . $this->stats['products_skipped'] . "\n";
        echo "\n";

        echo C_GREEN . "Suppléments créés   : " . C_RESET . $this->stats['supplements_created'] . "\n";
        echo C_YELLOW . "Suppléments skipped : " . C_RESET . $this->stats['supplements_skipped'] . "\n";
        echo "\n";

        echo C_GREEN . "Liaisons créées     : " . C_RESET . $this->stats['links_created'] . "\n";
        echo C_YELLOW . "Liaisons skipped    : " . C_RESET . $this->stats['links_skipped'] . "\n";
        echo "\n";

        if ($this->dryRun) {
            echo C_YELLOW . C_BOLD . "⚠ MODE DRY-RUN : Aucune modification en BDD" . C_RESET . "\n";
        } else {
            echo C_GREEN . C_BOLD . "✓ MIGRATION TERMINÉE AVEC SUCCÈS" . C_RESET . "\n";
        }
        echo "\n";
    }
}

// =============================================
// EXECUTION
// =============================================

try {
    $migrator = new JsonToMySQLMigrator(
        $options['restaurant_id'],
        $options['dry_run'],
        $options['force']
    );

    $migrator->run();

} catch (Exception $e) {
    log_error("Erreur fatale : " . $e->getMessage());
    exit(1);
}

exit(0);
