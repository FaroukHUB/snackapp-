<?php
/**
 * PaymentService - Gestion des paiements en ligne (Stripe)
 *
 * Ce service gère l'intégration Stripe pour les paiements CB en ligne.
 *
 * Installation Stripe SDK :
 * composer require stripe/stripe-php
 *
 * Documentation : https://stripe.com/docs/api
 */

require_once __DIR__ . '/Database.php';

class PaymentService {

    /**
     * Créer une intention de paiement Stripe
     *
     * @param float $amount Montant en euros (ex: 15.50)
     * @param int $orderId ID de la commande
     * @param array $config Configuration Stripe (depuis backend-config.php)
     * @return array ['client_secret' => string, 'payment_intent_id' => string]
     */
    public static function createPaymentIntent($amount, $orderId, $config) {
        // Charger Stripe SDK
        require_once __DIR__ . '/../../vendor/autoload.php';

        \Stripe\Stripe::setApiKey($config['stripe']['secret_key']);

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => round($amount * 100), // Convertir en centimes
                'currency' => strtolower($config['app']['currency']),
                'metadata' => [
                    'order_id' => $orderId,
                    'instance_id' => $config['app']['instance_id']
                ],
                'description' => "Commande #{$orderId} - " . $config['email']['from_name'],
                'receipt_email' => null, // Optionnel
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];

        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => 'Carte refusée : ' . $e->getMessage()
            ];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            return [
                'success' => false,
                'error' => 'Requête invalide : ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur serveur : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier le statut d'un paiement Stripe
     *
     * @param string $paymentIntentId ID du PaymentIntent Stripe
     * @param array $config Configuration Stripe
     * @return array ['status' => string, 'amount' => float]
     */
    public static function verifyPayment($paymentIntentId, $config) {
        require_once __DIR__ . '/../../vendor/autoload.php';

        \Stripe\Stripe::setApiKey($config['stripe']['secret_key']);

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            return [
                'success' => true,
                'status' => $paymentIntent->status, // 'succeeded', 'pending', 'failed'
                'amount' => $paymentIntent->amount / 100, // Convertir en euros
                'order_id' => $paymentIntent->metadata->order_id ?? null
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Mettre à jour le statut de paiement d'une commande
     *
     * @param int $orderId ID de la commande
     * @param string $paymentStatus 'pending', 'paid', 'failed', 'refunded'
     * @param string $transactionId ID de transaction Stripe
     */
    public static function updateOrderPaymentStatus($orderId, $paymentStatus, $transactionId = null) {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            UPDATE orders
            SET payment_status = ?, transaction_id = ?
            WHERE id = ?
        ");

        return $stmt->execute([$paymentStatus, $transactionId, $orderId]);
    }

    /**
     * Traiter un webhook Stripe (événement de paiement)
     *
     * @param string $payload Corps de la requête POST (JSON)
     * @param string $signature Header Stripe-Signature
     * @param array $config Configuration Stripe
     * @return array ['success' => bool, 'event_type' => string]
     */
    public static function handleWebhook($payload, $signature, $config) {
        require_once __DIR__ . '/../../vendor/autoload.php';

        \Stripe\Stripe::setApiKey($config['stripe']['secret_key']);

        try {
            // Vérifier la signature du webhook
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $config['stripe']['webhook_secret']
            );

            // Traiter l'événement selon son type
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    $orderId = $paymentIntent->metadata->order_id ?? null;

                    if ($orderId) {
                        self::updateOrderPaymentStatus($orderId, 'paid', $paymentIntent->id);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;
                    $orderId = $paymentIntent->metadata->order_id ?? null;

                    if ($orderId) {
                        self::updateOrderPaymentStatus($orderId, 'failed', $paymentIntent->id);
                    }
                    break;

                case 'charge.refunded':
                    $charge = $event->data->object;
                    $paymentIntentId = $charge->payment_intent;

                    // Récupérer l'order_id depuis le PaymentIntent
                    $pdo = Database::getInstance();
                    $stmt = $pdo->prepare("SELECT id FROM orders WHERE transaction_id = ?");
                    $stmt->execute([$paymentIntentId]);
                    $orderId = $stmt->fetchColumn();

                    if ($orderId) {
                        self::updateOrderPaymentStatus($orderId, 'refunded', $paymentIntentId);
                    }
                    break;
            }

            return [
                'success' => true,
                'event_type' => $event->type
            ];

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return [
                'success' => false,
                'error' => 'Signature invalide : ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
