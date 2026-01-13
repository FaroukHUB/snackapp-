<?php
/**
 * Script de correction menu.json - Ajoute priceSolo=0 aux produits sans prix
 * Résout le problème: API 400 Bad Request + Admin panel vide
 */

$menuPath = __DIR__ . '/../config/menu.json';

// Backup avant modification
$backupPath = $menuPath . '.backup-' . date('Ymd-His');
copy($menuPath, $backupPath);
echo "✅ Backup créé: $backupPath\n\n";

// Charger menu.json
$menuData = json_decode(file_get_contents($menuPath), true);

if (!$menuData) {
    die("❌ Erreur: Impossible de lire menu.json\n");
}

// Corriger les produits sans prix
$fixedCount = 0;
foreach ($menuData['menu']['categories'] as &$category) {
    foreach ($category['items'] as &$item) {
        // Si le produit n'a pas de priceSolo ou est null, lui donner 0
        if (!isset($item['priceSolo']) || $item['priceSolo'] === null) {
            $item['priceSolo'] = 0;
            $fixedCount++;
            echo "✅ Corrigé: " . ($item['name'] ?? 'sans nom') . " → priceSolo = 0\n";
        }
    }
}

// Sauvegarder
$json = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents($menuPath, $json);

echo "\n";
echo "✅ $fixedCount produits corrigés\n";
echo "✅ menu.json sauvegardé\n";
echo "\n";
echo "🔄 Rechargez maintenant l'admin panel dans votre navigateur\n";
