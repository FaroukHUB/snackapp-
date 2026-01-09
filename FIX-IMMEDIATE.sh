#!/bin/bash
# Fix immédiat erreurs 404/503 côté navigateur
# À exécuter sur le serveur o2switch

cd ~/Marvelous.mon-agenceweb.fr

echo "=== FIX 1: Désactiver display_errors dans bootstrap.php ==="

# Ajouter ini_set au début de bootstrap.php
sed -i '1 a\
// Production: masquer les erreurs PHP\
ini_set("display_errors", "0");\
ini_set("log_errors", "1");\
error_reporting(E_ALL);' admin-panel-v2/bootstrap.php

echo "✅ display_errors désactivé"

echo ""
echo "=== FIX 2: Vérifier restaurant.json ==="

# Vérifier si restaurant.json a des caractères de contrôle
if grep -P '[\x00-\x08\x0B\x0C\x0E-\x1F]' config/restaurant.json > /dev/null 2>&1; then
    echo "⚠️ Caractères de contrôle détectés dans restaurant.json"
    # Backup
    cp config/restaurant.json config/restaurant.json.backup.$(date +%s)
    # Nettoyer
    tr -d '\000-\010\013\014\016-\037' < config/restaurant.json > config/restaurant.json.clean
    mv config/restaurant.json.clean config/restaurant.json
    echo "✅ restaurant.json nettoyé"
else
    echo "✅ restaurant.json OK (pas de caractères de contrôle)"
fi

echo ""
echo "=== FIX 3: Tester l'API ==="

# Tester products.php
RESPONSE=$(curl -s -w "\n%{http_code}" "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/products.php?action=list")
HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | head -n -1)

if [ "$HTTP_CODE" = "200" ]; then
    # Vérifier que c'est du JSON valide (commence par {)
    FIRST_CHAR=$(echo "$BODY" | head -c 1)
    if [ "$FIRST_CHAR" = "{" ]; then
        echo "✅ API products.php retourne du JSON valide (200)"
    else
        echo "❌ API retourne 200 mais pas du JSON pur"
        echo "Premiers caractères: $(echo "$BODY" | head -c 100)"
    fi
else
    echo "❌ API retourne $HTTP_CODE"
    echo "$BODY" | head -20
fi

echo ""
echo "=== TERMINÉ ==="
echo "Testez maintenant dans le navigateur:"
echo "https://marvelous.mon-agenceweb.fr/admin-panel-v2/"
