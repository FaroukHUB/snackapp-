#!/bin/bash

echo "============================================"
echo "🚨 FIX URGENT - MENU.JSON"
echo "============================================"

echo -e "\n⚠️  Ce script va:"
echo "1. Restaurer menu.json depuis Git"
echo "2. Vérifier qu'il est valide"
echo "3. Nettoyer tous les conflits"
echo ""
read -p "Continuer? (o/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "❌ Annulé"
    exit 1
fi

# 1. Backup actuel
echo -e "\n1️⃣ Backup du fichier actuel..."
cp config/menu.json config/menu.json.backup-$(date +%Y%m%d-%H%M%S)
echo "✅ Backup créé"

# 2. Récupérer depuis Git
echo -e "\n2️⃣ Récupération depuis Git..."
git fetch origin claude/review-progress-continue-U4j8i
git checkout origin/claude/review-progress-continue-U4j8i -- config/menu.json
echo "✅ Fichier restauré depuis Git"

# 3. Vérifier JSON
echo -e "\n3️⃣ Validation JSON..."
if python3 -c "import json; json.load(open('config/menu.json'))" 2>/dev/null; then
    echo "✅ JSON valide"
else
    echo "❌ JSON INVALIDE après restauration!"
    echo "Affichage de l'erreur:"
    python3 -c "import json; json.load(open('config/menu.json'))" 2>&1
    exit 1
fi

# 4. Afficher structure
echo -e "\n4️⃣ Vérification structure:"
python3 << 'EOF'
import json
with open('config/menu.json', 'r') as f:
    menu = json.load(f)
cats = menu.get('menu', {}).get('categories', [])
print(f"✅ {len(cats)} catégories chargées")
for cat in cats:
    items = len(cat.get('items', []))
    print(f"   - {cat.get('name')}: {items} produits")
EOF

echo ""
echo "============================================"
echo "✅ FIX TERMINÉ!"
echo "============================================"
echo "Le site devrait maintenant afficher les catégories."
echo ""
echo "🔧 PROCHAINES ÉTAPES:"
echo "1. Vider cache navigateur: CTRL+SHIFT+R"
echo "2. Vérifier le site: https://marvelous.mon-agenceweb.fr/template-v2/"
echo "============================================"
