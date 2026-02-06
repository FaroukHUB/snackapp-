<?php
/**
 * API Gestion Livreurs
 * CRUD pour les livreurs WhatsApp
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../config.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$livreursFile = DATA_DIR . 'livreurs.json';

// Charger livreurs
function loadLivreurs() {
    global $livreursFile;
    if (!file_exists($livreursFile)) {
        return [];
    }
    return json_decode(file_get_contents($livreursFile), true) ?: [];
}

// Sauvegarder livreurs
function saveLivreurs($livreurs) {
    global $livreursFile;
    return file_put_contents($livreursFile, json_encode($livreurs, JSON_PRETTY_PRINT));
}

switch ($action) {

    case 'list':
        $livreurs = loadLivreurs();
        jsonSuccess(['livreurs' => $livreurs]);
        break;

    case 'add':
        requireCsrf();

        $prenom = trim($_POST['prenom'] ?? '');
        $indicatif = trim($_POST['indicatif'] ?? DEFAULT_PHONE_INDICATOR);
        $numero = trim($_POST['numero'] ?? '');

        if (empty($prenom) || empty($numero)) {
            jsonError('Prénom et numéro obligatoires');
        }

        // Nettoyer le numéro (garder que les chiffres)
        $numero = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($numero) < MIN_PHONE_LENGTH) {
            jsonError('Numéro invalide (minimum ' . MIN_PHONE_LENGTH . ' chiffres)');
        }

        $livreurs = loadLivreurs();

        $id = uniqid('liv_');
        $livreurs[$id] = [
            'id' => $id,
            'prenom' => $prenom,
            'indicatif' => $indicatif,
            'numero' => $numero,
            'whatsapp' => $indicatif . $numero,
            'actif' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        saveLivreurs($livreurs);
        jsonSuccess(['livreur' => $livreurs[$id]]);
        break;

    case 'edit':
        requireCsrf();

        $id = $_POST['id'] ?? '';
        $prenom = trim($_POST['prenom'] ?? '');
        $indicatif = trim($_POST['indicatif'] ?? DEFAULT_PHONE_INDICATOR);
        $numero = trim($_POST['numero'] ?? '');
        $actif = isset($_POST['actif']) && $_POST['actif'] === 'true';

        if (empty($id) || empty($prenom) || empty($numero)) {
            jsonError('Données manquantes');
        }

        $numero = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($numero) < MIN_PHONE_LENGTH) {
            jsonError('Numéro invalide (minimum ' . MIN_PHONE_LENGTH . ' chiffres)');
        }

        $livreurs = loadLivreurs();

        if (!isset($livreurs[$id])) {
            jsonError('Livreur introuvable');
        }

        $livreurs[$id]['prenom'] = $prenom;
        $livreurs[$id]['indicatif'] = $indicatif;
        $livreurs[$id]['numero'] = $numero;
        $livreurs[$id]['whatsapp'] = $indicatif . $numero;
        $livreurs[$id]['actif'] = $actif;

        saveLivreurs($livreurs);
        jsonSuccess(['livreur' => $livreurs[$id]]);
        break;

    case 'delete':
        requireCsrf();

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            jsonError('ID manquant');
        }

        $livreurs = loadLivreurs();

        if (!isset($livreurs[$id])) {
            jsonError('Livreur introuvable');
        }

        unset($livreurs[$id]);
        saveLivreurs($livreurs);

        jsonSuccess(['message' => 'Livreur supprimé']);
        break;

    case 'send_to_delivery':
        // 🔍 DIAGNOSTIC CSRF
        $debugLog = __DIR__ . '/../debug-csrf.log';
        $debugInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'session_id' => session_id(),
            'token_recu' => substr($_POST['csrf_token'] ?? 'AUCUN', 0, 10),
            'token_session' => substr($_SESSION['csrf_token'] ?? 'AUCUN', 0, 10),
            'cookies' => $_COOKIE,
            'post_keys' => array_keys($_POST)
        ];
        file_put_contents($debugLog, json_encode($debugInfo, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);

        requireCsrf();

        $orderId = $_POST['order_id'] ?? '';
        $livreurId = $_POST['livreur_id'] ?? '';

        if (empty($orderId) || empty($livreurId)) {
            jsonError('Données manquantes');
        }

        // Charger la commande
        try {
            // Essayer MySQL d'abord
            if (!SNACK_USE_JSON && !defined('SNACK_DB_ERROR')) {
                // Essayer d'abord par order_number
                $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);

                // Si pas trouvé, essayer par ID direct
                if (!$order && is_numeric($orderId)) {
                    $order = OrderRepository::getById((int)$orderId);
                }

                if (!$order) {
                    jsonError('Commande introuvable (ID: ' . $orderId . ')');
                }
            } else {
                // Fallback JSON
                $orders = loadData('orders.json') ?? [];
                $order = null;
                foreach ($orders as $o) {
                    if (($o['order_number'] ?? '') === $orderId || ($o['id'] ?? '') === $orderId) {
                        $order = $o;
                        break;
                    }
                }

                if (!$order) {
                    jsonError('Commande introuvable (ID: ' . $orderId . ')');
                }
            }

            // Charger le livreur
            $livreurs = loadLivreurs();
            if (!isset($livreurs[$livreurId])) {
                jsonError('Livreur introuvable (ID: ' . $livreurId . ')');
            }

            $livreur = $livreurs[$livreurId];
        } catch (Exception $e) {
            error_log('Erreur send_to_delivery: ' . $e->getMessage());
            jsonError('Erreur lors du chargement des données: ' . $e->getMessage());
        }

        // Récupérer le nom du restaurant
        $restaurant = getCurrentRestaurant();
        $restaurantName = $restaurant['name'] ?? 'Restaurant';
        $restaurantCity = $restaurant['city'] ?? $restaurant['address'] ?? '';

        // Formater le numéro de téléphone pour lien cliquable
        $customerPhone = $order['customer_phone'] ?? 'N/A';
        $phoneClean = preg_replace('/[^0-9+]/', '', $customerPhone);

        // Générer le message WhatsApp
        $message = "🍽️ *NOUVELLE LIVRAISON - {$restaurantName}*\n\n";

        // ========== CLIENT ==========
        $message .= "👤 *Client:* " . ($order['customer_name'] ?? 'N/A') . "\n";
        $message .= "📞 *Tel:* {$customerPhone}\n";
        // Lien cliquable pour appeler
        $message .= "📱 Appeler: https://wa.me/{$phoneClean}\n\n";

        // ========== ADRESSE ==========
        $notes = $order['notes'] ?? '';
        $deliveryAddress = $order['delivery_address'] ?? null;
        $isDelivery = str_contains($notes, 'LIVRAISON') || !empty($deliveryAddress);

        if ($isDelivery) {
            // Utiliser delivery_address si disponible, sinon extraire des notes
            $address = $deliveryAddress;
            if (empty($address) && preg_match('/Adresse:\s*(.+?)(?:\n|$)/i', $notes, $matches)) {
                $address = trim($matches[1]);
            }

            if (!empty($address)) {
                $message .= "📍 *ADRESSE DE LIVRAISON:*\n";
                $message .= "*{$address}*\n";

                // Lien Google Maps cliquable
                $searchAddress = $restaurantCity ? "{$address}, {$restaurantCity}" : $address;
                $addressEncoded = urlencode($searchAddress);
                $message .= "🗺️ *Ouvrir dans Maps:*\nhttps://www.google.com/maps/search/?api=1&query=" . $addressEncoded . "\n\n";
            } else {
                // Fallback: afficher toutes les notes si adresse pas trouvée
                $message .= "📍 *Infos:*\n" . $notes . "\n\n";
            }
        }

        // ========== COMMANDE ==========
        $message .= "🛍️ *COMMANDE:*\n";
        $message .= "━━━━━━━━━━━━━━━━\n";

        if (!empty($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $name = $item['name'] ?? 'Produit';
                $price = $item['price'] ?? 0;

                $message .= "*{$qty}x {$name}*\n";
                $message .= "   " . number_format($price * $qty, 2, ',', ' ') . " " . CURRENCY . "\n";

                // Options sélectionnées (Ifri, Croissant, etc.)
                if (!empty($item['selected_options']) && is_array($item['selected_options'])) {
                    foreach ($item['selected_options'] as $optKey => $optValue) {
                        if ($optValue) {
                            $message .= "   → " . ucfirst(str_replace('_', ' ', $optKey)) . ": " . $optValue . "\n";
                        }
                    }
                }

                // Suppléments
                if (!empty($item['supplements']) && is_array($item['supplements'])) {
                    foreach ($item['supplements'] as $sup) {
                        $supName = $sup['name'] ?? '';
                        $supPrice = $sup['price'] ?? 0;
                        $message .= "   + {$supName} (+" . number_format($supPrice, 2, ',', ' ') . " " . CURRENCY . ")\n";
                    }
                }

                // Ingrédients retirés
                if (!empty($item['removed_ingredients']) && is_array($item['removed_ingredients'])) {
                    $message .= "   ⚠️ SANS: " . implode(', ', $item['removed_ingredients']) . "\n";
                }

                $message .= "\n";
            }
        }

        $total = $order['total'] ?? 0;
        $message .= "━━━━━━━━━━━━━━━━\n";
        $message .= "💰 *TOTAL: " . number_format($total, 2, ',', ' ') . " " . CURRENCY . "*\n\n";

        // ========== PAIEMENT ==========
        $message .= "💳 *PAIEMENT:*\n";

        // Extraire infos de monnaie depuis notes
        if (preg_match('/l\'appoint/i', $notes)) {
            $message .= "✅ Client a l'appoint (montant exact)\n";
        } elseif (preg_match('/Prévoir monnaie sur:\s*(\d+)\s*' . CURRENCY . '/i', $notes, $matches)) {
            $changeFor = (int)$matches[1];
            $toReturn = $changeFor - $total;
            $message .= "💵 *À PRÉPARER:*\n";
            $message .= "   • Client donne: " . number_format($changeFor, 2, ',', ' ') . " " . CURRENCY . "\n";
            $message .= "   • *À rendre: " . number_format($toReturn, 2, ',', ' ') . " " . CURRENCY . "*\n";
        } else {
            $message .= "💵 Espèces (montant exact non précisé)\n";
        }

        $message .= "\n⏰ *Commande reçue:* " . date('H:i', strtotime($order['created_at'] ?? 'now')) . "\n";

        // Générer le lien WhatsApp
        $whatsappUrl = sendWhatsAppMessage($livreur['whatsapp'], $message);

        jsonSuccess([
            'whatsapp_url' => $whatsappUrl,
            'message' => $message
        ]);
        break;

    default:
        jsonError('Action invalide');
}
