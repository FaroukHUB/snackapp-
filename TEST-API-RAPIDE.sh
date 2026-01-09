#!/bin/bash
# Test rapide API pour identifier l'erreur exacte

echo "=== TEST API MARVELOUS - DIAGNOSTIC ERREUR ==="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

echo "1. Vérification fichier .htaccess..."
if [ -f "admin-panel-v2/api/.htaccess" ]; then
    echo "✅ Fichier .htaccess existe"
    echo "Contenu:"
    cat admin-panel-v2/api/.htaccess
else
    echo "❌ ERREUR: .htaccess manquant!"
fi

echo ""
echo "2. Test GET simple (devrait retourner JSON)..."
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php?action=check_new")
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Code HTTP: $HTTP_CODE"
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ GET fonctionne - Réponse JSON:"
    echo "$BODY" | head -c 200
else
    echo "❌ ERREUR sur GET!"
    echo "Réponse (premiers 500 caractères):"
    echo "$BODY" | head -c 500
    echo ""
    echo ">>> Si vous voyez du HTML ici, le .htaccess cause une erreur 500"
fi

echo ""
echo ""
echo "3. Test POST commande..."
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST \
  "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php" \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
  -d '{"action":"add","customer_phone":"0555888999","customer_name":"Test Diagnostic","items":[{"id":"1","name":"Burger Test","price":500,"quantity":1}],"total":500,"subtotal":500}')

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Code HTTP: $HTTP_CODE"
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ POST fonctionne! Problème résolu!"
    echo "Réponse:"
    echo "$BODY" | head -c 300
elif echo "$BODY" | grep -q "<!DOCTYPE\|<html\|<HTML"; then
    echo "❌ L'API retourne du HTML au lieu de JSON"
    echo ""
    echo "Réponse HTML (premiers 800 caractères):"
    echo "$BODY" | head -c 800
    echo ""
    echo ""
    echo "=== SOLUTION ==="
    if echo "$BODY" | grep -qi "internal server error\|500"; then
        echo "→ Erreur 500: Le .htaccess a une erreur de syntaxe"
        echo "→ SUPPRIMER le fichier .htaccess temporairement:"
        echo "   rm admin-panel-v2/api/.htaccess"
        echo ""
        echo "→ Si ça marche après suppression, le problème vient du .htaccess"
        echo "→ Contactez o2switch pour whitelist l'API dans ModSecurity"
    elif echo "$BODY" | grep -qi "service unavailable\|503"; then
        echo "→ Erreur 503: ModSecurity bloque toujours"
        echo "→ Le .htaccess ne suffit pas sur ce serveur"
        echo "→ Il faut contacter o2switch pour désactiver ModSecurity sur /admin-panel-v2/api/"
    else
        echo "→ Erreur inconnue, voir le HTML ci-dessus"
    fi
else
    echo "⚠️ Réponse inattendue:"
    echo "$BODY" | head -c 500
fi

echo ""
echo ""
echo "4. Logs d'erreur PHP (20 dernières lignes)..."
echo ""
find ~/logs ~/Marvelous.mon-agenceweb.fr -name "error_log" -o -name "error.log" 2>/dev/null | while read logfile; do
    echo "--- $logfile ---"
    tail -20 "$logfile" 2>/dev/null | grep -i "marvelous\|order\|fatal\|parse" | tail -5
done

echo ""
echo "=== FIN DU DIAGNOSTIC ==="
echo ""
echo "📋 RÉSUMÉ:"
echo "- Si GET et POST retournent 200 → ✅ PROBLÈME RÉSOLU"
echo "- Si vous voyez du HTML → .htaccess cause erreur 500 → LE SUPPRIMER"
echo "- Si 503 persiste → Contacter o2switch pour whitelist API"
