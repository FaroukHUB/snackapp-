<?php
require_once 'config.php';
requireLogin();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API Products</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #fff; padding: 20px; }
        button { padding: 10px 20px; margin: 10px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #059669; }
        .log { background: #2a2a3e; padding: 15px; margin: 10px 0; border-radius: 8px; overflow-x: auto; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <h1>🧪 Test API Products</h1>
    <p><a href="products-manager.php" style="color: #10b981;">← Retour à Products Manager</a></p>

    <hr>

    <h2>1. Test Connexion API</h2>
    <button onclick="testApiConnection()">Tester GET /api/products.php?action=list</button>
    <div id="test1" class="log"></div>

    <h2>2. Test Ajout Produit</h2>
    <button onclick="testAddProduct()">Tester POST /api/products.php (action=add)</button>
    <div id="test2" class="log"></div>

    <h2>3. Vérification Config File</h2>
    <button onclick="testConfigFile()">Vérifier config/fabrik-burger.config.js</button>
    <div id="test3" class="log"></div>

    <script>
        function log(id, message, isError = false) {
            const el = document.getElementById(id);
            el.innerHTML += `<div class="${isError ? 'error' : 'success'}">${message}</div>`;
        }

        function clear(id) {
            document.getElementById(id).innerHTML = '';
        }

        async function testApiConnection() {
            clear('test1');
            log('test1', '🔍 Test en cours...');

            try {
                const response = await fetch('api/products.php?action=list');
                log('test1', `📡 Statut HTTP: ${response.status} ${response.statusText}`);

                const data = await response.json();
                log('test1', `✅ Réponse JSON: ${JSON.stringify(data, null, 2)}`);

                if (data.success && data.products) {
                    log('test1', `✅ ${data.products.length} produits chargés`);
                }
            } catch (err) {
                log('test1', `❌ Erreur: ${err.message}`, true);
            }
        }

        async function testAddProduct() {
            clear('test2');
            log('test2', '🔍 Test ajout produit...');

            const testData = {
                action: 'add',
                name: 'TEST PRODUIT ' + Date.now(),
                category_id: 'burgers',
                price_solo: '9.99',
                price_menu: '12.99'
            };

            log('test2', `📦 Données: ${JSON.stringify(testData, null, 2)}`);

            try {
                const response = await fetch('api/products.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testData)
                });

                log('test2', `📡 Statut HTTP: ${response.status} ${response.statusText}`);

                const data = await response.json();
                log('test2', `✅ Réponse: ${JSON.stringify(data, null, 2)}`);

                if (data.success) {
                    log('test2', `✅ SUCCÈS ! Produit ajouté.`);
                } else {
                    log('test2', `❌ ÉCHEC: ${data.error}`, true);
                }
            } catch (err) {
                log('test2', `❌ Erreur: ${err.message}`, true);
            }
        }

        async function testConfigFile() {
            clear('test3');
            log('test3', '🔍 Vérification config file...');

            try {
                const response = await fetch('test-config-check.php');
                const data = await response.json();

                log('test3', `📁 Fichier: ${data.file}`);
                log('test3', `✅ Existe: ${data.exists ? 'OUI' : 'NON'}`, !data.exists);
                log('test3', `✅ Lisible: ${data.readable ? 'OUI' : 'NON'}`, !data.readable);
                log('test3', `✅ Inscriptible: ${data.writable ? 'OUI' : 'NON'}`, !data.writable);
                log('test3', `📏 Taille: ${data.size} octets`);
                log('test3', `📅 Dernière modif: ${data.modified}`);
            } catch (err) {
                log('test3', `❌ Erreur: ${err.message}`, true);
            }
        }
    </script>
</body>
</html>
