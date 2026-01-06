<?php
/**
 * API Gestion Codes Promo (Admin)
 * Actions: list, create, update, delete, stats
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier authentification admin
requireAdmin();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Merger POST data si JSON vide
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

// Mode MySQL uniquement (pas de fallback JSON pour codes promo)
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

if (!$useMySQL) {
    jsonError('Les codes promo nécessitent une base de données MySQL');
}

try {
    switch ($action) {

        // Liste tous les codes promo
        case 'list':
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
            $promoCodes = PromoCodeRepository::getAll(SNACK_RESTAURANT_ID, $activeOnly);
            jsonSuccess(['promo_codes' => $promoCodes]);
            break;

        // Créer un code promo
        case 'create':
            $promoId = PromoCodeRepository::create(SNACK_RESTAURANT_ID, $input);
            $promo = PromoCodeRepository::getById($promoId);
            jsonSuccess(['promo_code' => $promo], 'Code promo créé avec succès');
            break;

        // Modifier un code promo
        case 'update':
            $id = intval($input['id'] ?? 0);

            if ($id <= 0) {
                jsonError('ID invalide');
            }

            // Vérifier que le code appartient au restaurant
            $existing = PromoCodeRepository::getById($id);
            if (!$existing || $existing['restaurant_id'] != SNACK_RESTAURANT_ID) {
                jsonError('Code promo non trouvé');
            }

            $success = PromoCodeRepository::update($id, $input);

            if ($success) {
                $promo = PromoCodeRepository::getById($id);
                jsonSuccess(['promo_code' => $promo], 'Code promo modifié avec succès');
            } else {
                jsonError('Aucune modification effectuée');
            }
            break;

        // Supprimer un code promo
        case 'delete':
            $id = intval($input['id'] ?? $_GET['id'] ?? 0);

            if ($id <= 0) {
                jsonError('ID invalide');
            }

            // Vérifier que le code appartient au restaurant
            $existing = PromoCodeRepository::getById($id);
            if (!$existing || $existing['restaurant_id'] != SNACK_RESTAURANT_ID) {
                jsonError('Code promo non trouvé');
            }

            $success = PromoCodeRepository::delete($id);

            if ($success) {
                jsonSuccess([], 'Code promo supprimé avec succès');
            } else {
                jsonError('Erreur lors de la suppression');
            }
            break;

        // Activer/Désactiver un code promo
        case 'toggle':
            $id = intval($input['id'] ?? 0);

            if ($id <= 0) {
                jsonError('ID invalide');
            }

            $existing = PromoCodeRepository::getById($id);
            if (!$existing || $existing['restaurant_id'] != SNACK_RESTAURANT_ID) {
                jsonError('Code promo non trouvé');
            }

            $newStatus = !$existing['is_active'];
            $success = PromoCodeRepository::update($id, ['is_active' => $newStatus]);

            if ($success) {
                jsonSuccess(['is_active' => $newStatus], 'Statut modifié avec succès');
            } else {
                jsonError('Erreur lors du changement de statut');
            }
            break;

        // Statistiques
        case 'stats':
            $stats = PromoCodeRepository::getStats(SNACK_RESTAURANT_ID);
            jsonSuccess(['stats' => $stats]);
            break;

        default:
            jsonError('Action non reconnue');
    }

} catch (Exception $e) {
    jsonError($e->getMessage());
}
