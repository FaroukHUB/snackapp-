#!/bin/bash
# Script de diagnostic ultra-simple

echo "🔍 DIAGNOSTIC: Nouvelles catégories créées"
echo "=========================================="
echo ""

cd ~/Marvelous.mon-agenceweb.fr

echo "1️⃣ Catégories dans RUNTIME (customCategories):"
echo "------------------------------------------------"
cat config/menu.runtime.json | python3 -m json.tool 2>/dev/null | grep -A 50 '"customCategories"' | grep '"id"' | cut -d'"' -f4 | head -20 || \
  cat config/menu.runtime.json | grep -o '"id"[[:space:]]*:[[:space:]]*"[^"]*"' | grep -A 20 "customCategories" | cut -d'"' -f4
echo ""

echo "2️⃣ Catégories dans MENU.JSON (site public):"
echo "------------------------------------------------"
cat config/menu.json | python3 -m json.tool 2>/dev/null | grep -B 2 '"id"' | grep '"id"' | cut -d'"' -f4 | grep -v "^$" | head -20 || \
  cat config/menu.json | grep -o '"id"[[:space:]]*:[[:space:]]*"[a-z-][^"]*"' | head -20
echo ""

echo "3️⃣ COMPARAISON:"
echo "------------------------------------------------"
RUNTIME_CATS=$(cat config/menu.runtime.json | grep "customCategories" -A 100 | grep -c '"id"')
MENU_CATS=$(cat config/menu.json | grep "\"categories\"" -A 1000 | grep -c "\"id\".*:")

echo "Nombre dans runtime.customCategories: $RUNTIME_CATS"
echo "Nombre dans menu.json.categories: $MENU_CATS"
echo ""

if [ $MENU_CATS -lt 10 ]; then
    echo "⚠️  ATTENTION: Seulement $MENU_CATS catégories dans menu.json!"
    echo "   C'est probablement trop peu."
fi

echo "4️⃣ Chercher catégories spécifiques:"
echo "------------------------------------------------"
for cat in "burgers" "test-direct" "urgers"; do
    if grep -q "\"$cat\"" config/menu.json; then
        ITEMS=$(cat config/menu.json | grep -A 30 "\"$cat\"" | grep -c "\"id\".*:")
        echo "✅ '$cat' trouvée dans menu.json ($ITEMS items)"
    else
        echo "❌ '$cat' ABSENTE de menu.json"
    fi

    if grep -q "\"$cat\"" config/menu.runtime.json; then
        echo "   ℹ️  '$cat' présente dans runtime"
    fi
done
echo ""

echo "5️⃣ Vérifier deletedCategories:"
echo "------------------------------------------------"
cat config/menu.runtime.json | grep -A 10 "deletedCategories"
echo ""

echo "=========================================="
echo "📝 CONCLUSION:"
if [ $RUNTIME_CATS -gt 0 ] && [ $MENU_CATS -lt 10 ]; then
    echo "❌ Vous avez $RUNTIME_CATS catégories custom dans runtime"
    echo "   mais seulement $MENU_CATS dans menu.json"
    echo "   → Le problème: generatePublicMenuJson() ne fonctionne pas!"
elif grep -q "burgers\|test-direct" config/menu.runtime.json; then
    if ! grep -q "burgers\|test-direct" config/menu.json; then
        echo "❌ Catégories dans runtime mais PAS dans menu.json"
        echo "   → Le problème: La fusion ne fonctionne pas!"
    else
        echo "✅ Catégories présentes dans menu.json"
        echo "   → Vérifiez le cache de votre navigateur (Ctrl+F5)"
    fi
else
    echo "ℹ️  Aucune catégorie custom trouvée"
    echo "   → Créez une catégorie via l'admin puis relancez ce script"
fi
