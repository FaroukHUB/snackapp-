#!/bin/bash
# Script d'application de la migration clients
# À exécuter sur o2switch après git pull

cd ~/Marvelous.mon-agenceweb.fr

echo "============================================"
echo "🔄 MIGRATION: Amélioration gestion clients"
echo "============================================"
echo ""

# Vérifier que le fichier de migration existe
if [ ! -f "database/migrations/2026-01-09-customer-improvements.sql" ]; then
    echo "❌ Fichier de migration introuvable!"
    echo "   Assurez-vous d'avoir fait: git pull origin claude/review-progress-continue-U4j8i"
    exit 1
fi

echo "📄 Fichier de migration trouvé"
echo ""

# Charger les paramètres de connexion depuis database/config.php
echo "🔍 Lecture configuration database..."
DB_CONFIG=$(php -r "
    \$config = require 'database/config.php';
    echo json_encode(\$config['database']);
")

DB_HOST=$(echo $DB_CONFIG | php -r "echo json_decode(file_get_contents('php://stdin'), true)['host'];")
DB_NAME=$(echo $DB_CONFIG | php -r "echo json_decode(file_get_contents('php://stdin'), true)['dbname'];")
DB_USER=$(echo $DB_CONFIG | php -r "echo json_decode(file_get_contents('php://stdin'), true)['user'];")
DB_PASS=$(echo $DB_CONFIG | php -r "echo json_decode(file_get_contents('php://stdin'), true)['password'];")

if [ -z "$DB_HOST" ] || [ -z "$DB_NAME" ]; then
    echo "❌ Impossible de lire la configuration database"
    exit 1
fi

echo "✅ Configuration chargée"
echo "   Host: $DB_HOST"
echo "   Database: $DB_NAME"
echo ""

# Demander confirmation
echo "⚠️  ATTENTION: Cette migration va modifier la table customers et créer customer_tags"
echo ""
read -p "Continuer? (oui/non) " -r
echo ""

if [[ ! $REPLY =~ ^(oui|OUI|o|O)$ ]]; then
    echo "❌ Migration annulée"
    exit 1
fi

# Backup avant migration
echo "💾 Backup table customers..."
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" customers > "backup_customers_$(date +%Y%m%d_%H%M%S).sql"

if [ $? -eq 0 ]; then
    echo "✅ Backup créé: backup_customers_$(date +%Y%m%d_%H%M%S).sql"
else
    echo "❌ Erreur lors du backup"
    exit 1
fi

echo ""
echo "🚀 Application de la migration..."
echo ""

# Exécuter la migration
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/migrations/2026-01-09-customer-improvements.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ ✅ ✅ MIGRATION RÉUSSIE! ✅ ✅ ✅"
    echo ""
    echo "📊 Vérifications:"
    echo ""

    # Vérifier colonnes ajoutées
    echo "1. Colonnes customers:"
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT COLUMN_NAME, COLUMN_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '$DB_NAME'
          AND TABLE_NAME = 'customers'
          AND COLUMN_NAME IN ('addresses', 'preferences', 'admin_notes');
    "

    echo ""
    echo "2. Table customer_tags créée:"
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT COUNT(*) as total_tags FROM customer_tags;
    "

    echo ""
    echo "3. Distribution des tags:"
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT tag, COUNT(*) as count
        FROM customer_tags
        GROUP BY tag
        ORDER BY count DESC;
    "

    echo ""
    echo "============================================"
    echo "✅ Migration terminée avec succès!"
    echo "============================================"
    echo ""
    echo "Prochaine étape:"
    echo "→ Les nouvelles fonctionnalités sont disponibles dans l'admin"
    echo "→ Rechargez la page admin pour voir les changements"
    echo ""

else
    echo ""
    echo "❌ ❌ ❌ ERREUR LORS DE LA MIGRATION! ❌ ❌ ❌"
    echo ""
    echo "Pour restaurer le backup:"
    echo "  mysql -h'$DB_HOST' -u'$DB_USER' -p'$DB_PASS' '$DB_NAME' < backup_customers_*.sql"
    echo ""
    exit 1
fi
