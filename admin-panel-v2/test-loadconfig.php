<?php
// Test simple de loadConfig() sans authentification
define('CONFIG_FILE', '../config/fabrik-burger.config.js');

function loadConfig() {
    $content = file_get_contents(CONFIG_FILE);

    // Trouver le début : "window.SNACK_CONFIG = " ou "const SNACK_CONFIG = "
    $pattern = '/(const|window\.)\s*SNACK_CONFIG\s*=\s*/';
    if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        echo "❌ Pattern SNACK_CONFIG non trouvé\n";
        return null;
    }

    echo "✅ Pattern trouvé: " . $matches[0][0] . "\n";
    echo "✅ Position: " . $matches[0][1] . "\n";

    // Position de début du JSON (juste après le "=")
    $startPos = $matches[0][1] + strlen($matches[0][0]);
    $jsonContent = substr($content, $startPos);

    // Enlever le point-virgule final et les espaces
    $jsonContent = rtrim($jsonContent);
    if (substr($jsonContent, -1) === ';') {
        $jsonContent = substr($jsonContent, 0, -1);
    }
    $jsonContent = trim($jsonContent);

    echo "✅ JSON length (brut): " . strlen($jsonContent) . " bytes\n";

    // ⚠️ IMPORTANT: Convertir le JavaScript object notation en JSON valide

    // 1. Enlever les commentaires JavaScript (// ...)
    $jsonContent = preg_replace('/\/\/[^\n]*/', '', $jsonContent);
    echo "✅ Après suppression commentaires: " . strlen($jsonContent) . " bytes\n";

    // 2. Ajouter des guillemets autour des clés non quotées
    $jsonContent = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $jsonContent);
    echo "✅ Après ajout guillemets aux clés\n";

    // 3. Enlever les trailing commas
    $jsonContent = preg_replace('/,(\s*[}\]])/', '$1', $jsonContent);
    echo "✅ Après nettoyage trailing commas\n";

    // 4. Nettoyer les caractères de contrôle invalides
    $jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $jsonContent);
    echo "✅ Après nettoyage caractères de contrôle\n";

    echo "✅ Premiers 200 chars: " . substr($jsonContent, 0, 200) . "...\n";

    // Parser le JSON avec options permissives
    $decoded = json_decode($jsonContent, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

    if ($decoded === null) {
        echo "❌ Erreur de parsing JSON: " . json_last_error_msg() . "\n";
        return null;
    }

    echo "✅ JSON parsé avec succès!\n";
    echo "✅ Restaurant name: " . ($decoded['name'] ?? 'N/A') . "\n";
    echo "✅ Nombre de catégories: " . (isset($decoded['menu']['categories']) ? count($decoded['menu']['categories']) : 0) . "\n";

    return $decoded;
}

echo "<pre>";
echo "========================================\n";
echo "TEST loadConfig() pour Fabrik Burger\n";
echo "========================================\n\n";

$config = loadConfig();

if ($config) {
    echo "\n✅✅✅ SUCCÈS ! Configuration chargée.\n";
} else {
    echo "\n❌❌❌ ÉCHEC ! Configuration non chargée.\n";
}

echo "</pre>";
?>
