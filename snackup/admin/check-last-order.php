<?php
/**
 * Vérifier la dernière commande pour voir si l'adresse est sauvegardée
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== VÉRIFICATION DERNIÈRE COMMANDE ===\n\n";

// Récupérer la dernière commande
$order = Database::fetchOne(
    "SELECT order_number, customer_name, delivery_address, delivery_instructions, notes, created_at
     FROM orders
     ORDER BY created_at DESC
     LIMIT 1"
);

if (!$order) {
    echo "❌ Aucune commande trouvée\n";
    exit(1);
}

echo "Commande: {$order['order_number']}\n";
echo "Client: {$order['customer_name']}\n";
echo "Date: {$order['created_at']}\n";
echo "\n";

echo "📍 Adresse de livraison: ";
if (!empty($order['delivery_address'])) {
    echo "✅ {$order['delivery_address']}\n";
} else {
    echo "❌ NULL (pas d'adresse)\n";
}

echo "ℹ️  Instructions: ";
if (!empty($order['delivery_instructions'])) {
    echo "✅ {$order['delivery_instructions']}\n";
} else {
    echo "❌ NULL (pas d'instructions)\n";
}

echo "\n📝 Notes complètes:\n";
echo $order['notes'] . "\n";

echo "\n";

// Résumé
if (!empty($order['delivery_address'])) {
    echo "✅ ✅ ✅ SUCCÈS! L'adresse est bien sauvegardée en BDD!\n";
    echo "→ Le problème était bien le cache du navigateur\n";
    echo "→ Les nouvelles commandes auront l'adresse\n";
} else {
    echo "❌ PROBLÈME: L'adresse n'est PAS sauvegardée en BDD\n";
    echo "→ Le frontend envoie bien les données (vu dans console)\n";
    echo "→ Mais le backend ne les sauvegarde pas\n";
    echo "→ Il faut vérifier OrderRepository.php\n";
}
