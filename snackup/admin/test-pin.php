<?php
/**
 * Test du système PIN
 * Accès direct : https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-pin.php
 */

require_once __DIR__ . '/bootstrap.php';

// Vérifier connexion admin
echo "<h2>1. Vérification connexion admin</h2>";
echo "Admin connecté : " . (isAdminLoggedIn() ? "✅ OUI" : "❌ NON") . "<br>";
echo "Session ID : " . session_id() . "<br>";
echo "Session data : <pre>" . print_r($_SESSION, true) . "</pre>";

// Charger le PIN
echo "<h2>2. Fichier PIN</h2>";
$pinFile = __DIR__ . '/data/admin-pin.json';
echo "Chemin : " . $pinFile . "<br>";
echo "Existe : " . (file_exists($pinFile) ? "✅ OUI" : "❌ NON") . "<br>";

if (file_exists($pinFile)) {
    echo "Permissions : " . substr(sprintf('%o', fileperms($pinFile)), -4) . "<br>";
    echo "Contenu : <pre>" . file_get_contents($pinFile) . "</pre>";

    $data = json_decode(file_get_contents($pinFile), true);
    echo "PIN stocké : " . ($data['pin'] ?? 'N/A') . "<br>";
    echo "Longueur PIN : " . strlen($data['pin'] ?? '') . "<br>";
}

// Test de vérification PIN
echo "<h2>3. Test vérification PIN</h2>";
echo "<form method='post' style='margin:20px 0;'>
    <label>Entrer le PIN : <input type='text' name='test_pin' value='1234' style='padding:8px; font-size:16px;'></label>
    <button type='submit' style='padding:8px 16px; background:#3b82f6; color:#fff; border:none; border-radius:4px; cursor:pointer;'>Tester</button>
</form>";

if (isset($_POST['test_pin'])) {
    $testPin = trim($_POST['test_pin']);
    $stored = json_decode(file_get_contents($pinFile), true);

    echo "<div style='background:#1a1a2e; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "PIN saisi : '{$testPin}' (longueur: " . strlen($testPin) . ")<br>";
    echo "PIN stocké : '{$stored['pin']}' (longueur: " . strlen($stored['pin']) . ")<br>";
    echo "Comparaison stricte (===) : " . ($testPin === $stored['pin'] ? "✅ MATCH" : "❌ NO MATCH") . "<br>";
    echo "Comparaison souple (==) : " . ($testPin == $stored['pin'] ? "✅ MATCH" : "❌ NO MATCH") . "<br>";

    // Afficher les codes ASCII
    echo "<br>Codes ASCII PIN saisi : ";
    for ($i = 0; $i < strlen($testPin); $i++) {
        echo ord($testPin[$i]) . " ";
    }
    echo "<br>Codes ASCII PIN stocké : ";
    for ($i = 0; $i < strlen($stored['pin']); $i++) {
        echo ord($stored['pin'][$i]) . " ";
    }
    echo "</div>";
}

// Test API directement
echo "<h2>4. Test API (simulé)</h2>";
echo "<form method='post'>
    <input type='hidden' name='api_test' value='1'>
    <label>PIN pour test API : <input type='text' name='api_pin' value='1234' style='padding:8px; font-size:16px;'></label>
    <button type='submit' style='padding:8px 16px; background:#10b981; color:#fff; border:none; border-radius:4px; cursor:pointer;'>Test API</button>
</form>";

if (isset($_POST['api_test'])) {
    $apiPin = trim($_POST['api_pin']);

    echo "<div style='background:#1a1a2e; padding:15px; border-radius:8px; margin:10px 0;'>";
    echo "<h3>Simulation appel API</h3>";

    // Simuler ce que fait l'API
    $stored = json_decode(file_get_contents($pinFile), true);

    if (!isAdminLoggedIn()) {
        echo "❌ Erreur 401 : Admin non connecté<br>";
    } elseif ($apiPin === $stored['pin']) {
        echo "✅ PIN correct - Accès autorisé<br>";
        echo "Session serait déverrouillée : pin_unlocked = true<br>";
    } else {
        echo "❌ PIN incorrect (403)<br>";
        echo "Détails : '{$apiPin}' !== '{$stored['pin']}'<br>";
    }
    echo "</div>";
}

// JavaScript pour tester fetch
echo "<h2>5. Test JavaScript Fetch</h2>";
echo "<button onclick='testFetch()' style='padding:8px 16px; background:#8b5cf6; color:#fff; border:none; border-radius:4px; cursor:pointer;'>Test Fetch API</button>";
echo "<div id='fetchResult' style='background:#1a1a2e; padding:15px; border-radius:8px; margin:10px 0; display:none;'></div>";

echo "<script>
async function testFetch() {
    const result = document.getElementById('fetchResult');
    result.style.display = 'block';
    result.innerHTML = 'Envoi requête...';

    try {
        const response = await fetch('api/admin-pin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'verify', pin: '1234' })
        });

        result.innerHTML = 'Status: ' + response.status + '<br>';

        const data = await response.json();
        result.innerHTML += 'Réponse: <pre>' + JSON.stringify(data, null, 2) + '</pre>';

        if (data.success) {
            result.innerHTML += '<br>✅ PIN accepté !';
        } else {
            result.innerHTML += '<br>❌ PIN refusé : ' + data.message;
        }
    } catch (error) {
        result.innerHTML = '❌ Erreur: ' + error.message;
    }
}
</script>";

echo "<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; background: #0f0f1e; color: #fff; }
h2 { color: #3b82f6; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-top: 30px; }
pre { background: #000; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";
