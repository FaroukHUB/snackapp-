#!/bin/bash
########################################
# DÉPLOIEMENT ATELIER PIZZA - 2026-01-16
# Déploiement complet : code + base de données
########################################

echo "🍕 Déploiement Atelier Pizza - atelierpizza.mon-agenceweb.fr"
echo "================================================================"
echo ""

# Configuration
DB_NAME="zajr1824_atelierpizza"
DB_USER="zajr1824_atelierpizza"
DB_PASS="Mariagor6!"

# 1. Aller dans le répertoire
echo "📁 Navigation vers le répertoire..."
cd ~/atelierpizza.mon-agenceweb.fr || {
    echo "❌ Erreur: Impossible d'accéder au répertoire"
    echo "Vérifiez que le chemin est correct (peut-être ~/www/atelierpizza.mon-agenceweb.fr ?)"
    exit 1
}

# 2. Sauvegarder l'état actuel
echo "💾 Sauvegarde de l'état actuel..."
git stash

# 3. Récupérer les derniers changements
echo "⬇️  Récupération des changements depuis GitHub..."
git fetch origin

# 4. Checkout la branche avec les corrections
echo "🔄 Basculement sur la branche avec les corrections..."
git checkout claude/review-progress-continue-U4j8i
git pull origin claude/review-progress-continue-U4j8i

echo ""
echo "✅ Code déployé !"
echo ""

# 5. Vérifier que les scripts SQL existent
echo "🔍 Vérification des scripts SQL..."
if [ ! -f "instances/atelier-pizza/init_restaurant.sql" ]; then
    echo "❌ Erreur: instances/atelier-pizza/init_restaurant.sql introuvable"
    exit 1
fi

if [ ! -f "instances/atelier-pizza/populate_menu.sql" ]; then
    echo "❌ Erreur: instances/atelier-pizza/populate_menu.sql introuvable"
    exit 1
fi

echo "✅ Scripts SQL trouvés"
echo ""

# 6. Exécuter les scripts SQL
echo "🗄️  Initialisation de la base de données..."
echo ""
echo "📊 Exécution de init_restaurant.sql..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < instances/atelier-pizza/init_restaurant.sql

if [ $? -eq 0 ]; then
    echo "✅ Restaurant initialisé"
else
    echo "⚠️  Erreur lors de l'initialisation (peut-être déjà fait ?)"
fi

echo ""
echo "🍕 Exécution de populate_menu.sql..."
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < instances/atelier-pizza/populate_menu.sql

if [ $? -eq 0 ]; then
    echo "✅ Menu importé"
else
    echo "⚠️  Erreur lors de l'import du menu (peut-être déjà fait ?)"
fi

echo ""
echo "🗄️  Base de données configurée !"
echo ""

# 7. Vérification des APIs
echo "🔍 VÉRIFICATION DES APIs"
echo "========================"
echo ""

echo "1. Test API restaurant:"
RESTAURANT_RESPONSE=$(curl -s "https://atelierpizza.mon-agenceweb.fr/config/restaurant.php" | head -c 50)
echo "$RESTAURANT_RESPONSE"
echo ""

echo "2. Test API menu:"
MENU_RESPONSE=$(curl -s "https://atelierpizza.mon-agenceweb.fr/config/menu.php" | head -c 50)
echo "$MENU_RESPONSE"
echo ""

# 8. Vérification du contenu JSON
if [[ $RESTAURANT_RESPONSE == "{"* ]]; then
    echo "✅ API restaurant OK (retourne du JSON)"
else
    echo "❌ API restaurant KO (erreur ou pas de JSON)"
fi

if [[ $MENU_RESPONSE == "{"* ]]; then
    echo "✅ API menu OK (retourne du JSON)"
else
    echo "❌ API menu KO (erreur ou pas de JSON)"
fi

echo ""
echo "================================================================"
echo "✅ DÉPLOIEMENT TERMINÉ"
echo "================================================================"
echo ""
echo "🌐 TESTS FINAUX:"
echo ""
echo "1. Ouvrez dans votre navigateur:"
echo "   https://atelierpizza.mon-agenceweb.fr/"
echo ""
echo "2. Vérifiez que le menu s'affiche"
echo ""
echo "3. Si vous voyez des erreurs:"
echo "   - API restaurant: curl -v https://atelierpizza.mon-agenceweb.fr/config/restaurant.php"
echo "   - API menu: curl -v https://atelierpizza.mon-agenceweb.fr/config/menu.php"
echo "   - Logs PHP: tail -50 ~/logs/error.log"
echo ""
echo "4. Admin panel:"
echo "   https://atelierpizza.mon-agenceweb.fr/snackup/admin/"
echo ""
echo "📝 Configuration appliquée:"
echo "   - Restaurant ID: 3"
echo "   - Instance: atelier-pizza"
echo "   - Devise: EUR"
echo "   - Base MySQL: zajr1824_atelierpizza"
echo ""
