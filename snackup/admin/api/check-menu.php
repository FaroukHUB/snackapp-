<?php
/**
 * Diagnostic ultra-simple pour menu.json
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC MENU.JSON ===\n\n";

// Chemin direct
$menuPath = __DIR__ . '/../../config/menu.json';

echo "1. Chemin construit:\n";
echo "   __DIR__ = " . __DIR__ . "\n";
echo "   Chemin menu.json = " . $menuPath . "\n";
echo "   Chemin absolu = " . realpath(dirname($menuPath)) . "\n\n";

echo "2. Vérifications:\n";
echo "   Fichier existe? " . (file_exists($menuPath) ? "OUI" : "NON") . "\n";

if (file_exists($menuPath)) {
    echo "   Taille: " . filesize($menuPath) . " octets\n";
    echo "   Lisible? " . (is_readable($menuPath) ? "OUI" : "NON") . "\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($menuPath)), -4) . "\n\n";

    echo "3. Contenu:\n";
    $content = @file_get_contents($menuPath);
    if ($content === false) {
        echo "   ❌ ERREUR lecture: " . error_get_last()['message'] . "\n";
    } else {
        echo "   ✅ Lu avec succès: " . strlen($content) . " octets\n";
        echo "   Premiers 200 caractères:\n";
        echo "   " . substr($content, 0, 200) . "...\n\n";

        echo "4. Parsing JSON:\n";
        $data = json_decode($content, true);
        if ($data === null) {
            echo "   ❌ ERREUR JSON: " . json_last_error_msg() . "\n";
        } else {
            echo "   ✅ JSON valide\n";
            echo "   Type: " . gettype($data) . "\n";
            echo "   Nombre de clés: " . count($data) . "\n";
            echo "   Clés: " . implode(', ', array_keys($data)) . "\n";
            echo "   Est vide? " . (empty($data) ? "OUI" : "NON") . "\n";
            echo "   Est truthy? " . ($data ? "OUI" : "NON") . "\n";

            if (isset($data['supplements']['catalog'])) {
                echo "   Suppléments: " . count($data['supplements']['catalog']) . " items\n";
            }
            if (isset($data['menu']['categories'])) {
                echo "   Catégories: " . count($data['menu']['categories']) . " items\n";
            }
        }
    }
} else {
    echo "\n❌ FICHIER INTROUVABLE!\n";
    echo "\nContenu du dossier config:\n";
    $configDir = dirname($menuPath);
    if (is_dir($configDir)) {
        $files = scandir($configDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "   - $file\n";
            }
        }
    } else {
        echo "   Le dossier config n'existe pas!\n";
    }
}

echo "\n=== FIN DIAGNOSTIC ===\n";
