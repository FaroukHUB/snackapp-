#!/bin/bash
###############################################################################
# Script de déploiement automatique - Atelier Pizza o2switch
#
# Usage:
#   1. Se connecter en SSH: ssh zajr1824@zajr1824.o2switch.net
#   2. Copier ce script sur le serveur
#   3. Le rendre exécutable: chmod +x deploy-o2switch.sh
#   4. L'exécuter: ./deploy-o2switch.sh
###############################################################################

set -e  # Arrêter en cas d'erreur

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                                                                ║"
echo "║     🚀 DÉPLOIEMENT ATELIER PIZZA - ARCHITECTURE SCALABLE        ║"
echo "║                                                                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Configuration
REPO_URL="https://github.com/FaroukHUB/snackapp-.git"
BRANCH="claude/review-progress-continue-U4j8i"
DEPLOY_DIR="$HOME/www/atelierpizza"

# Étape 1: Vérifier si le projet existe
echo "📂 Étape 1/6: Vérification du projet..."
if [ -d "$DEPLOY_DIR" ]; then
    echo "   ✓ Projet trouvé dans $DEPLOY_DIR"
    cd "$DEPLOY_DIR"

    echo "   📥 Pull de la dernière version..."
    git fetch origin
    git checkout $BRANCH
    git pull origin $BRANCH
else
    echo "   ⚠️  Projet non trouvé, clonage en cours..."
    git clone $REPO_URL "$DEPLOY_DIR"
    cd "$DEPLOY_DIR"
    git checkout $BRANCH
fi

echo ""
echo "📋 Étape 2/6: Configuration des permissions..."
chmod 755 scripts/*.sh 2>/dev/null || true
chmod 755 scripts/*.php 2>/dev/null || true
chmod 600 instances/atelier-pizza/backend-config.php 2>/dev/null || true
echo "   ✓ Permissions configurées"

echo ""
echo "🔍 Étape 3/6: Vérification de l'architecture..."
if [ -f "snackup/backend/InstanceManager.php" ]; then
    echo "   ✓ InstanceManager trouvé"
else
    echo "   ❌ ERREUR: InstanceManager manquant"
    exit 1
fi

if [ -f "config/instances.json" ]; then
    echo "   ✓ instances.json trouvé"
else
    echo "   ❌ ERREUR: instances.json manquant"
    exit 1
fi

echo ""
echo "🧪 Étape 4/6: Test de l'architecture..."
if php test-instance-manager.php > /dev/null 2>&1; then
    echo "   ✓ Test InstanceManager: PASSÉ"
else
    echo "   ⚠️  Test InstanceManager: Des erreurs peuvent exister (vérifier les logs)"
fi

echo ""
echo "🌐 Étape 5/6: Vérification des APIs..."

# Test API Restaurant
if curl -s -f "https://atelierpizza.mon-agenceweb.fr/config/restaurant.php" > /dev/null 2>&1; then
    CURRENCY=$(curl -s "https://atelierpizza.mon-agenceweb.fr/config/restaurant.php" | grep -o '"currency":"EUR"' || echo "")
    if [ -n "$CURRENCY" ]; then
        echo "   ✓ API restaurant.php: OK (EUR détecté)"
    else
        echo "   ⚠️  API restaurant.php: Répond mais devise non détectée"
    fi
else
    echo "   ⚠️  API restaurant.php: Erreur (vérifier les logs)"
fi

# Test API Menu
if curl -s -f "https://atelierpizza.mon-agenceweb.fr/config/menu.php" > /dev/null 2>&1; then
    echo "   ✓ API menu.php: OK"
else
    echo "   ⚠️  API menu.php: Erreur (vérifier les logs)"
fi

echo ""
echo "📊 Étape 6/6: Résumé du déploiement..."
echo ""
echo "   Branche déployée: $BRANCH"
echo "   Dossier: $DEPLOY_DIR"
echo ""

# Informations utiles
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                 ✅ DÉPLOIEMENT TERMINÉ                          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "🌐 URLs à tester:"
echo "   - Frontend: https://atelierpizza.mon-agenceweb.fr/"
echo "   - API Restaurant: https://atelierpizza.mon-agenceweb.fr/config/restaurant.php"
echo "   - API Menu: https://atelierpizza.mon-agenceweb.fr/config/menu.php"
echo "   - Admin: https://atelierpizza.mon-agenceweb.fr/snackup/admin/"
echo ""
echo "🔍 Diagnostic complet:"
echo "   cd $DEPLOY_DIR"
echo "   php scripts/diagnostic.php atelier-pizza"
echo ""
echo "📚 Documentation:"
echo "   - ARCHITECTURE-SCALABLE.md"
echo "   - PREUVE-ATELIER-PIZZA.md"
echo "   - DEPLOIEMENT-SSH-O2SWITCH.md"
echo ""
echo "🎉 L'architecture scalable est déployée !"
echo ""
