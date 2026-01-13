#!/bin/bash
# ========================================
# Script d'exécution migration MySQL
# À exécuter SUR LE SERVEUR o2switch
# ========================================

set -e  # Arrêt en cas d'erreur

echo "🚀 Début migration MySQL - Le Marvelous"
echo "======================================="
echo ""

# 1. Aller dans le bon répertoire
cd ~/Marvelous.mon-agenceweb.fr

# 2. Pull des derniers changements
echo "📥 Pull des changements GitHub..."
git pull origin claude/setup-marvelous-creperie-Wg8p0
echo "✅ Code mis à jour"
echo ""

# 3. Exécuter la migration
echo "🔄 Exécution migration données JSON → MySQL..."
php database/migrate-json-to-mysql.php

echo ""
echo "======================================="
echo "✅ MIGRATION TERMINÉE !"
echo "======================================="
echo ""
echo "🔍 Prochaine étape : Vérifier les données dans MySQL"
echo "   Commande : mysql -u zajr1824_marvelous -p zajr1824_marvelous"
echo ""
