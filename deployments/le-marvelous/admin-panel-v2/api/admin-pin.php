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
        return ['pin' => '1234'];
    }
    $data = json_decode(file_get_contents($pinFile), true);
    return is_array($data) ? $data : ['pin' => '1234'];
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
        // Vérifier le PIN
        $pin = $input['pin'] ?? '';
        $stored = loadPin();

        if ($pin === $stored['pin']) {
            // Stocker en session que le PIN est validé (expire après 1h)
            $_SESSION['pin_unlocked'] = true;
            $_SESSION['pin_unlocked_at'] = time();
            jsonSuccess(['unlocked' => true], 'PIN correct');
        } else {
            jsonError('PIN incorrect', 403);
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
