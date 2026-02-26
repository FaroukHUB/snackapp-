<?php
/**
 * SnackApp - Admin PIN API
 * Gestion du PIN pour accès aux onglets sensibles (Stats, Archives)
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

// Vérifier que l'admin est connecté
if (!isAdminLoggedIn()) {
    jsonError('Non autorisé', 401);
}

$pinFile = __DIR__ . '/../data/admin-pin.json';

/**
 * Charger le PIN
 */
function loadPin(): array {
    global $pinFile;
    if (!file_exists($pinFile)) {
        // ⚠️ SÉCURITÉ: PIN par défaut 1234 (doit être changé au premier usage)
        $defaultPin = '1234';
        error_log('[SÉCURITÉ] ⚠️ PIN par défaut utilisé: ' . $defaultPin . ' - CHANGEZ-LE immédiatement!');
        // Sauvegarder le PIN par défaut pour qu'il persiste
        $dir = dirname($pinFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $data = [
            'pin' => $defaultPin,
            'updated_at' => date('c'),
            'is_default' => true,
            'note' => 'PIN initial généré automatiquement - CHANGEZ-LE immédiatement.'
        ];
        file_put_contents($pinFile, json_encode($data, JSON_PRETTY_PRINT));
        return $data;
    }
    $data = json_decode(file_get_contents($pinFile), true);
    return is_array($data) ? $data : ['pin' => '1234', 'is_default' => true];
}

/**
 * Sauvegarder le PIN
 */
function savePin(string $newPin): bool {
    global $pinFile;
    $data = [
        'pin' => $newPin,
        'updated_at' => date('c'),
        'note' => 'PIN modifié via interface admin.'
    ];
    return file_put_contents($pinFile, json_encode($data, JSON_PRETTY_PRINT)) !== false;
}

// Récupérer l'action
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'verify':
        // 🔒 SÉCURITÉ: Rate limiting - bloquer après 3 tentatives
        if (!isset($_SESSION['pin_attempts'])) {
            $_SESSION['pin_attempts'] = 0;
            $_SESSION['pin_blocked_until'] = 0;
        }

        // Vérifier si bloqué
        if (time() < $_SESSION['pin_blocked_until']) {
            $waitTime = $_SESSION['pin_blocked_until'] - time();
            $minutes = ceil($waitTime / 60);
            error_log('[PIN] ⛔ Tentative bloquée - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            jsonError("Trop de tentatives. Réessayez dans {$minutes} minute(s).", 429);
        }

        // Vérifier le PIN
        $pin = trim($input['pin'] ?? '');
        $stored = loadPin();

        // Debug logging (sans révéler le PIN)
        error_log('[PIN] Tentative de vérification - longueur reçue: ' . strlen($pin));

        if ($pin === $stored['pin']) {
            error_log('[PIN] ✅ PIN correct - accès autorisé');

            // 🔒 SÉCURITÉ: Régénérer session ID (protection session fixation)
            session_regenerate_id(true);

            // Reset tentatives après succès
            $_SESSION['pin_attempts'] = 0;
            $_SESSION['pin_blocked_until'] = 0;

            // Stocker en session que le PIN est validé (expire après 1h)
            $_SESSION['pin_unlocked'] = true;
            $_SESSION['pin_unlocked_at'] = time();

            // Avertir si PIN par défaut utilisé
            if (!empty($stored['is_default'])) {
                error_log('[SÉCURITÉ] ⚠️ PIN par défaut utilisé - recommande changement immédiat');
            }

            jsonSuccess(['unlocked' => true, 'is_default' => !empty($stored['is_default'])], 'PIN correct');
        } else {
            // Incrémenter tentatives
            $_SESSION['pin_attempts']++;

            error_log('[PIN] ❌ PIN incorrect - tentative ' . $_SESSION['pin_attempts'] . '/3 - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

            // Bloquer après 3 tentatives (1 heure)
            if ($_SESSION['pin_attempts'] >= 3) {
                $_SESSION['pin_blocked_until'] = time() + 3600; // 1 heure
                error_log('[PIN] ⛔ BLOQUÉ pour 1 heure après 3 tentatives échouées');
                jsonError('Trop de tentatives. Réessayez dans 1 heure.', 429);
            }

            $remaining = 3 - $_SESSION['pin_attempts'];
            jsonError("PIN incorrect. {$remaining} tentative(s) restante(s).", 403);
        }
        break;

    case 'check':
        // Vérifier si déjà déverrouillé (session valide < 1h)
        $unlocked = false;
        if (!empty($_SESSION['pin_unlocked']) && !empty($_SESSION['pin_unlocked_at'])) {
            $elapsed = time() - $_SESSION['pin_unlocked_at'];
            if ($elapsed < 3600) { // 1 heure
                $unlocked = true;
            } else {
                // Expiré
                unset($_SESSION['pin_unlocked'], $_SESSION['pin_unlocked_at']);
            }
        }
        jsonSuccess(['unlocked' => $unlocked]);
        break;

    case 'change':
        // Changer le PIN (nécessite l'ancien PIN)
        $oldPin = $input['old_pin'] ?? '';
        $newPin = $input['new_pin'] ?? '';

        if (strlen($newPin) < 4 || strlen($newPin) > 8) {
            jsonError('Le PIN doit contenir entre 4 et 8 caractères');
        }

        if (!ctype_digit($newPin)) {
            jsonError('Le PIN doit contenir uniquement des chiffres');
        }

        $stored = loadPin();
        if ($oldPin !== $stored['pin']) {
            jsonError('Ancien PIN incorrect', 403);
        }

        if (savePin($newPin)) {
            jsonSuccess([], 'PIN modifié avec succès');
        } else {
            jsonError('Erreur lors de la sauvegarde');
        }
        break;

    case 'lock':
        // Verrouiller manuellement
        unset($_SESSION['pin_unlocked'], $_SESSION['pin_unlocked_at']);
        jsonSuccess([], 'Session verrouillée');
        break;

    default:
        jsonError('Action invalide');
}
