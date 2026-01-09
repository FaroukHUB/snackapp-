#!/bin/bash
# Test API après suppression .htaccess

echo "=== TEST API SANS .HTACCESS ==="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

# Vérifier que .htaccess est bien supprimé
if [ -f "admin-panel-v2/api/.htaccess" ]; then
    echo "⚠️  .htaccess existe encore, suppression..."
    rm admin-panel-v2/api/.htaccess
    echo "✅ Supprimé"
else
    echo "✅ .htaccess déjà supprimé"
fi

echo ""
echo "Test POST de commande (sans .htaccess)..."
echo ""

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X POST \
  "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php" \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
  -H "Accept: application/json" \
  -d '{"action":"add","customer_phone":"0555888999","customer_name":"Test Sans Htaccess","items":[{"id":"1","name":"Burger Test","price":500,"quantity":1}],"total":500,"subtotal":500}')

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo "Code HTTP: $HTTP_CODE"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅✅✅ SUCCÈS! L'API fonctionne sans .htaccess!"
    echo ""
    echo "Réponse JSON:"
    echo "$BODY" | head -c 500
    echo ""
    echo ""
    echo "=== CONCLUSION ==="
    echo "Le problème n'était PAS ModSecurity!"
    echo "L'erreur 503 originale avait une autre cause (déjà résolue?)"
    echo "Le .htaccess causait une erreur 500 et empêchait l'API de fonctionner."
    echo ""
    echo "✅ SOLUTION FINALE: NE PAS remettre le .htaccess"
    echo "✅ L'API fonctionne correctement maintenant"

elif [ "$HTTP_CODE" = "503" ]; then
    echo "❌ Erreur 503 Service Unavailable (SANS .htaccess)"
    echo ""
    if echo "$BODY" | grep -q "<!DOCTYPE\|<html"; then
        echo "Réponse HTML (premiers 800 chars):"
        echo "$BODY" | head -c 800
    else
        echo "Réponse:"
        echo "$BODY"
    fi
    echo ""
    echo ""
    echo "=== ANALYSE ==="
    echo "L'erreur 503 persiste MÊME SANS .htaccess"
    echo "Cela signifie que ModSecurity bloque au niveau serveur (avant .htaccess)"
    echo ""
    echo "=== SOLUTIONS POSSIBLES ==="
    echo ""
    echo "1. VÉRIFIER LES LOGS MODSECURITY:"
    echo "   Cherchez: ~/logs/error_log ou demandez à o2switch"
    echo ""
    echo "2. CONTACTER SUPPORT O2SWITCH:"
    echo "   Demandez de whitelister: /admin-panel-v2/api/orders.php"
    echo "   Ou désactiver ModSecurity pour ce path"
    echo ""
    echo "3. SOLUTION TEMPORAIRE - Utiliser GET au lieu de POST:"
    echo "   Modifier cart.html pour envoyer les données en GET (moins sécurisé)"
    echo ""

elif [ "$HTTP_CODE" = "500" ]; then
    echo "❌ Erreur 500 - Problème PHP ou configuration serveur"
    echo ""
    echo "Réponse:"
    echo "$BODY" | head -c 800
    echo ""
    echo "→ Vérifiez les logs d'erreur PHP"

else
    echo "⚠️ Code HTTP inattendu: $HTTP_CODE"
    echo ""
    echo "Réponse complète:"
    echo "$BODY"
fi

echo ""
echo "=== LOGS D'ERREUR PHP ==="
echo ""
# Chercher error_log dans différents endroits
for logpath in ~/logs ~/Marvelous.mon-agenceweb.fr ~/error_log /home/*/logs; do
    if [ -d "$logpath" ]; then
        find "$logpath" -name "error_log" -o -name "error.log" 2>/dev/null | while read logfile; do
            if [ -f "$logfile" ]; then
                echo "--- Dernières erreurs de $logfile ---"
                tail -30 "$logfile" | grep -i "marvelous\|order\|fatal\|notice\|warning" | tail -10
                echo ""
            fi
        done
    fi
done

# Log spécifique au domaine (o2switch)
if [ -f ~/logs/marvelous.mon-agenceweb.fr.error.log ]; then
    echo "--- Log domaine marvelous.mon-agenceweb.fr ---"
    tail -30 ~/logs/marvelous.mon-agenceweb.fr.error.log
fi

echo ""
echo "=== FIN DU TEST ==="
