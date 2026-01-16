#!/bin/bash
########################################
# TEST RAPIDE DES APIs - ATELIER PIZZA
########################################

echo "🧪 Test des APIs Atelier Pizza"
echo "==============================="
echo ""

# Test 1: API Restaurant
echo "1️⃣  Test API restaurant.php..."
echo "URL: https://atelierpizza.mon-agenceweb.fr/config/restaurant.php"
echo ""
RESPONSE=$(curl -s "https://atelierpizza.mon-agenceweb.fr/config/restaurant.php" 2>&1)

if [[ $RESPONSE == "{"* ]]; then
    echo "✅ Retourne du JSON"
    echo "$RESPONSE" | head -c 200
    echo ""
else
    echo "❌ ERREUR - Pas de JSON retourné"
    echo "$RESPONSE"
fi

echo ""
echo "---"
echo ""

# Test 2: API Menu
echo "2️⃣  Test API menu.php..."
echo "URL: https://atelierpizza.mon-agenceweb.fr/config/menu.php"
echo ""
RESPONSE=$(curl -s "https://atelierpizza.mon-agenceweb.fr/config/menu.php" 2>&1)

if [[ $RESPONSE == "{"* ]]; then
    echo "✅ Retourne du JSON"
    echo "$RESPONSE" | head -c 200
    echo ""
else
    echo "❌ ERREUR - Pas de JSON retourné"
    echo "$RESPONSE"
fi

echo ""
echo "==============================="
echo ""
echo "💡 DIAGNOSTIC APPROFONDI:"
echo "   php DIAGNOSTIC-ATELIER-PIZZA.php"
echo ""
echo "📋 LOGS PHP:"
echo "   tail -50 ~/logs/error.log"
echo ""
