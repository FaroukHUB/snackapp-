<?php
/**
 * API Validation Codes Promo (Public)
 * Permet aux clients de valider un code promo lors du checkout
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../cors.php';

header('Content-Type: application/json; charset=utf-8');

// CORS centralisé - restreint aux domaines autorisés
handleCors(false, false, false);

$action = $_GET['action'] ?? 'validate';

// Mode MySQL uniquement
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

if (!$useMySQL) {
    jsonError('Service temporairement indisponible');
}

try {
    switch ($action) {

        // Valider un code promo
        case 'validate':
            $code = trim($_GET['code'] ?? '');
            $orderTotal = floatval($_GET['order_total'] ?? 0);

            if (empty($code)) {
                jsonError('Code promo requis');
            }

            if ($orderTotal <= 0) {
                jsonError('Montant de commande invalide');
            }

            // Valider le code
            $promo = PromoCodeRepository::validate(SNACK_RESTAURANT_ID, $code, $orderTotal);

            if (!$promo) {
                jsonError('Code promo invalide');
            }

            // Calculer la réduction
            $discount = PromoCodeRepository::calculateDiscount($promo, $orderTotal);

            // Ne pas appliquer de réduction supérieure au total
            $discount = min($discount, $orderTotal);

            jsonSuccess([
                'valid' => true,
                'code' => $promo['code'],
                'description' => $promo['description'],
                'discount_type' => $promo['discount_type'],
                'discount_value' => $promo['discount_value'],
                'discount_amount' => round($discount, 2),
                'promo_id' => $promo['id']
            ]);
            break;

        default:
            jsonError('Action non reconnue');
    }

} catch (Exception $e) {
    jsonError($e->getMessage());
}
