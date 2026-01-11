#!/bin/bash

echo "============================================"
echo "🔍 DIAGNOSTIC URGENT - MENU.JSON"
echo "============================================"

# 1. Vérifier JSON valide
echo -e "\n1️⃣ Validation JSON:"
if python3 -c "import json; json.load(open('config/menu.json'))" 2>/dev/null; then
    echo "✅ JSON valide"
else
    echo "❌ JSON INVALIDE - ERREUR:"
    python3 -c "import json; json.load(open('config/menu.json'))" 2>&1
    exit 1
fi

# 2. Recherche conflits Git
echo -e "\n2️⃣ Recherche conflits Git:"
if grep -q "<<<<<<\|======\|>>>>>>" config/menu.json 2>/dev/null; then
    echo "❌ CONFLIT GIT DÉTECTÉ!"
    echo "Lignes avec conflits:"
    grep -n "<<<<<<\|======\|>>>>>>" config/menu.json
    echo ""
    echo "🔧 CORRECTION AUTOMATIQUE:"
    sed -i '/<<<<<<</d; /=======/d; />>>>>>>/d' config/menu.json
    echo "✅ Conflits supprimés"
else
    echo "✅ Pas de conflit"
fi

# 3. Vérifier structure
echo -e "\n3️⃣ Structure du menu:"
python3 << 'EOF'
import json
try:
    with open('config/menu.json', 'r') as f:
        menu = json.load(f)
    cats = menu.get('menu', {}).get('categories', [])
    print(f"✅ Catégories: {len(cats)}")
    for cat in cats[:3]:
        items = len(cat.get('items', []))
        print(f"   - {cat.get('name')}: {items} produits")
except Exception as e:
    print(f"❌ ERREUR: {e}")
EOF

# 4. Vérifier pricePrefix supprimé
echo -e "\n4️⃣ Vérification pricePrefix:"
if grep -q "pricePrefix" config/menu.json; then
    echo "⚠️  pricePrefix toujours présent (normal si pas encore pull)"
    grep -c "pricePrefix" config/menu.json
    echo " occurrences trouvées"
else
    echo "✅ pricePrefix supprimé"
fi

# 5. Vérifier produits avec price=0
echo -e "\n5️⃣ Produits avec options (price=0):"
python3 << 'EOF'
import json
with open('config/menu.json', 'r') as f:
    menu = json.load(f)

products_zero = []
for cat in menu.get('menu', {}).get('categories', []):
    for item in cat.get('items', []):
        if item.get('price') == 0:
            has_opts = item.get('hasBeverageOptions') or item.get('hasPâtisserieOptions')
            if has_opts:
                opts_count = len(item.get('beverageOptions', [])) + len(item.get('pâtisserieOptions', []))
                products_zero.append(f"{item.get('name')} ({opts_count} options)")

if products_zero:
    print(f"✅ {len(products_zero)} produits avec options:")
    for p in products_zero:
        print(f"   - {p}")
else:
    print("⚠️  Aucun produit avec price=0 trouvé")
EOF

echo ""
echo "============================================"
echo "📋 RÉSUMÉ"
echo "============================================"
echo "Script terminé. Si JSON valide, le problème"
echo "vient du cache navigateur."
echo ""
echo "🔧 SOLUTION:"
echo "1. Vider cache: CTRL+SHIFT+R (ou CMD+SHIFT+R)"
echo "2. Ou: F12 → Network → Disable cache → F5"
echo "============================================"
