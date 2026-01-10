<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction Chemins WebP</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #00ff00;
        }
        .output {
            background: #000;
            padding: 20px;
            border-radius: 8px;
            white-space: pre-wrap;
            line-height: 1.5;
            border: 2px solid #00ff00;
        }
        button {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        button:hover {
            background: #00cc00;
        }
        button:disabled {
            background: #666;
            cursor: not-allowed;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
    </style>
</head>
<body>
    <h1>🔧 Correction Chemins WebP</h1>
    <p>Ce script va mettre à jour tous les chemins d'images dans la base de données pour pointer vers les fichiers WebP.</p>

    <button onclick="runFix()" id="runBtn">▶️ Lancer la correction</button>

    <div class="output" id="output">En attente...</div>

    <script>
        async function runFix() {
            const output = document.getElementById('output');
            const btn = document.getElementById('runBtn');

            output.textContent = '🔄 Correction en cours...\n\n';
            btn.disabled = true;

            try {
                const response = await fetch('fix-webp-execute.php');
                const text = await response.text();
                output.innerHTML = text.replace(/\n/g, '<br>');
            } catch (error) {
                output.innerHTML = '<span class="error">❌ Erreur: ' + error.message + '</span>';
            } finally {
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
