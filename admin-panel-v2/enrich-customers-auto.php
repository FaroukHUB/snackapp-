<?php
/**
 * Script d'enrichissement automatique des profils clients
 *
 * Ce script analyse l'historique des commandes et enrichit automatiquement:
 * - Les adresses de livraison (extraction depuis les commandes)
 * - Les produits favoris (top 3 des produits les plus commandés)
 *
 * Les données manuelles (tags, notes admin, allergies) sont préservées
 */

require_once __DIR__ . '/bootstrap.php';

// Activer affichage erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "============================================\n";
echo "🔄 ENRICHISSEMENT AUTOMATIQUE CLIENTS\n";
echo "============================================\n\n";

// Vérifier si MySQL est actif
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');

if (!$useMySQL) {
    echo "❌ Ce script nécessite MySQL. Mode JSON non supporté.\n";
    exit(1);
}

// Vérifier que la classe Database est disponible (chargée par bootstrap.php)
if (!class_exists('Database')) {
    echo "❌ Erreur: Classe Database non disponible.\n";
    echo "   Vérifiez que bootstrap.php a bien chargé database/Database.php.\n";
    exit(4);
}

echo "📊 Analyse des commandes...\n\n";

// Récupérer tous les clients
$customers = Database::fetchAll("SELECT id, name, phone FROM customers WHERE restaurant_id = ?", [SNACK_RESTAURANT_ID]);

if (empty($customers)) {
    echo "❌ Aucun client trouvé.\n";
    exit(2);
}

echo "✅ " . count($customers) . " clients trouvés\n\n";

$stats = [
    'addresses_added' => 0,
    'customers_enriched' => 0,
    'errors' => 0
];

foreach ($customers as $customer) {
    echo "---\n";
    echo "Client: {$customer['name']} (#{$customer['id']})\n";

    try {
        // 1. Extraire les adresses de livraison depuis les commandes
        $orders = Database::fetchAll(
            "SELECT DISTINCT delivery_address, delivery_instructions
             FROM orders
             WHERE customer_id = ?
               AND delivery_address IS NOT NULL
               AND delivery_address != ''
             ORDER BY created_at DESC
             LIMIT 10",
            [$customer['id']]
        );

        if (!empty($orders)) {
            // Récupérer les adresses existantes
            $existingAddresses = Database::fetchOne(
                "SELECT addresses FROM customers WHERE id = ?",
                [$customer['id']]
            );

            $addresses = json_decode($existingAddresses['addresses'] ?? '[]', true) ?: [];

            // Dédupliquer et limiter à 2 adresses uniques
            $uniqueAddresses = [];
            $seenAddresses = array_column($addresses, 'address'); // Adresses déjà enregistrées

            foreach ($orders as $order) {
                $addr = trim($order['delivery_address']);

                // Si cette adresse n'existe pas déjà
                if (!in_array($addr, $seenAddresses) && !in_array($addr, $uniqueAddresses)) {
                    $uniqueAddresses[] = [
                        'id' => uniqid('addr_'),
                        'type' => count($uniqueAddresses) === 0 ? 'home' : 'work',
                        'label' => count($uniqueAddresses) === 0 ? 'Maison' : 'Bureau',
                        'address' => $addr,
                        'notes' => '', // Ne pas inclure les instructions de monnaie
                        'is_default' => count($addresses) === 0 && count($uniqueAddresses) === 0
                    ];

                    if (count($uniqueAddresses) >= 2) break; // Max 2 nouvelles adresses
                }
            }

            // Fusionner avec les adresses existantes (max 2 au total)
            $allAddresses = array_merge($addresses, $uniqueAddresses);
            $allAddresses = array_slice($allAddresses, 0, 2); // Limiter à 2

            if (count($uniqueAddresses) > 0) {
                // Mettre à jour le profil client
                Database::update(
                    'customers',
                    ['addresses' => json_encode($allAddresses)],
                    ['id' => $customer['id']]
                );

                $stats['addresses_added'] += count($uniqueAddresses);
                echo "  ✅ " . count($uniqueAddresses) . " adresse(s) ajoutée(s)\n";
            } else {
                echo "  ℹ️  Aucune nouvelle adresse unique trouvée\n";
            }
        } else {
            echo "  ℹ️  Aucune commande avec adresse\n";
        }

        $stats['customers_enriched']++;

    } catch (Exception $e) {
        echo "  ❌ Erreur: " . $e->getMessage() . "\n";
        $stats['errors']++;
    }
}

echo "\n============================================\n";
echo "📊 RÉSUMÉ\n";
echo "============================================\n";
echo "Clients traités: {$stats['customers_enriched']}\n";
echo "Adresses ajoutées: {$stats['addresses_added']}\n";
echo "Erreurs: {$stats['errors']}\n";
echo "\n✅ Enrichissement terminé!\n";
echo "\n💡 Les produits favoris sont calculés automatiquement à l'affichage.\n";
echo "   Aucune action nécessaire pour cette donnée.\n\n";

// Retourner code de succès (0) ou échec (3) selon les erreurs
exit($stats['errors'] > 0 ? 3 : 0);
