<?php
require_once 'admin-panel-v2/config.php';

$config = loadConfig();

echo "🔍 DEBUG: Qu'est-ce qu'il y a dans \$config?\n";
echo "==========================================\n\n";

echo "1️⃣ \$config existe? " . (isset($config) ? "OUI ✅" : "NON ❌") . "\n";
echo "2️⃣ \$config['menu'] existe? " . (isset($config['menu']) ? "OUI ✅" : "NON ❌") . "\n";
echo "3️⃣ \$config['menu']['categories'] existe? " . (isset($config['menu']['categories']) ? "OUI ✅" : "NON ❌") . "\n";

if (isset($config['menu']['categories'])) {
    $cats = $config['menu']['categories'];
    echo "4️⃣ Nombre de catégories: " . count($cats) . "\n\n";
    
    if (count($cats) > 0) {
        echo "5️⃣ Première catégorie:\n";
        echo "   " . json_encode($cats[0], JSON_PRETTY_PRINT) . "\n\n";
        
        echo "6️⃣ Liste des IDs:\n";
        foreach ($cats as $i => $cat) {
            echo "   [$i] id = " . ($cat['id'] ?? 'MANQUANT') . ", name = " . ($cat['name'] ?? 'MANQUANT') . "\n";
        }
    } else {
        echo "❌ Le tableau de catégories est VIDE!\n";
    }
}

echo "\n7️⃣ loadConfig() charge depuis quel fichier?\n";
echo "   Vérifiez le code de loadConfig() dans config.php\n";
