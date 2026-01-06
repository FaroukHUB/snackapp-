<?php
/**
 * Script de diagnostic pour identifier le problème products.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC PRODUCTS.PHP ===\n\n";

// 1. Vérifier les constantes
echo "1. CONSTANTES\n";
echo "   __DIR__ = " . __DIR__ . "\n";
echo "   dirname(__DIR__) = " . dirname(__DIR__) . "\n";

// 2. Charger bootstrap
try {
    require_once __DIR__ . '/../bootstrap.php';
    echo "   ✅ bootstrap.php chargé\n";
    echo "   SNACK_ROOT = " . (defined('SNACK_ROOT') ? SNACK_ROOT : 'NON DÉFINI') . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur bootstrap.php: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Vérifier menu.json
echo "\n2. FICHIER MENU.JSON\n";
$menuJsonPath = SNACK_ROOT . '/config/menu.json';
echo "   Chemin: $menuJsonPath\n";
echo "   Existe: " . (file_exists($menuJsonPath) ? "OUI" : "NON") . "\n";

if (file_exists($menuJsonPath)) {
    echo "   Taille: " . filesize($menuJsonPath) . " octets\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($menuJsonPath)), -4) . "\n";
    echo "   Readable: " . (is_readable($menuJsonPath) ? "OUI" : "NON") . "\n";

    // 4. Tester chargement JSON
    echo "\n3. CHARGEMENT JSON\n";
    $content = @file_get_contents($menuJsonPath);
    if ($content === false) {
        echo "   ❌ Impossible de lire le fichier\n";
        echo "   Erreur: " . error_get_last()['message'] . "\n";
    } else {
        echo "   ✅ Fichier lu: " . strlen($content) . " octets\n";

        $menuData = json_decode($content, true);
        if ($menuData === null) {
            echo "   ❌ Erreur JSON: " . json_last_error_msg() . "\n";
        } else {
            echo "   ✅ JSON décodé\n";
            echo "   Type: " . gettype($menuData) . "\n";
            echo "   Vide: " . (empty($menuData) ? "OUI" : "NON") . "\n";
            echo "   Truthy: " . ($menuData ? "OUI" : "NON") . "\n";
            echo "   Clés: " . implode(', ', array_keys($menuData)) . "\n";
        }
    }
}

// 5. Vérifier runtime
echo "\n4. FICHIER RUNTIME\n";
try {
    $runtime = loadMenuRuntime();
    echo "   ✅ Runtime chargé\n";
    echo "   Type: " . gettype($runtime) . "\n";
    echo "   Clés: " . implode(', ', array_keys($runtime)) . "\n";
} catch (Exception $e) {
    echo "   ❌ Erreur runtime: " . $e->getMessage() . "\n";
}

// 6. Tester le code complet de products.php GET
echo "\n5. TEST COMPLET PRODUCTS.PHP GET\n";
try {
    if (file_exists($menuJsonPath)) {
        $menuData = json_decode(file_get_contents($menuJsonPath), true);

        if ($menuData) {
            echo "   ✅ if (\$menuData) passe\n";

            require_once __DIR__ . '/../config.php';
            $runtime = loadMenuRuntime();
            echo "   ✅ Runtime chargé dans bloc\n";

            // Tester les supplements
            $supplements = $menuData['supplements'] ?? [
                'catalog' => [],
                'defaultForCategories' => []
            ];
            echo "   Suppléments: " . count($supplements['catalog'] ?? []) . " items\n";

            echo "   ✅ Tout fonctionne!\n";
        } else {
            echo "   ❌ if (\$menuData) échoue (valeur falsy)\n";
            echo "   Valeur: " . var_export($menuData, true) . "\n";
        }
    } else {
        echo "   ❌ Fichier n'existe pas\n";
    }
} catch (Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
