<?php
/**
 * API publique pour les points de fidélité
 * Accessible sans authentification admin
 */

require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_points':
        checkPoints();
        break;
    case 'get_rewards':
        getRewards();
        break;
    default:
        jsonError('Action invalide');
}

function normalizePhone($phone) {
    // Enlever tous les caractères non numériques sauf le +
    $normalized = preg_replace('/[^0-9+]/', '', $phone);

    // Enlever le + du début
    $normalized = ltrim($normalized, '+');

    // Si le numéro commence par 0 (format national français)
    // et fait 10 chiffres → convertir en format international
    if (preg_match('/^0([1-9]\d{8})$/', $normalized, $matches)) {
        // 0757831883 → 33757831883
        $normalized = '33' . $matches[1];
    }

    return $normalized;
}

function checkPoints() {
    $phone = $_GET['phone'] ?? '';

    if (empty($phone)) {
        jsonError('Numéro de téléphone manquant');
    }

    // Normaliser le numéro recherché
    $normalizedPhone = normalizePhone($phone);

    // Déterminer si on utilise MySQL ou JSON
    $useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

    if ($useMySQL) {
        // Mode MySQL
        $allCustomers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    } else {
        // Mode JSON
        $allCustomers = loadData('customers.json') ?? [];
    }

    // Trouver TOUS les comptes qui matchent (gérer les doublons)
    $matchingCustomers = [];
    $totalPoints = 0;
    $mainCustomer = null;

    foreach ($allCustomers as $customer) {
        $customerPhone = $customer['phone'] ?? '';

        // Comparer les numéros normalisés
        if (normalizePhone($customerPhone) === $normalizedPhone) {
            $matchingCustomers[] = $customer;
            $totalPoints += (int)($customer['loyalty_points'] ?? 0);

            // Garder le premier comme compte principal
            if ($mainCustomer === null) {
                $mainCustomer = $customer;
            }
        }
    }

    if ($mainCustomer) {
        // Retourner le compte principal avec les points combinés
        jsonSuccess([
            'customer' => [
                'id' => $mainCustomer['id'],
                'name' => $mainCustomer['name'],
                'phone' => $mainCustomer['phone'],
                'delivery_address' => $mainCustomer['delivery_address'] ?? null, // ⚡ Adresse de livraison
                'loyalty_points' => $totalPoints, // Points combinés de tous les comptes
                'duplicate_count' => count($matchingCustomers) // Nombre de comptes en double
            ]
        ]);
        return;
    }

    jsonError('Aucun compte fidélité trouvé pour ce numéro');
}

function getRewards() {
    $customerPoints = (int)($_GET['points'] ?? 0);

    // Récupérer toutes les récompenses actives avec infos produit et image custom
    $rewards = Database::fetchAll(
        "SELECT lr.id, lr.name, lr.description, lr.points_required, lr.reward_type, lr.reward_value,
                lr.product_id, lr.image AS reward_image,
                p.name AS product_name, p.image AS product_image
         FROM loyalty_rewards lr
         LEFT JOIN products p ON lr.product_id = p.id
         WHERE lr.restaurant_id = ? AND lr.is_active = 1
         ORDER BY lr.points_required ASC",
        [SNACK_RESTAURANT_ID]
    );

    // Marquer lesquelles sont disponibles
    foreach ($rewards as &$reward) {
        $reward['can_claim'] = $customerPoints >= $reward['points_required'];
        $reward['points_required'] = (int)$reward['points_required'];
    }

    jsonSuccess(['rewards' => $rewards]);
}
