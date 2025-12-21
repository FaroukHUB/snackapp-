<?php
/**
 * SnackApp v1 - Bootstrap
 * À inclure au début de chaque fichier PHP
 */

// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Chemins
define('SNACK_ROOT', dirname(__DIR__));
define('SNACK_DB_PATH', SNACK_ROOT . '/database');
define('SNACK_CONFIG_PATH', SNACK_ROOT . '/config');
define('SNACK_ADMIN_PATH', __DIR__);

// Charger les fonctions de config (loadMenuRuntime, saveMenuRuntime, etc.)
require_once __DIR__ . '/config.php';

// Charger les classes database
require_once SNACK_DB_PATH . '/Database.php';
require_once SNACK_DB_PATH . '/repositories/RestaurantRepository.php';
require_once SNACK_DB_PATH . '/repositories/MenuRepository.php';
require_once SNACK_DB_PATH . '/repositories/OrderRepository.php';
require_once SNACK_DB_PATH . '/repositories/CustomerRepository.php';

// Charger la config database
$dbConfigFile = SNACK_DB_PATH . '/config.php';
if (!file_exists($dbConfigFile)) {
    // Fallback: mode JSON (compatibilité)
    define('SNACK_USE_JSON', true);
} else {
    define('SNACK_USE_JSON', false);
    $dbConfig = require $dbConfigFile;

    try {
        Database::init($dbConfig['database']);
        // Tester la connexion
        Database::getInstance();
    } catch (Exception $e) {
        error_log("SnackApp DB Error: " . $e->getMessage());
        // Fallback JSON si la DB échoue
        define('SNACK_DB_ERROR', $e->getMessage());
    }
}

// Restaurant actuel (pour multi-tenant)
// Par défaut: Fabrik Burger (ID 1)
if (!defined('SNACK_RESTAURANT_ID')) {
    // Essayer de récupérer depuis la session ou le domaine
    if (isset($_SESSION['restaurant_id'])) {
        define('SNACK_RESTAURANT_ID', $_SESSION['restaurant_id']);
    } else {
        // TODO: Détecter depuis le domaine pour multi-tenant
        define('SNACK_RESTAURANT_ID', 1);
    }
}

/**
 * Helper: Récupérer le restaurant actuel
 */
function getCurrentRestaurant(): ?array {
    static $restaurant = null;
    if ($restaurant === null && !SNACK_USE_JSON) {
        $restaurant = RestaurantRepository::getById(SNACK_RESTAURANT_ID);
    }
    return $restaurant;
}

/**
 * Helper: Récupérer les settings du restaurant
 */
function getRestaurantSettings(): ?array {
    static $settings = null;
    if ($settings === null && !SNACK_USE_JSON) {
        $settings = RestaurantRepository::getSettings(SNACK_RESTAURANT_ID);
    }
    return $settings;
}

/**
 * Helper: Vérifier si l'admin est connecté
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Helper: Rediriger si non connecté
 */
function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Helper: Réponse JSON pour les API
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Helper: Erreur JSON
 */
function jsonError(string $message, int $statusCode = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

/**
 * Helper: Succès JSON
 */
function jsonSuccess(array $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

/**
 * CSRF Protection
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function getCsrfToken(): string {
    return $_SESSION['csrf_token'] ?? generateCsrfToken();
}

function validateCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCsrfToken($token)) {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json')) {
            jsonError('Token CSRF invalide', 403);
        }
        http_response_code(403);
        die('Token CSRF invalide');
    }
}

/**
 * Helper: Escape HTML pour éviter XSS
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
