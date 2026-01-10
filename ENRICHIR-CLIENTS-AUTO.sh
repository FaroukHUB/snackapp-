#!/bin/bash
# Script d'enrichissement automatique des profils clients
# Analyse l'historique des commandes et enrichit les profils

cd ~/Marvelous.mon-agenceweb.fr/admin-panel-v2

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

echo ""
echo "============================================"
echo "✅ Enrichissement terminé!"
echo "============================================"
echo ""
echo "Prochaines étapes:"
echo "  → Ouvrez clients.php pour voir les adresses"
echo "  → Les produits favoris s'affichent automatiquement"
echo ""
