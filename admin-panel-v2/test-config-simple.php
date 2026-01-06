<?php
// Test ultra-simple sans session
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CONFIG_FILE', '../config/fabrik-burger.config.js');

echo "<pre style='background:#1a1a2e;color:#fff;padding:20px;font-family:monospace;'>";
echo "========================================\n";
echo "TEST ULTRA-SIMPLE loadConfig()\n";
echo "========================================\n\n";

// Vérifier que le fichier existe
if (!file_exists(CONFIG_FILE)) {
    echo "❌ ERREUR: Fichier config n'existe pas à " . CONFIG_FILE . "\n";
    exit;
}

echo "✅ Fichier existe: " . realpath(CONFIG_FILE) . "\n";
echo "✅ Taille: " . filesize(CONFIG_FILE) . " bytes\n\n";

// Lire le contenu brut
$content = file_get_contents(CONFIG_FILE);
echo "✅ Contenu lu: " . strlen($content) . " bytes\n\n";

// Trouver le pattern
$pattern = '/(const|window\.)\s*SNACK_CONFIG\s*=\s*/';
if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
    echo "❌ Pattern SNACK_CONFIG non trouvé\n";
    exit;
}

echo "✅ Pattern trouvé: '" . $matches[0][0] . "'\n";
echo "✅ Position: " . $matches[0][1] . "\n\n";

// Extraire le JSON
$startPos = $matches[0][1] + strlen($matches[0][0]);
$jsonContent = substr($content, $startPos);

// Enlever le ;
$jsonContent = rtrim($jsonContent);
if (substr($jsonContent, -1) === ';') {
    $jsonContent = substr($jsonContent, 0, -1);
}
$jsonContent = trim($jsonContent);

echo "✅ JSON extrait: " . strlen($jsonContent) . " bytes\n\n";

// Nettoyage 1: Commentaires
$before = strlen($jsonContent);
$jsonContent = preg_replace('/\/\/[^\n]*/', '', $jsonContent);
$after = strlen($jsonContent);
echo "✅ Commentaires supprimés: -" . ($before - $after) . " bytes\n";

// Nettoyage 2: Guillemets clés
$jsonContent = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $jsonContent);
echo "✅ Guillemets ajoutés aux clés\n";

// Nettoyage 3: Trailing commas
$jsonContent = preg_replace('/,(\s*[}\]])/', '$1', $jsonContent);
echo "✅ Trailing commas supprimées\n";

// Nettoyage 4: Caractères de contrôle
$before = strlen($jsonContent);
$jsonContent = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $jsonContent);
$after = strlen($jsonContent);
echo "✅ Caractères de contrôle supprimés: -" . ($before - $after) . " bytes\n\n";

echo "📝 Premiers 300 caractères du JSON nettoyé:\n";
echo substr($jsonContent, 0, 300) . "...\n\n";

// Parser avec json_decode
echo "🔍 Tentative de parsing JSON...\n";
$decoded = json_decode($jsonContent, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

if ($decoded === null) {
    $error = json_last_error_msg();
    echo "❌❌❌ ÉCHEC PARSING: " . $error . "\n";
    echo "Code erreur: " . json_last_error() . "\n\n";

    // Sauvegarder pour debug
    file_put_contents(__DIR__ . '/data/debug-json-failed.txt', $jsonContent);
    echo "💾 JSON sauvegardé dans: data/debug-json-failed.txt\n\n";

    // Afficher les 500 premiers chars pour debug
    echo "📄 Début du JSON problématique:\n";
    echo substr($jsonContent, 0, 500) . "\n";

} else {
    echo "✅✅✅ SUCCÈS ! JSON parsé correctement\n\n";
    echo "📊 Données récupérées:\n";
    echo "  - Restaurant: " . ($decoded['name'] ?? 'N/A') . "\n";
    echo "  - ID: " . ($decoded['id'] ?? 'N/A') . "\n";
    echo "  - Slug: " . ($decoded['slug'] ?? 'N/A') . "\n";

    if (isset($decoded['menu']['categories'])) {
        echo "  - Catégories: " . count($decoded['menu']['categories']) . "\n";
        foreach ($decoded['menu']['categories'] as $cat) {
            $itemCount = isset($cat['items']) ? count($cat['items']) : 0;
            echo "    → " . $cat['name'] . " (" . $itemCount . " produits)\n";
        }
    }

    echo "\n🎉🎉🎉 CONFIGURATION CHARGÉE AVEC SUCCÈS !\n";
}

echo "\n========================================\n";
echo "</pre>";
?>
