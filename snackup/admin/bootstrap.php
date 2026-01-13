<?php
/**
 * Bootstrap Admin Panel - Snackup v2 (MySQL)
 * À inclure au début de chaque fichier PHP qui nécessite l'authentification
 */

// 🔒 SÉCURITÉ: Configuration session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    // Détecter si HTTPS est actif
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    // Cookies de session sécurisés
    ini_set('session.cookie_httponly', '1');  // Protection XSS
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');    // HTTPS uniquement si disponible
    ini_set('session.cookie_samesite', 'Lax');  // Lax au lieu de Strict pour permettre navigation
    ini_set('session.use_strict_mode', '1');  // Rejeter sessions non initialisées
    session_start();
}

// 🔒 SÉCURITÉ: Headers de sécurité HTTP (appliqués globalement)
header('X-Frame-Options: DENY');  // Protection clickjacking
header('X-Content-Type-Options: nosniff');  // Protection MIME sniffing
// HSTS uniquement en HTTPS
if (isset($isHttps) && $isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');  // Force HTTPS
}
header('Referrer-Policy: strict-origin-when-cross-origin');  // Limite fuite d'infos
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');  // Permissions strictes

// Charger la configuration principale (qui charge l'instance et la DB)
require_once __DIR__ . '/config.php';

// Charger les repositories
require_once __DIR__ . '/../backend/repositories/RestaurantRepository.php';
require_once __DIR__ . '/../backend/repositories/MenuRepository.php';
require_once __DIR__ . '/../backend/repositories/OrderRepository.php';
require_once __DIR__ . '/../backend/repositories/CustomerRepository.php';
require_once __DIR__ . '/../backend/repositories/PromoCodeRepository.php';
require_once __DIR__ . '/../backend/repositories/LoyaltyRepository.php';

// Constantes pour le mode MySQL
define('SNACK_USE_JSON', false);
define('SNACK_RESTAURANT_ID', RESTAURANT_ID);

/**
 * Helper: Récupérer le restaurant actuel
 */
function getCurrentRestaurant(): ?array {
    static $restaurant = null;
    if ($restaurant === null) {
        $restaurant = RestaurantRepository::getById(SNACK_RESTAURANT_ID);
    }
    return $restaurant;
}

/**
 * Helper: Récupérer les settings du restaurant
 */
function getRestaurantSettings(): ?array {
    static $settings = null;
    if ($settings === null) {
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
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

/**
 * 🔒 SÉCURITÉ: Régénérer le token CSRF (à appeler après opérations sensibles)
 */
function regenerateCsrfToken(): string {
    unset($_SESSION['csrf_token']);
    return generateCsrfToken();
}

function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCsrfToken($token)) {
        // Détecter si c'est une requête API
        $isApiRequest = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')
                     || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
                     || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'json');

        if ($isApiRequest) {
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
