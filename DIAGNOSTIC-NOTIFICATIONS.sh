#!/bin/bash
# Diagnostic système notifications - À exécuter sur o2switch

cd ~/Marvelous.mon-agenceweb.fr

echo "=== DIAGNOSTIC NOTIFICATIONS COMMANDES ==="
echo ""

echo "1. Fichier son existe?"
ls -lh admin-panel-v2/assets/sounds/commande.mp3 2>&1

echo ""
echo "2. notification-sound.js chargé dans index.php?"
grep -n "notification-sound.js" admin-panel-v2/index.php

echo ""
echo "3. Script démarré dans index.php?"
grep -n "orderNotificationSystem.start" admin-panel-v2/index.php

echo ""
echo "4. Test API orders.php (ce que le JS appelle)"
curl -s "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php?action=list&limit=1" 2>&1 | head -100

echo ""
echo "5. Test API nécessite-t-elle authentification?"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php?action=list&limit=1")
echo "Code HTTP: $HTTP_CODE"

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ API accessible sans session"
elif [ "$HTTP_CODE" = "302" ] || [ "$HTTP_CODE" = "401" ] || [ "$HTTP_CODE" = "403" ]; then
    echo "❌ API requiert authentification (normal)"
    echo "Les notifications fonctionneront SEULEMENT quand admin est connecté"
else
    echo "⚠️ Code inattendu: $HTTP_CODE"
fi

echo ""
echo "=== INSTRUCTIONS TEST NAVIGATEUR ==="
echo "1. Ouvrez: https://marvelous.mon-agenceweb.fr/admin-panel-v2/"
echo "2. Connectez-vous"
echo "3. Ouvrez la console (F12)"
echo "4. Tapez: window.orderNotificationSystem"
echo "5. Regardez l'onglet Network - voyez-vous des requêtes vers orders.php toutes les 10 secondes?"
echo ""
echo "Si vous voyez des erreurs rouges dans la console, copiez-les!"
