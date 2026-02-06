<?php
/**
 * Constantes globales - Snackup Admin
 * ====================================
 * Ce fichier centralise TOUTES les constantes et seuils de l'application.
 * IMPORTANT: À inclure avant config.php
 *
 * @author Claude
 * @version 1.0.0
 */

// ============================================================================
// TELEPHONE
// ============================================================================

/**
 * Indicatif téléphonique par défaut (Algérie)
 * Utilisé quand aucun indicatif n'est spécifié
 */
define('DEFAULT_PHONE_INDICATOR', '+213');

/**
 * Longueur minimale d'un numéro de téléphone (sans indicatif)
 */
define('MIN_PHONE_LENGTH', 8);

/**
 * Longueur maximale d'un numéro de téléphone (sans indicatif)
 */
define('MAX_PHONE_LENGTH', 15);

/**
 * Longueur minimale totale (avec indicatif)
 */
define('MIN_PHONE_TOTAL_LENGTH', 6);

// ============================================================================
// DEVISE
// ============================================================================

/**
 * Devise par défaut (sera surchargée par la config de l'instance)
 * @see config.php pour la définition finale de CURRENCY
 */
define('DEFAULT_CURRENCY', 'DA');

/**
 * Symboles de devises supportés
 */
define('CURRENCY_SYMBOLS', [
    'DA' => 'DA',      // Dinar Algérien
    'DZD' => 'DA',     // Alias ISO
    'EUR' => '€',      // Euro
    '€' => '€',        // Alias
    'USD' => '$',      // Dollar US
    '$' => '$',        // Alias
]);

// ============================================================================
// PAIEMENT
// ============================================================================

/**
 * Montant minimum pour le paiement en ligne (en unité de devise)
 */
define('MIN_ONLINE_PAYMENT_AMOUNT', 0.50);

/**
 * Montant minimum pour une commande
 */
define('MIN_ORDER_AMOUNT', 0);

// ============================================================================
// UTILISATEURS / AUTHENTIFICATION
// ============================================================================

/**
 * Longueur minimale du nom d'utilisateur
 */
define('MIN_USERNAME_LENGTH', 3);

/**
 * Longueur maximale du nom d'utilisateur
 */
define('MAX_USERNAME_LENGTH', 50);

/**
 * Longueur minimale du mot de passe
 */
define('MIN_PASSWORD_LENGTH', 6);

/**
 * Longueur du code PIN
 */
define('PIN_LENGTH', 4);

/**
 * Durée de session (en secondes) - 8 heures par défaut
 */
define('SESSION_LIFETIME', 8 * 60 * 60);

// ============================================================================
// LIMITES DE REQUETES / PAGINATION
// ============================================================================

/**
 * Nombre maximum de commandes actives à charger
 */
define('MAX_ACTIVE_ORDERS', 50);

/**
 * Nombre maximum de commandes archivées à charger
 */
define('MAX_ARCHIVED_ORDERS', 500);

/**
 * Limite d'historique commandes client
 */
define('CUSTOMER_ORDER_HISTORY_LIMIT', 20);

/**
 * Nombre de clients dans le leaderboard fidélité
 */
define('LOYALTY_LEADERBOARD_LIMIT', 10);

/**
 * Limite par défaut pour les notifications
 */
define('NOTIFICATION_LIMIT', 1);

// ============================================================================
// PRODUITS
// ============================================================================

/**
 * Longueur maximale du badge produit
 */
define('MAX_BADGE_LENGTH', 30);

/**
 * Longueur maximale du préfixe prix
 */
define('MAX_PRICE_PREFIX_LENGTH', 20);

// ============================================================================
// LIVRAISON
// ============================================================================

/**
 * Distance maximale de livraison par défaut (km)
 */
define('DEFAULT_MAX_DELIVERY_DISTANCE', 10);

// ============================================================================
// ENVIRONNEMENT / DEBUG
// ============================================================================

/**
 * Mode debug (à mettre à false en production)
 * Peut être surchargé via .env ou backend-config.php
 */
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', false);
}

/**
 * Environnement (development, staging, production)
 */
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}

// ============================================================================
// CORS - DOMAINES AUTORISÉS EN DÉVELOPPEMENT
// ============================================================================

/**
 * Domaines de développement autorisés pour CORS
 * En production, ces domaines sont ajoutés aux domaines de l'instance
 */
define('DEV_CORS_ORIGINS', [
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8000',
    'http://127.0.0.1',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000',
]);

/**
 * Méthodes HTTP autorisées pour CORS
 */
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');

/**
 * Headers autorisés pour CORS
 */
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-CSRF-Token, X-Requested-With');

// ============================================================================
// WHATSAPP
// ============================================================================

/**
 * URL de base WhatsApp
 */
define('WHATSAPP_BASE_URL', 'https://wa.me/');

// ============================================================================
// HELPERS FUNCTIONS
// ============================================================================

/**
 * Récupère le symbole de devise à partir du code
 *
 * @param string $currencyCode Code de devise (DA, EUR, USD, etc.)
 * @return string Symbole de devise
 */
function getCurrencySymbol(string $currencyCode): string {
    return CURRENCY_SYMBOLS[$currencyCode] ?? $currencyCode;
}

/**
 * Formate un montant avec la devise
 *
 * @param float $amount Montant
 * @param string|null $currency Code devise (utilise CURRENCY si null)
 * @param int $decimals Nombre de décimales
 * @return string Montant formaté
 */
function formatPrice(float $amount, ?string $currency = null, int $decimals = 2): string {
    $currency = $currency ?? (defined('CURRENCY') ? CURRENCY : DEFAULT_CURRENCY);
    $symbol = getCurrencySymbol($currency);
    return number_format($amount, $decimals, ',', ' ') . ' ' . $symbol;
}

/**
 * Valide un numéro de téléphone
 *
 * @param string $phone Numéro de téléphone (sans indicatif)
 * @return bool True si valide
 */
function isValidPhoneNumber(string $phone): bool {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    $length = strlen($cleanPhone);
    return $length >= MIN_PHONE_LENGTH && $length <= MAX_PHONE_LENGTH;
}

/**
 * Formate un numéro de téléphone complet
 *
 * @param string $phone Numéro de téléphone
 * @param string $indicator Indicatif pays
 * @return string Numéro formaté
 */
function formatPhoneNumber(string $phone, string $indicator = DEFAULT_PHONE_INDICATOR): string {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    // Retirer le 0 initial si présent (format local)
    if (str_starts_with($cleanPhone, '0')) {
        $cleanPhone = substr($cleanPhone, 1);
    }
    return $indicator . $cleanPhone;
}
