<?php
/**
 * Configuration CORS centralisée - Snackup Admin
 * ===============================================
 * Gère les headers CORS de manière uniforme pour toutes les API.
 *
 * Usage:
 *   require_once __DIR__ . '/cors.php';
 *   handleCors(); // Autorise uniquement les domaines configurés
 *   // ou
 *   handleCors(true); // Mode public (Access-Control-Allow-Origin: *)
 *
 * @author Claude
 * @version 1.0.0
 */

// Charger les constantes si pas déjà fait
if (!defined('DEV_CORS_ORIGINS')) {
    require_once __DIR__ . '/constants.php';
}

/**
 * Récupère tous les domaines autorisés pour CORS
 *
 * @return array Liste des origines autorisées
 */
function getAllowedCorsOrigins(): array {
    $origins = DEV_CORS_ORIGINS;

    // Ajouter les domaines de production depuis les instances
    $instanceManagerPath = __DIR__ . '/../backend/InstanceManager.php';
    if (file_exists($instanceManagerPath)) {
        require_once $instanceManagerPath;

        if (class_exists('InstanceManager')) {
            try {
                InstanceManager::init();
                $allInstances = InstanceManager::getAllInstances();

                foreach ($allInstances as $instanceData) {
                    if (isset($instanceData['domains']) && is_array($instanceData['domains'])) {
                        foreach ($instanceData['domains'] as $domain) {
                            $origins[] = 'https://' . $domain;
                            $origins[] = 'https://www.' . $domain;
                            $origins[] = 'http://' . $domain; // Pour dev/staging
                        }
                    }
                }
            } catch (Exception $e) {
                // Silently fail - use only dev origins
                error_log('[CORS] Erreur chargement instances: ' . $e->getMessage());
            }
        }
    }

    return array_unique($origins);
}

/**
 * Vérifie si une origine est autorisée
 *
 * @param string $origin Origine à vérifier
 * @return bool True si autorisée
 */
function isOriginAllowed(string $origin): bool {
    if (empty($origin)) {
        return false;
    }

    $allowedOrigins = getAllowedCorsOrigins();
    return in_array($origin, $allowedOrigins, true);
}

/**
 * Applique les headers CORS
 *
 * @param bool $publicApi Si true, autorise toutes les origines (*)
 * @param bool $allowCredentials Si true, autorise les credentials (cookies)
 * @param bool $exitOnForbidden Si true, termine le script si origine non autorisée
 * @return bool True si origine autorisée, false sinon
 */
function handleCors(
    bool $publicApi = false,
    bool $allowCredentials = false,
    bool $exitOnForbidden = false
): bool {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Mode public - accepte toutes les origines
    if ($publicApi) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return true;
    }

    // Mode restreint - vérifie l'origine
    if (isOriginAllowed($origin)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
        header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);

        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return true;
    }

    // Origine non autorisée
    if ($exitOnForbidden && !empty($origin)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Origine non autorisée'
        ]);
        exit;
    }

    // Pas d'origine (requête same-origin ou serveur) - autorisé
    if (empty($origin)) {
        return true;
    }

    return false;
}

/**
 * Version simplifiée pour les API publiques (read-only)
 * Autorise toutes les origines mais pas les credentials
 */
function handlePublicCors(): void {
    handleCors(true, false, false);
}

/**
 * Version pour les API qui nécessitent authentification
 * Restreint aux domaines autorisés, permet les credentials
 */
function handlePrivateCors(): void {
    handleCors(false, true, true);
}

/**
 * Version pour les webhooks
 * Vérifie l'origine mais ne bloque pas (les webhooks viennent de serveurs tiers)
 */
function handleWebhookCors(): void {
    handleCors(false, false, false);
}
