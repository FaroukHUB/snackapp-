<?php
declare(strict_types=1);
/**
 * API Settings - Gestion des paramètres restaurant, livraison par VILLE, paiement
 * Source de vérité: MySQL
 */

require_once __DIR__ . '/../bootstrap.php';
require_once SNACK_ROOT . '/snackup/backend/repositories/SettingsRepository.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// CORS pour les appels cross-origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Fonctions jsonSuccess, jsonError, requireAdmin viennent de bootstrap.php

function readInput(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?: [];
    }
    return $_POST;
}

// Action
$action = $_GET['action'] ?? $_POST['action'] ?? (readInput()['action'] ?? 'get_public');

switch ($action) {

    // ========================================
    // PUBLIC: Pour le panier (cart.html)
    // ========================================
    case 'get_public':
        $settings = SettingsRepository::getPublicSettings();
        jsonSuccess(['settings' => $settings]);
        break;

    case 'get_delivery_fee':
        // Récupérer frais de livraison pour une ville
        $input = readInput();
        $cityName = trim($input['city'] ?? $_GET['city'] ?? '');

        if (empty($cityName)) {
            jsonError('Ville non spécifiée');
        }

        $result = SettingsRepository::getDeliveryFeeForCity($cityName);

        if ($result === null) {
            jsonSuccess([
                'available' => false,
                'message' => 'Livraison non disponible pour cette ville'
            ]);
        } else {
            jsonSuccess([
                'available' => true,
                'city' => $result
            ]);
        }
        break;

    // ========================================
    // ADMIN: Gestion des paramètres
    // ========================================
    case 'get_admin':
        requireAdmin();
        $settings = SettingsRepository::getAdminSettings();
        jsonSuccess(['settings' => $settings]);
        break;

    case 'update_settings':
        requireAdmin();
        $input = readInput();
        unset($input['action']);

        if (SettingsRepository::updateSettings($input)) {
            jsonSuccess(['message' => 'Paramètres mis à jour']);
        } else {
            jsonError('Aucune modification');
        }
        break;

    // ========================================
    // ADMIN: Villes de livraison
    // ========================================
    case 'get_delivery_cities':
        requireAdmin();
        $cities = SettingsRepository::getAllDeliveryCities();
        jsonSuccess(['cities' => $cities]);
        break;

    case 'add_delivery_city':
        requireAdmin();
        $input = readInput();

        $id = SettingsRepository::addDeliveryCity($input);
        $cities = SettingsRepository::getAllDeliveryCities();

        jsonSuccess([
            'message' => 'Ville ajoutée',
            'city_id' => $id,
            'cities' => $cities
        ]);
        break;

    case 'update_delivery_city':
        requireAdmin();
        $input = readInput();
        $id = (int) ($input['id'] ?? 0);
        unset($input['action'], $input['id']);

        if ($id <= 0) {
            jsonError('ID ville manquant');
        }

        if (SettingsRepository::updateDeliveryCity($id, $input)) {
            $cities = SettingsRepository::getAllDeliveryCities();
            jsonSuccess(['message' => 'Ville mise à jour', 'cities' => $cities]);
        } else {
            jsonError('Erreur de mise à jour');
        }
        break;

    case 'delete_delivery_city':
        requireAdmin();
        $input = readInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            jsonError('ID ville manquant');
        }

        if (SettingsRepository::deleteDeliveryCity($id)) {
            $cities = SettingsRepository::getAllDeliveryCities();
            jsonSuccess(['message' => 'Ville supprimée', 'cities' => $cities]);
        } else {
            jsonError('Erreur de suppression');
        }
        break;

    // ========================================
    // ADMIN: Paramètres de paiement
    // ========================================
    case 'get_payment_settings':
        requireAdmin();
        $payment = SettingsRepository::getPaymentSettings();
        jsonSuccess(['payment' => $payment]);
        break;

    case 'update_payment_settings':
        requireAdmin();
        $input = readInput();
        unset($input['action']);

        try {
            if (SettingsRepository::updatePaymentSettings($input)) {
                jsonSuccess(['message' => 'Paramètres de paiement mis à jour']);
            } else {
                jsonError('Aucune modification - données reçues: ' . json_encode($input));
            }
        } catch (Exception $e) {
            jsonError('Erreur SQL: ' . $e->getMessage());
        }
        break;

    default:
        jsonError('Action invalide: ' . $action);
}
