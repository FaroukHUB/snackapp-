#!/bin/bash
# Diagnostic erreur 503 sur commandes depuis le site
# À exécuter sur o2switch

cd ~/Marvelous.mon-agenceweb.fr

echo "=== DIAGNOSTIC ERREUR 503 COMMANDES ==="
echo ""
echo "1. Vérifier les logs d'erreurs Apache/PHP..."
echo ""

# Trouver le fichier d'erreur le plus récent
echo "Fichiers de logs trouvés:"
find ~ -name "error_log" -o -name "error.log" -o -name "php_error*" 2>/dev/null | head -10

echo ""
echo "Dernières erreurs PHP (20 dernières lignes):"
find ~ -name "error_log" -type f -exec tail -20 {} \; 2>/dev/null | grep -i "marvelous\|order\|503" | tail -20

echo ""
echo "=== 2. Test API minimal (sans ModSecurity) ==="
echo ""

# Test 1: GET simple (devrait fonctionner)
echo "Test GET /api/orders.php?action=list&limit=1"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php?action=list&limit=1")
echo "Résultat: HTTP $HTTP_CODE"

echo ""
echo "Test POST minimal (action add):"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php" \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Firefox/120.0" \
  -d '{"action":"add","customer_phone":"0555000001","customer_name":"Test","items":[],"total":100}')

HTTP_CODE=$(echo "$RESPONSE" | tail -1)
BODY=$(echo "$RESPONSE" | head -n -1)

echo "HTTP Code: $HTTP_CODE"
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ POST fonctionne avec données minimales"
    echo "Réponse: $(echo "$BODY" | head -c 200)"
elif [ "$HTTP_CODE" = "503" ]; then
    echo "❌ 503 même avec données minimales - Problème ModSecurity probable"
    echo ""
    echo "=== SOLUTION: Désactiver ModSecurity pour l'API ==="
    echo ""
    echo "Créez ou modifiez: admin-panel-v2/api/.htaccess"
    echo ""
    cat << 'HTACCESS'
# Désactiver ModSecurity pour les API
<IfModule mod_security.c>
    SecRuleEngine Off
</IfModule>

# Alternative si mod_security2
<IfModule mod_security2.c>
    SecRuleEngine Off
</IfModule>
HTACCESS
else
    echo "⚠️ Code HTTP inattendu: $HTTP_CODE"
    echo "$BODY" | head -20
fi

echo ""
echo "=== 3. Vérifier configuration PHP ==="
php -i | grep -E "max_post_size|max_input_vars|memory_limit" 2>/dev/null || echo "PHP CLI non disponible"

echo ""
echo "=== 4. Test avec curl en mode debug ==="
echo ""
echo "Exécutez cette commande pour voir les headers exacts:"
echo ""
cat << 'CURLCMD'
curl -v -X POST "https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/orders.php" \
  -H "Content-Type: application/json" \
  -H "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36" \
  -d '{"action":"add","customer_phone":"0555000001","customer_name":"Test Client","items":[{"id":"P1","name":"Test","price":100,"quantity":1}],"total":100,"subtotal":100}'
CURLCMD

echo ""
echo "=== SOLUTIONS POSSIBLES ==="
echo ""
echo "Si le test POST minimal retourne 503:"
echo "→ C'est ModSecurity qui bloque. Créez admin-panel-v2/api/.htaccess avec:"
echo "  SecRuleEngine Off"
echo ""
echo "Si les logs montrent des erreurs PHP:"
echo "→ Regardez le message d'erreur exact et corrigez le code"
echo ""
echo "Si rien dans les logs:"
echo "→ Contactez o2switch pour voir les logs ModSecurity"
echo ""
