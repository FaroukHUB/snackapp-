#!/bin/bash
# Script d'enrichissement automatique des profils clients
# Analyse l'historique des commandes et enrichit les profils

# Déterminer le répertoire du script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR/admin-panel-v2"

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "enrich-customers-auto.php" ]; then
    echo "❌ Erreur: Impossible de trouver enrich-customers-auto.php"
    echo "   Vérifiez que le script est exécuté depuis la racine du projet"
    exit 1
fi

echo "============================================"
echo "🔄 ENRICHISSEMENT AUTOMATIQUE CLIENTS"
echo "============================================"
echo ""
echo "Ce script va:"
echo "  ✅ Extraire les adresses depuis les commandes"
echo "  ✅ Ajouter max 2 adresses par client"
echo "  ✅ Préserver les données manuelles (tags, notes)"
echo ""
read -p "Continuer? (oui/non) " -r
echo ""

if [[ ! $REPLY =~ ^(oui|OUI|o|O)$ ]]; then
    echo "❌ Enrichissement annulé"
    exit 1
fi

# Exécuter le script PHP
php enrich-customers-auto.php
EXIT_CODE=$?

echo ""

if [ $EXIT_CODE -eq 0 ]; then
    echo "============================================"
    echo "✅ Enrichissement terminé!"
    echo "============================================"
    echo ""
    echo "Prochaines étapes:"
    echo "  → Ouvrez clients.php pour voir les adresses"
    echo "  → Les produits favoris s'affichent automatiquement"
    echo ""
else
    echo "============================================"
    echo "❌ Erreur lors de l'enrichissement"
    echo "============================================"
    echo ""
    echo "Code de sortie: $EXIT_CODE"
    echo ""
    echo "Vérifiez:"
    echo "  → La connexion MySQL est-elle active?"
    echo "  → Le fichier config.php est-il correct?"
    echo "  → Les permissions sont-elles correctes?"
    echo ""
    exit $EXIT_CODE
fi
