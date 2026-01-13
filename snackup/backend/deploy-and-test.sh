#!/bin/bash
# Script de déploiement Phase 3 - Synchronisation menu.json depuis MySQL
# À exécuter sur le serveur o2switch

set -e  # Arrêter en cas d'erreur

echo "========================================="
echo "DÉPLOIEMENT PHASE 3 - Sync menu.json"
echo "========================================="
echo ""

# 1. Pull des derniers changements
echo "📥 1. Pull depuis GitHub..."
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
echo "✅ Pull terminé"
echo ""

# 2. Vérifier que les fichiers existent
echo "🔍 2. Vérification des fichiers..."
if [ ! -f "database/generateMenuJson.php" ]; then
    echo "❌ ERREUR: database/generateMenuJson.php introuvable"
    exit 1
fi
if [ ! -f "database/repositories/MenuRepository.php" ]; then
    echo "❌ ERREUR: database/repositories/MenuRepository.php introuvable"
    exit 1
fi
echo "✅ Tous les fichiers présents"
echo ""

# 3. Générer menu.json depuis MySQL
echo "🔄 3. Génération menu.json depuis MySQL..."
cd ~/Marvelous.mon-agenceweb.fr/database
php generateMenuJson.php

if [ $? -ne 0 ]; then
    echo "❌ ERREUR lors de la génération de menu.json"
    exit 1
fi
echo ""

# 4. Vérifier que menu.json a été créé et contient des données
echo "🔍 4. Vérification menu.json..."
if [ ! -f "../config/menu.json" ]; then
    echo "❌ ERREUR: menu.json n'a pas été généré"
    exit 1
fi

FILE_SIZE=$(stat -f%z "../config/menu.json" 2>/dev/null || stat -c%s "../config/menu.json")
if [ "$FILE_SIZE" -lt 1000 ]; then
    echo "⚠️  ATTENTION: menu.json semble trop petit ($FILE_SIZE octets)"
    echo "Contenu:"
    head -20 ../config/menu.json
    exit 1
fi

echo "✅ menu.json généré avec succès ($FILE_SIZE octets)"
echo ""

# 5. Afficher un extrait de menu.json
echo "📄 5. Extrait de menu.json (premières catégories):"
head -50 ../config/menu.json
echo ""

# 6. Compter les catégories
CATEGORIES_COUNT=$(grep -o '"name"' ../config/menu.json | wc -l)
echo "📊 Nombre de catégories détectées: $CATEGORIES_COUNT"
echo ""

echo "========================================="
echo "✅ DÉPLOIEMENT TERMINÉ"
echo "========================================="
echo ""
echo "🎯 Prochaines étapes:"
echo "   1. Tester l'affichage du site web"
echo "   2. Tester la création d'une catégorie dans l'admin"
echo "   3. Vérifier que menu.json se régénère automatiquement"
echo ""
