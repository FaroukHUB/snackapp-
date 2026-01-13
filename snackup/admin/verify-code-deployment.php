<?php
/**
 * Vérification du déploiement du code de capture d'adresse
 */

echo "=== VÉRIFICATION DÉPLOIEMENT CODE CAPTURE ADRESSE ===\n\n";

// 1. Vérifier template-v2/cart.html
echo "1️⃣ Vérification template-v2/cart.html:\n";
$cartPath = __DIR__ . '/../template-v2/cart.html';

if (!file_exists($cartPath)) {
    echo "   ❌ ERREUR: Fichier cart.html introuvable à: $cartPath\n";
    exit(1);
}

$cartContent = file_get_contents($cartPath);

// Vérifier la présence du code de capture
$checks = [
    'deliveryAddress' => strpos($cartContent, 'deliveryAddress = fullAddress') !== false,
    'delivery_address_field' => strpos($cartContent, 'delivery_address:') !== false,
    'delivery_instructions_field' => strpos($cartContent, 'delivery_instructions:') !== false,
    'riad_city_capture' => strpos($cartContent, 'Riad City Bât') !== false,
];

foreach ($checks as $check => $found) {
    $icon = $found ? '✅' : '❌';
    echo "   {$icon} {$check}\n";
}

$allFrontendOk = !in_array(false, $checks, true);

if ($allFrontendOk) {
    echo "   ✅ Frontend OK - Code de capture présent\n";
} else {
    echo "   ❌ Frontend MANQUANT - Code de capture absent!\n";
    echo "   → Le fichier cart.html n'a pas le code de capture\n";
}

echo "\n";

// 2. Vérifier database/repositories/OrderRepository.php
echo "2️⃣ Vérification database/repositories/OrderRepository.php:\n";
$repoPath = __DIR__ . '/../database/repositories/OrderRepository.php';

if (!file_exists($repoPath)) {
    echo "   ❌ ERREUR: Fichier OrderRepository.php introuvable à: $repoPath\n";
    exit(2);
}

$repoContent = file_get_contents($repoPath);

// Vérifier la présence du code de sauvegarde
$backendChecks = [
    'delivery_address_save' => strpos($repoContent, "'delivery_address' =>") !== false,
    'delivery_instructions_save' => strpos($repoContent, "'delivery_instructions' =>") !== false,
    'data_delivery_address' => strpos($repoContent, "\$data['delivery_address']") !== false,
];

foreach ($backendChecks as $check => $found) {
    $icon = $found ? '✅' : '❌';
    echo "   {$icon} {$check}\n";
}

$allBackendOk = !in_array(false, $backendChecks, true);

if ($allBackendOk) {
    echo "   ✅ Backend OK - Code de sauvegarde présent\n";
} else {
    echo "   ❌ Backend MANQUANT - Code de sauvegarde absent!\n";
    echo "   → Le fichier OrderRepository.php n'a pas le code de sauvegarde\n";
}

echo "\n";

// 3. Résumé
echo "3️⃣ RÉSUMÉ:\n";
if ($allFrontendOk && $allBackendOk) {
    echo "   ✅ Le code est bien déployé!\n";
    echo "   → Problème probable: CACHE NAVIGATEUR\n";
    echo "   → Solution: Vider le cache (Ctrl+Shift+R ou Ctrl+F5)\n";
    echo "   → Tester avec console ouverte (F12) pour voir 'Order data being sent'\n";
} elseif (!$allFrontendOk && !$allBackendOk) {
    echo "   ❌ Frontend ET Backend manquants!\n";
    echo "   → Le git pull n'a pas mis à jour les fichiers\n";
    echo "   → Vérifier que vous êtes dans le bon répertoire\n";
} elseif (!$allFrontendOk) {
    echo "   ❌ Frontend manquant!\n";
    echo "   → template-v2/cart.html n'a pas le code de capture\n";
    echo "   → Le git pull n'a pas mis à jour ce fichier\n";
} else {
    echo "   ❌ Backend manquant!\n";
    echo "   → OrderRepository.php n'a pas le code de sauvegarde\n";
    echo "   → Le git pull n'a pas mis à jour ce fichier\n";
}

echo "\n✅ Vérification terminée\n";
