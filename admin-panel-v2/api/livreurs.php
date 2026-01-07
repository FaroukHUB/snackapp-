<?php
/**
 * API Gestion Livreurs
 * CRUD pour les livreurs WhatsApp
 */

require_once __DIR__ . '/../bootstrap.php';
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
        requireCsrf();

        $orderId = $_POST['order_id'] ?? '';
        $livreurId = $_POST['livreur_id'] ?? '';

        if (empty($orderId) || empty($livreurId)) {
            jsonError('Données manquantes');
        }

        // Charger la commande
        try {
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

        // Générer le message WhatsApp
        $message = "🍽️ *NOUVELLE LIVRAISON - Le Marvelous*\n\n";
        $message .= "👤 *Client:* " . ($order['customer_name'] ?? 'N/A') . "\n";
        $message .= "📞 *Tel:* " . ($order['customer_phone'] ?? 'N/A') . "\n\n";

        // Adresse
        if (!empty($order['delivery_address'])) {
            $message .= "📍 *Adresse:*\n" . $order['delivery_address'] . "\n";
            // Lien Google Maps si coordonnées disponibles
            if (!empty($order['delivery_coords'])) {
                $coords = explode(',', $order['delivery_coords']);
                if (count($coords) === 2) {
                    $message .= "🗺️ https://www.google.com/maps?q=" . trim($coords[0]) . "," . trim($coords[1]) . "\n";
                }
            }
            $message .= "\n";
        }

        // Détail commande
        $message .= "🛍️ *Commande:*\n";
        if (!empty($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $name = $item['name'] ?? 'Produit';
                $price = $item['price'] ?? 0;
                $message .= "• {$qty}x {$name} (" . number_format($price, 0, '', ' ') . " DA)\n";

                // Suppléments
                if (!empty($item['supplements']) && is_array($item['supplements'])) {
                    foreach ($item['supplements'] as $sup) {
                        $supName = $sup['name'] ?? '';
                        $supPrice = $sup['price'] ?? 0;
                        $message .= "  + {$supName} (+" . number_format($supPrice, 0, '', ' ') . " DA)\n";
                    }
                }
            }
        }

        $total = $order['total'] ?? 0;
        $message .= "\n💰 *TOTAL: " . number_format($total, 0, '', ' ') . " DA*\n\n";

        // Paiement
        $paymentMethod = $order['payment_method'] ?? 'cash';
        if ($paymentMethod === 'cash') {
            $message .= "💵 *Paiement:* ESPÈCES\n";

            // Info monnaie
            if (!empty($order['has_exact_change'])) {
                $message .= "✅ Client a l'appoint\n";
            } elseif (!empty($order['change_for'])) {
                $changeFor = (float)$order['change_for'];
                $toReturn = $changeFor - $total;
                $message .= "💵 À rendre: " . number_format($toReturn, 0, '', ' ') . " DA\n";
                $message .= "   (client donne " . number_format($changeFor, 0, '', ' ') . " DA)\n";
            }
        } else {
            $message .= "💳 *Paiement:* CARTE BANCAIRE\n";
        }

        $message .= "\n⏰ Commande reçue: " . date('H:i', strtotime($order['created_at'] ?? 'now'));

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
