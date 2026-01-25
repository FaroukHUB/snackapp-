<?php
require_once __DIR__ . '/config.php';

// Lire les commandes
$ordersFile = DATA_DIR . 'orders.json';
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true);
    
    echo "<h2>Dernières 5 commandes - Analyse du champ notes:</h2>";
    foreach (array_slice($orders, -5) as $order) {
        echo "<hr>";
        echo "<strong>Commande #{$order['id']}</strong><br>";
        echo "Notes: <code>" . htmlspecialchars($order['notes'] ?? 'VIDE') . "</code><br>";
        echo "Mode notes: <code>" . htmlspecialchars($order['mode_notes'] ?? 'VIDE') . "</code><br>";
        
        // Test de détection
        $hasLivraison = isset($order['notes']) && stripos($order['notes'], 'livraison') !== false;
        echo "Contient 'livraison'? " . ($hasLivraison ? "✅ OUI" : "❌ NON") . "<br>";
    }
} else {
    echo "Fichier orders.json introuvable";
}
?>
