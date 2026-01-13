<?php
/**
 * Configuration Admin Panel - Snackup v2 (MySQL)
 * Charge la configuration depuis l'instance et se connecte à MySQL
 */

session_start();

// Déterminer quelle instance utiliser
// Pour l'instant on hardcode Atelier Pizza, mais on pourrait détecter depuis l'URL
define('INSTANCE_NAME', 'atelier-pizza');
define('INSTANCE_CONFIG_PATH', __DIR__ . '/../../instances/' . INSTANCE_NAME . '/backend-config.php');

// Charger la configuration de l'instance
if (!file_exists(INSTANCE_CONFIG_PATH)) {
    die('❌ Erreur: Fichier de configuration instance introuvable: ' . INSTANCE_CONFIG_PATH);
}

$instanceConfig = require INSTANCE_CONFIG_PATH;

// Définir les constantes depuis la config
define('DB_HOST', $instanceConfig['database']['host']);
define('DB_NAME', $instanceConfig['database']['name']);
define('DB_USER', $instanceConfig['database']['user']);
define('DB_PASS', $instanceConfig['database']['password']);
define('DB_CHARSET', $instanceConfig['database']['charset']);

define('RESTAURANT_ID', $instanceConfig['app']['restaurant_id']);
define('APP_NAME', $instanceConfig['app']['name']);
define('APP_SLUG', $instanceConfig['app']['instance_id']);
define('TIMEZONE', $instanceConfig['app']['timezone']);

// Chemins
define('UPLOADS_DIR', __DIR__ . '/../../images/');
define('DATA_DIR', __DIR__ . '/data/');

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

// Définir le timezone
date_default_timezone_set(TIMEZONE);

// Configuration Stripe (si disponible)
if (isset($instanceConfig['stripe'])) {
    define('STRIPE_ENABLED', $instanceConfig['stripe']['enabled'] ?? false);
    define('STRIPE_PUBLISHABLE_KEY', $instanceConfig['stripe']['publishable_key'] ?? '');
    define('STRIPE_SECRET_KEY', $instanceConfig['stripe']['secret_key'] ?? '');
}
