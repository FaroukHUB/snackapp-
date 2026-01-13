#!/bin/bash
# Script de restauration du menu.json

set -e

echo "🔄 RESTAURATION MENU.JSON"
echo "========================="
echo ""

# Backup du menu.json actuel (corrompu)
echo "1. Sauvegarde du menu.json actuel..."
cd ~/Marvelous.mon-agenceweb.fr
cp config/menu.json config/menu.json.corrupted-$(date +%Y%m%d-%H%M%S)
echo "✅ Sauvegardé"
echo ""

# Restauration du backup le plus récent
echo "2. Restauration du backup du 5 janvier..."
cp config/menu.json.backup-20260105-134315 config/menu.json
echo "✅ Restauré"
echo ""

# Vérification
echo "3. Vérification du menu.json restauré..."
FILE_SIZE=$(stat -c%s config/menu.json 2>/dev/null || stat -f%z config/menu.json)
echo "   Taille: $FILE_SIZE octets (attendu: ~47000)"

# Compter quelques éléments clés
FORMULES_COUNT=$(grep -o '"formules"' config/menu.json | wc -l)
OPTIONS_COUNT=$(grep -o 'pâtisserieOptions\|beverageOptions' config/menu.json | wc -l)

echo "   Formules détectées: $FORMULES_COUNT"
echo "   Options détectées: $OPTIONS_COUNT"
echo ""

echo "✅ RESTAURATION TERMINÉE"
echo ""
echo "⚠️  IMPORTANT: La régénération automatique est DÉSACTIVÉE"
echo "   pour éviter d'écraser à nouveau ces données."
