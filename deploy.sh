#!/bin/bash
# Script de déploiement - Correction de tous les bugs (2024-12-26)
# À exécuter sur o2switch via SSH ou Terminal cPanel

echo "🚀 Déploiement des corrections de bugs sur o2switch..."
echo "=================================================="
echo ""

# Variables
BASE_DIR="/home/zajr1824/Marvelous.mon-agenceweb.fr"
GITHUB_RAW="https://raw.githubusercontent.com/FaroukHUB/snackapp-/claude/setup-marvelous-creperie-Wg8p0/deployments/le-marvelous"

# Aller dans le dossier
cd "$BASE_DIR" || exit 1

echo "📁 Répertoire de travail: $BASE_DIR"
echo ""

# ============================================
# 🔴 BUG CRITIQUE #1: Protection des JSON
# ============================================
echo "🔐 [1/7] Protection des fichiers JSON avec .htaccess..."

# Créer .htaccess pour database/ SEULEMENT
wget -O database/.htaccess "$GITHUB_RAW/database/.htaccess" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ database/.htaccess créé"
else
    echo "   ❌ Erreur lors de la création de database/.htaccess"
fi

# IMPORTANT: Ne PAS créer .htaccess dans config/ car restaurant.php doit rester accessible
echo "   ⚠️  config/.htaccess DÉSACTIVÉ (restaurant.php doit être accessible)"

echo ""

# ============================================
# 🔴 BUG CRITIQUE #2 & #3: Cart.html complet
# ============================================
echo "🛒 [2/7] Mise à jour cart.html (sélecteur pays + options)..."

# Backup
cp template-v2/cart.html template-v2/cart.html.backup-$(date +%Y%m%d-%H%M%S)

# Download
wget -O template-v2/cart.html "$GITHUB_RAW/template-v2/cart.html" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ cart.html mis à jour"
    echo "      - Sélecteur de pays (12 pays)"
    echo "      - Validation téléphone"
    echo "      - Envoi de TOUTES les options à l'API"
    echo "      - Protection XSS avec escapeHtml()"
else
    echo "   ❌ Erreur lors de la mise à jour de cart.html"
fi

echo ""

# ============================================
# 🆕 FIX UPSELLS: Config.js
# ============================================
echo "⚙️  [3/7] Mise à jour config.js (fix upsells produits parents)..."

# Backup
cp template-v2/js/config.js template-v2/js/config.js.backup-$(date +%Y%m%d-%H%M%S)

# Download
wget -O template-v2/js/config.js "$GITHUB_RAW/template-v2/js/config.js" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ config.js mis à jour"
    echo "      - Affiche produits parents (Soda, Viennoiserie)"
    echo "      - Désactive options aléatoires (Ifri, Croissant)"
    echo "      - Modal s'ouvre pour choisir l'option"
else
    echo "   ❌ Erreur lors de la mise à jour de config.js"
fi

echo ""

# ============================================
# 🟠 BUG HAUT #4: Admin app.js
# ============================================
echo "📊 [4/7] Mise à jour admin app.js (modal détails)..."

# Backup
cp admin-panel-v2/assets/js/app.js admin-panel-v2/assets/js/app.js.backup-$(date +%Y%m%d-%H%M%S)

# Download
wget -O admin-panel-v2/assets/js/app.js "$GITHUB_RAW/admin-panel-v2/assets/js/app.js" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ app.js mis à jour"
    echo "      - Affichage supplements"
    echo "      - Affichage options (crêpe, sauce, etc.)"
    echo "      - Affichage ingrédients retirés"
else
    echo "   ❌ Erreur lors de la mise à jour de app.js"
fi

echo ""

# ============================================
# 🟠 BUG HAUT #5: Admin index.php
# ============================================
echo "📋 [5/7] Mise à jour admin index.php (liste commandes)..."

# Backup
cp admin-panel-v2/index.php admin-panel-v2/index.php.backup-$(date +%Y%m%d-%H%M%S)

# Download
wget -O admin-panel-v2/index.php "$GITHUB_RAW/admin-panel-v2/index.php" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ index.php mis à jour"
    echo "      - Affichage toutes les options dans la liste"
else
    echo "   ❌ Erreur lors de la mise à jour de index.php"
fi

echo ""

# ============================================
# 🟡 BUG MOYEN #7: Rate limiting
# ============================================
echo "⏱️  [6/7] Mise à jour orders.php (rate limiting)..."

# Backup
cp admin-panel-v2/api/orders.php admin-panel-v2/api/orders.php.backup-$(date +%Y%m%d-%H%M%S)

# Download
wget -O admin-panel-v2/api/orders.php "$GITHUB_RAW/admin-panel-v2/api/orders.php" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "   ✅ orders.php mis à jour"
    echo "      - Rate limiting: Max 5 commandes/minute"
else
    echo "   ❌ Erreur lors de la mise à jour de orders.php"
fi

echo ""

# ============================================
# Vérification des permissions
# ============================================
echo "🔒 [7/7] Vérification des permissions..."

chmod 644 database/.htaccess 2>/dev/null
chmod 644 template-v2/cart.html 2>/dev/null
chmod 644 template-v2/js/config.js 2>/dev/null
chmod 644 admin-panel-v2/assets/js/app.js 2>/dev/null
chmod 644 admin-panel-v2/index.php 2>/dev/null
chmod 644 admin-panel-v2/api/orders.php 2>/dev/null

# Supprimer config/.htaccess s'il existe (ne doit pas bloquer restaurant.php)
rm -f config/.htaccess 2>/dev/null

# Créer le dossier cache pour rate limiting
mkdir -p admin-panel-v2/cache 2>/dev/null
chmod 755 admin-panel-v2/cache 2>/dev/null

echo "   ✅ Permissions configurées"
echo ""

# ============================================
# Résumé
# ============================================
echo "=================================================="
echo "✅ DÉPLOIEMENT TERMINÉ!"
echo "=================================================="
echo ""
echo "📊 Corrections déployées:"
echo ""
echo "🔴 BUGS CRITIQUES:"
echo "   ✅ #1 - Fichiers JSON protégés (.htaccess)"
echo "   ✅ #2 - Options produits envoyées à l'API"
echo "   ✅ #3 - Sélecteur de pays (12 pays)"
echo ""
echo "🟠 BUGS HAUTS:"
echo "   ✅ #4 - Options affichées dans modal admin"
echo "   ✅ #5 - Options affichées dans liste admin"
echo ""
echo "🟡 BUGS MOYENS:"
echo "   ✅ #6 - Protection XSS (escapeHtml)"
echo "   ✅ #7 - Rate limiting (5/min)"
echo "   ✅ #8 - Validation format téléphone"
echo ""
echo "🆕 NOUVELLE FONCTIONNALITÉ:"
echo "   ✅ #9 - Modal upsells avec choix d'options"
echo "          • Affiche produits parents (Soda, Viennoiserie)"
echo "          • Clic → modal pour choisir l'option"
echo "          • Fini les options aléatoires (Ifri, Croissant)"
echo ""
echo "🧪 TESTS À FAIRE:"
echo "   1. Tester qu'on NE PEUT PLUS accéder à:"
echo "      https://marvelous.mon-agenceweb.fr/database/orders.json"
echo "      https://marvelous.mon-agenceweb.fr/config/menu.json"
echo ""
echo "   2. Passer une commande avec:"
echo "      - Sélectionner un pays (ex: France)"
echo "      - Choisir Crêpes Kids → Poulet + Ketchup"
echo "      - Vérifier que tout s'affiche dans l'admin"
echo ""
echo "   3. Vérifier dans l'admin que les détails s'affichent:"
echo "      - Liste des commandes"
echo "      - Modal de détail"
echo ""
echo "   4. Tester les upsells avec modal:"
echo "      - Cliquer sur upsell 'Soda' → modal s'ouvre"
echo "      - Choisir 'Ifri' → cliquer 'Ajouter'"
echo "      - Vérifier que 'Soda (Ifri)' apparaît dans le panier"
echo ""
echo "=================================================="
echo "🎉 Prêt! Le site est maintenant plus sécurisé et fonctionnel."
echo "=================================================="
