<?php
/**
 * Configuration Admin Panel - Snackup v2 (MySQL)
 * Charge la configuration depuis l'instance et se connecte à MySQL
 */

session_start();

// Charger le gestionnaire d'instances (architecture scalable)
require_once __DIR__ . '/../backend/InstanceManager.php';

// Détecter automatiquement l'instance selon le domaine
try {
    $instanceConfig = InstanceManager::loadConfig();
    $instanceName = InstanceManager::getCurrentInstance();
} catch (Exception $e) {
    die('❌ Erreur: Impossible de charger la configuration de l\'instance: ' . $e->getMessage());
}

// Définir les constantes depuis la config
define('INSTANCE_NAME', $instanceName);
define('DB_HOST', $instanceConfig['database']['host']);
define('DB_NAME', $instanceConfig['database']['name']);
define('DB_USER', $instanceConfig['database']['user']);
define('DB_PASS', $instanceConfig['database']['password']);
define('DB_CHARSET', $instanceConfig['database']['charset']);

define('RESTAURANT_ID', $instanceConfig['app']['restaurant_id']);
define('APP_NAME', $instanceConfig['app']['name']);
define('APP_SLUG', $instanceConfig['app']['instance_id']);
define('TIMEZONE', $instanceConfig['app']['timezone']);
define('CURRENCY', $instanceConfig['app']['currency'] ?? 'DA');

// Chemins
define('SNACK_ROOT', __DIR__ . '/../..');  // Racine du projet (2 niveaux au-dessus de admin/)
define('UPLOADS_DIR', __DIR__ . '/../../images/');
define('DATA_DIR', __DIR__ . '/data/');
define('MENU_RUNTIME_FILE', __DIR__ . '/../config/menu.runtime.json');

// Créer le dossier data s'il n'existe pas
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}

// Créer le dossier uploads s'il n'existe pas
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

// Charger la classe Database
require_once __DIR__ . '/../backend/Database.php';

// Initialiser la connexion Database avec les credentials
Database::init($instanceConfig['database']);

/**
 * Obtenir une connexion à la base de données
 */
function getDatabase() {
    static $db = null;
    if ($db === null) {
        $db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET);
    }
    return $db;
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function requireLogin() {
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Obtenir l'utilisateur connecté
 */
function getCurrentUser() {
    if (!isset($_SESSION['admin_user_id'])) {
        return null;
    }

    $db = getDatabase();
    $stmt = $db->prepare('SELECT id, username, role FROM admin_users WHERE id = ?');
    $stmt->execute([$_SESSION['admin_user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Charger la configuration du restaurant depuis la base de données
 */
function loadRestaurantConfig() {
    $db = getDatabase();

    // Récupérer les infos du restaurant
    $stmt = $db->prepare('
        SELECT r.*, rs.*
        FROM restaurants r
        LEFT JOIN restaurant_settings rs ON r.id = rs.restaurant_id
        WHERE r.id = ?
    ');
    $stmt->execute([RESTAURANT_ID]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$restaurant) {
        die('❌ Restaurant introuvable (ID: ' . RESTAURANT_ID . ')');
    }

    return $restaurant;
}

/**
 * Charger les catégories du restaurant
 */
function loadCategories() {
    $db = getDatabase();
    $stmt = $db->prepare('
        SELECT * FROM categories
        WHERE restaurant_id = ? AND is_active = 1
        ORDER BY sort_order ASC
    ');
    $stmt->execute([RESTAURANT_ID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Charger les produits d'une catégorie
 */
function loadProducts($categoryId = null) {
    $db = getDatabase();

    if ($categoryId) {
        $stmt = $db->prepare('
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.restaurant_id = ? AND p.category_id = ?
            ORDER BY p.sort_order ASC
        ');
        $stmt->execute([RESTAURANT_ID, $categoryId]);
    } else {
        $stmt = $db->prepare('
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.restaurant_id = ?
            ORDER BY c.sort_order ASC, p.sort_order ASC
        ');
        $stmt->execute([RESTAURANT_ID]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fonction de debug (à désactiver en production)
 */
function debug_log($message, $data = null) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('[ADMIN DEBUG] ' . $message);
        if ($data !== null) {
            error_log(print_r($data, true));
        }
    }
}

/**
 * Charger le menu runtime (compatibilité avec ancien système JSON)
 * Utilisé pendant la période de transition MySQL -> JSON
 */
function loadMenuRuntime() {
    $path = MENU_RUNTIME_FILE;
    if (!file_exists($path)) {
        return [
            'products' => [],
            'categories' => [],
            'customCategories' => [],
            'customProducts' => [],
            'deletedProducts' => [],
            'deletedCategories' => []
        ];
    }
    $raw = @file_get_contents($path);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) $data = [];

    // Normaliser la forme
    $data['products'] = isset($data['products']) && is_array($data['products']) ? $data['products'] : [];
    $data['categories'] = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];
    $data['customCategories'] = isset($data['customCategories']) && is_array($data['customCategories']) ? $data['customCategories'] : [];
    $data['customProducts'] = isset($data['customProducts']) && is_array($data['customProducts']) ? $data['customProducts'] : [];
    $data['deletedProducts'] = isset($data['deletedProducts']) && is_array($data['deletedProducts']) ? $data['deletedProducts'] : [];
    $data['deletedCategories'] = isset($data['deletedCategories']) && is_array($data['deletedCategories']) ? $data['deletedCategories'] : [];

    return $data;
}

/**
 * Sauvegarder le menu runtime (compatibilité avec ancien système JSON)
 * Utilisé pendant la période de transition MySQL -> JSON
 */
function saveMenuRuntime($runtime, $autoSync = false) {
    $runtime = is_array($runtime) ? $runtime : [];
    $runtime['products'] = isset($runtime['products']) && is_array($runtime['products']) ? $runtime['products'] : [];
    $runtime['categories'] = isset($runtime['categories']) && is_array($runtime['categories']) ? $runtime['categories'] : [];
    $runtime['customCategories'] = isset($runtime['customCategories']) && is_array($runtime['customCategories']) ? $runtime['customCategories'] : [];
    $runtime['customProducts'] = isset($runtime['customProducts']) && is_array($runtime['customProducts']) ? $runtime['customProducts'] : [];

    $json = json_encode($runtime, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new Exception('Impossible d\'encoder le runtime JSON');
    }

    $dir = dirname(MENU_RUNTIME_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Écriture atomique + lock
    $tmp = MENU_RUNTIME_FILE . '.tmp';
    $fp = fopen($tmp, 'wb');
    if (!$fp) {
        throw new Exception('Impossible d\'ouvrir le fichier temporaire runtime');
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new Exception('Impossible de verrouiller le fichier runtime');
    }

    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    if (!rename($tmp, MENU_RUNTIME_FILE)) {
        @unlink($tmp);
        throw new Exception('Impossible de déplacer le fichier runtime');
    }

    return true;
}

// Définir le timezone
date_default_timezone_set(TIMEZONE);

// Configuration Stripe (si disponible)
if (isset($instanceConfig['stripe'])) {
    define('STRIPE_ENABLED', $instanceConfig['stripe']['enabled'] ?? false);
    define('STRIPE_PUBLISHABLE_KEY', $instanceConfig['stripe']['publishable_key'] ?? '');
    define('STRIPE_SECRET_KEY', $instanceConfig['stripe']['secret_key'] ?? '');
}
