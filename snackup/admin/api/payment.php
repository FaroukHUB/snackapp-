<?php
/**
 * SnackApp - Payment API (Stripe)
 * Gestion des paiements en ligne via Stripe
 *
 * Actions:
 * - create_intent: Créer un PaymentIntent pour une commande
 * - get_public_key: Récupérer la clé publique Stripe
 * - verify: Vérifier le statut d'un paiement
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../../backend/PaymentService.php';
require_once __DIR__ . '/../../backend/repositories/SettingsRepository.php';

header('Content-Type: application/json');

// CORS centralisé - API publique
handlePublicCors();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

// Récupérer les settings de paiement
$paymentSettings = SettingsRepository::getPaymentSettings();

switch ($action) {

    /**
     * Récupérer la clé publique Stripe et l'état du paiement en ligne
     */
    case 'get_config':
        // Pas besoin d'auth pour cette action
        $publicSettings = SettingsRepository::getPublicPaymentSettings();

        if (empty($publicSettings['online_payment_enabled'])) {
            jsonSuccess([
                'enabled' => false,
                'message' => 'Paiement en ligne non activé'
            ]);
            break;
        }

        $stripeKey = $publicSettings['stripe_public_key'] ?? null;

        if (empty($stripeKey)) {
            jsonSuccess([
                'enabled' => false,
                'message' => 'Stripe non configuré'
            ]);
            break;
        }

        jsonSuccess([
            'enabled' => true,
            'stripe_public_key' => $stripeKey,
            'mode' => $publicSettings['stripe_mode'] ?? 'test'
        ]);
        break;

    /**
     * Créer un PaymentIntent Stripe
     * Requis: amount (en euros)
     * Optionnel: customer_name, customer_phone, order_items (pour métadonnées)
     */
    case 'create_intent':
        $amount = (float)($input['amount'] ?? 0);

        if ($amount < MIN_ONLINE_PAYMENT_AMOUNT) {
            jsonError('Montant minimum: ' . MIN_ONLINE_PAYMENT_AMOUNT . ' ' . CURRENCY);
        }

        // Vérifier que le paiement en ligne est activé
        if (empty($paymentSettings['online_payment_enabled'])) {
            jsonError('Paiement en ligne non activé');
        }

        // Déterminer la clé secrète selon le mode
        $mode = $paymentSettings['stripe_mode'] ?? 'test';
        $secretKey = ($mode === 'live')
            ? ($paymentSettings['stripe_secret_key_live'] ?? '')
            : ($paymentSettings['stripe_secret_key_test'] ?? '');

        if (empty($secretKey)) {
            error_log('[Payment] Stripe secret key manquante pour le mode: ' . $mode);
            jsonError('Configuration Stripe incomplète');
        }

        // Vérifier que le SDK Stripe est installé
        $vendorPath = __DIR__ . '/../../../vendor/autoload.php';
        if (!file_exists($vendorPath)) {
            error_log('[Payment] Stripe SDK non installé: ' . $vendorPath);
            jsonError('SDK Stripe non installé. Exécutez: composer require stripe/stripe-php');
        }

        require_once $vendorPath;

        try {
            \Stripe\Stripe::setApiKey($secretKey);

            // Métadonnées pour le paiement
            $metadata = [
                'restaurant_id' => SNACK_RESTAURANT_ID,
                'customer_name' => $input['customer_name'] ?? 'Client',
                'customer_phone' => $input['customer_phone'] ?? ''
            ];

            // Créer le PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => round($amount * 100), // Convertir en centimes
                'currency' => 'eur',
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            jsonSuccess([
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount
            ]);

        } catch (\Stripe\Exception\CardException $e) {
            error_log('[Payment] Carte refusée: ' . $e->getMessage());
            jsonError('Carte refusée: ' . $e->getError()->message);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            error_log('[Payment] Requête invalide: ' . $e->getMessage());
            jsonError('Erreur de configuration Stripe');
        } catch (Exception $e) {
            error_log('[Payment] Erreur Stripe: ' . $e->getMessage());
            jsonError('Erreur lors de la création du paiement');
        }
        break;

    /**
     * Vérifier le statut d'un paiement
     */
    case 'verify':
        $paymentIntentId = $input['payment_intent_id'] ?? '';

        if (empty($paymentIntentId)) {
            jsonError('ID de paiement manquant');
        }

        // Déterminer la clé secrète selon le mode
        $mode = $paymentSettings['stripe_mode'] ?? 'test';
        $secretKey = ($mode === 'live')
            ? ($paymentSettings['stripe_secret_key_live'] ?? '')
            : ($paymentSettings['stripe_secret_key_test'] ?? '');

        if (empty($secretKey)) {
            jsonError('Configuration Stripe incomplète');
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        try {
            \Stripe\Stripe::setApiKey($secretKey);

            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            jsonSuccess([
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'metadata' => (array) $paymentIntent->metadata
            ]);

        } catch (Exception $e) {
            error_log('[Payment] Erreur vérification: ' . $e->getMessage());
            jsonError('Impossible de vérifier le paiement');
        }
        break;

    /**
     * Webhook Stripe (pour réception des événements)
     */
    case 'webhook':
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $webhookSecret = $paymentSettings['stripe_webhook_secret'] ?? '';

        if (empty($webhookSecret)) {
            http_response_code(400);
            echo json_encode(['error' => 'Webhook secret non configuré']);
            exit;
        }

        require_once __DIR__ . '/../../../vendor/autoload.php';

        $mode = $paymentSettings['stripe_mode'] ?? 'test';
        $secretKey = ($mode === 'live')
            ? ($paymentSettings['stripe_secret_key_live'] ?? '')
            : ($paymentSettings['stripe_secret_key_test'] ?? '');

        \Stripe\Stripe::setApiKey($secretKey);

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);

            // Traiter l'événement
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    error_log('[Webhook] Paiement réussi: ' . $paymentIntent->id);
                    // TODO: Mettre à jour le statut de la commande
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    error_log('[Webhook] Paiement échoué: ' . $paymentIntent->id);
                    break;
            }

            http_response_code(200);
            echo json_encode(['received' => true]);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log('[Webhook] Signature invalide: ' . $e->getMessage());
            http_response_code(400);
            echo json_encode(['error' => 'Signature invalide']);
        } catch (Exception $e) {
            error_log('[Webhook] Erreur: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;

    default:
        jsonError('Action invalide');
}
