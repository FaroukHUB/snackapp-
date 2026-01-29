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
        $indicatif = trim($_POST['indicatif'] ?? '+213');
        $numero = trim($_POST['numero'] ?? '');

        if (empty($prenom) || empty($numero)) {
            jsonError('Prénom et numéro obligatoires');
        }

        // Nettoyer le numéro (garder que les chiffres)
        $numero = preg_replace('/[^0-9]/', '', $numero);

        if (strlen($numero) < 8) {
            jsonError('Numéro invalide');
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
        $indicatif = trim($_POST['indicatif'] ?? '+213');
        $numero = trim($_POST['numero'] ?? '');
        $actif = isset($_POST['actif']) && $_POST['actif'] === 'true';

        if (empty($id) || empty($prenom) || empty($numero)) {
            jsonError('Données manquantes');
        }

        $numero = preg_replace('/[^0-9]/', '', $numero);

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
                $order = OrderRepository::getByNumber(SNACK_RESTAURANT_ID, $orderId);
                if (!$order) {
                    jsonError('Commande introuvable dans MySQL (ID: ' . $orderId . ')');
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

        // Générer le message WhatsApp
        $message = "🍽️ *NOUVELLE LIVRAISON - {$restaurantName}*\n\n";

        // ========== CLIENT ==========
        $message .= "👤 *Client:* " . ($order['customer_name'] ?? 'N/A') . "\n";
        $message .= "📞 *Tel:* " . ($order['customer_phone'] ?? 'N/A') . "\n\n";

        // ========== ADRESSE ==========
        $notes = $order['notes'] ?? '';
        $isDelivery = str_contains($notes, 'LIVRAISON');

        if ($isDelivery) {
            // Récupérer la ville du restaurant pour Google Maps
            $restaurantCity = $restaurant['city'] ?? $restaurant['address'] ?? '';

            // Extraire l'adresse depuis les notes
            if (preg_match('/Adresse:\s*(.+?)(?:\n|$)/i', $notes, $matches)) {
                $address = trim($matches[1]);
                $message .= "📍 *Adresse de livraison:*\n";
                $message .= $address . "\n";

                // Lien Google Maps (utiliser l'adresse + ville du restaurant)
                $searchAddress = $restaurantCity ? "{$address}, {$restaurantCity}" : $address;
                $addressEncoded = urlencode($searchAddress);
                $message .= "🗺️ https://www.google.com/maps/search/?api=1&query=" . $addressEncoded . "\n\n";
            } else {
                // Fallback: afficher toutes les notes si adresse pas trouvée
                $message .= "📍 *Adresse:*\n" . $notes . "\n\n";
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
                $message .= "   " . number_format($price * $qty, 0, '', ' ') . " DA\n";

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
                        $message .= "   + {$supName} (+" . number_format($supPrice, 0, '', ' ') . " DA)\n";
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
        $message .= "💰 *TOTAL: " . number_format($total, 0, '', ' ') . " DA*\n\n";

        // ========== PAIEMENT ==========
        $message .= "💳 *PAIEMENT:*\n";

        // Extraire infos de monnaie depuis notes
        if (preg_match('/l\'appoint/i', $notes)) {
            $message .= "✅ Client a l'appoint (montant exact)\n";
        } elseif (preg_match('/Prévoir monnaie sur:\s*(\d+)\s*DA/i', $notes, $matches)) {
            $changeFor = (int)$matches[1];
            $toReturn = $changeFor - $total;
            $message .= "💵 *À PRÉPARER:*\n";
            $message .= "   • Client donne: " . number_format($changeFor, 0, '', ' ') . " DA\n";
            $message .= "   • *À rendre: " . number_format($toReturn, 0, '', ' ') . " DA*\n";
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
