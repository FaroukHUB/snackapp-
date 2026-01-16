#!/bin/bash
########################################
# DÉPLOIEMENT FIX MENU - 2026-01-16
# Problème: Menu ne s'affiche pas sur le site
# Solution: Déployer système multi-instance MySQL
########################################

echo "🚀 Déploiement fix menu - Le Marvelous"
echo "========================================"
echo ""

# 1. Aller dans le répertoire
cd ~/Marvelous.mon-agenceweb.fr

# 2. Sauvegarder l'état actuel
echo "📦 Sauvegarde de l'état actuel..."
git stash

# 3. Récupérer les derniers changements
echo "⬇️  Récupération des changements depuis GitHub..."
git fetch origin

# 4. Checkout la branche avec les fix
echo "🔄 Basculement sur la branche avec les corrections..."
git checkout claude/review-progress-continue-U4j8i
git pull origin claude/review-progress-continue-U4j8i

echo ""
echo "✅ Déploiement terminé !"
echo ""
echo "🔍 VÉRIFICATION:"
echo "================"
echo ""
echo "1. Testez l'API menu:"
echo "   curl -s 'https://marvelous.mon-agenceweb.fr/config/menu.php' | head -c 200"
echo ""
echo "2. Testez l'API restaurant:"
echo "   curl -s 'https://marvelous.mon-agenceweb.fr/config/restaurant.php' | head -c 200"
echo ""
echo "3. Ouvrez le site dans votre navigateur:"
echo "   https://marvelous.mon-agenceweb.fr/"
echo ""
echo "4. Vérifiez que le menu s'affiche correctement"
echo ""
echo "❓ Si vous voyez des erreurs de connexion MySQL:"
echo "   - Vérifiez instances/marvelous/backend-config.php"
echo "   - Mot de passe MySQL correct: Mariagor6!"
echo ""
