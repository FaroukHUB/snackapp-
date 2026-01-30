<?php
/**
 * Debug: Test sauvegarde settings
 */
require_once __DIR__ . '/bootstrap.php';
requireAdmin();

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG SAVE SETTINGS ===\n\n";
echo "SNACK_RESTAURANT_ID: " . SNACK_RESTAURANT_ID . "\n";
echo "useMySQL: " . (defined('SNACK_USE_JSON') && !SNACK_USE_JSON ? 'OUI' : 'NON') . "\n\n";

// Test lecture
echo "--- LECTURE ACTUELLE ---\n";
$settings = RestaurantRepository::getSettings(SNACK_RESTAURANT_ID);
echo "instagram: " . ($settings['instagram'] ?? 'VIDE') . "\n";
echo "facebook: " . ($settings['facebook'] ?? 'VIDE') . "\n\n";

// Test écriture
echo "--- TEST ECRITURE ---\n";
$testData = [
    'instagram' => 'https://instagram.com/test_' . time(),
    'facebook' => 'https://facebook.com/test_' . time()
];
echo "Données à écrire: " . json_encode($testData) . "\n";

$result = RestaurantRepository::updateSettings(SNACK_RESTAURANT_ID, $testData);
echo "Résultat updateSettings: " . ($result ? 'SUCCESS' : 'ECHEC') . "\n\n";

// Vérifier
echo "--- VERIFICATION ---\n";
$settingsAfter = RestaurantRepository::getSettings(SNACK_RESTAURANT_ID);
echo "instagram après: " . ($settingsAfter['instagram'] ?? 'VIDE') . "\n";
echo "facebook après: " . ($settingsAfter['facebook'] ?? 'VIDE') . "\n";
