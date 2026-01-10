#!/bin/bash
# Script de migration: Ajout colonne delivery_address à orders
# Date: 2026-01-10

cd ~/Marvelous.mon-agenceweb.fr

echo "============================================"
echo "🔄 MIGRATION: delivery_address dans orders"
echo "============================================"
echo ""

# 1. Lire la config database
CONFIG_FILE="database/config.php"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "❌ Erreur: $CONFIG_FILE introuvable"
    exit 1
fi

DB_HOST=$(php -r "include '$CONFIG_FILE'; echo \$config['database']['host'];")
DB_NAME=$(php -r "include '$CONFIG_FILE'; echo \$config['database']['dbname'];")
DB_USER=$(php -r "include '$CONFIG_FILE'; echo \$config['database']['username'];")
DB_PASS=$(php -r "include '$CONFIG_FILE'; echo \$config['database']['password'];")

echo "📊 Configuration:"
echo "  - Host: $DB_HOST"
echo "  - Database: $DB_NAME"
echo "  - User: $DB_USER"
echo ""

# 2. Backup de la table orders
BACKUP_FILE="backup_orders_$(date +%Y%m%d_%H%M%S).sql"
echo "💾 Backup de la table orders..."
MYSQL_PWD="$DB_PASS" mysqldump -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" orders > "$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Backup créé: $BACKUP_FILE"
else
    echo "❌ Erreur lors du backup"
    exit 1
fi

echo ""
read -p "⚠️  Continuer avec la migration? (oui/non) " -r
echo ""

if [[ ! $REPLY =~ ^(oui|OUI|o|O)$ ]]; then
    echo "❌ Migration annulée"
    exit 1
fi

# 3. Appliquer la migration
echo "🔧 Application de la migration..."
MYSQL_PWD="$DB_PASS" mysql -h"$DB_HOST" -u"$DB_USER" "$DB_NAME" < database/migrations/2026-01-10-add-delivery-address.sql

if [ $? -eq 0 ]; then
    echo "✅ Migration appliquée avec succès!"
else
    echo "❌ Erreur lors de la migration"
    echo ""
    echo "Pour restaurer le backup:"
    echo "  MYSQL_PWD=\"$DB_PASS\" mysql -h\"$DB_HOST\" -u\"$DB_USER\" \"$DB_NAME\" < $BACKUP_FILE"
    exit 1
fi

echo ""
echo "============================================"
echo "✅ MIGRATION TERMINÉE"
echo "============================================"
echo ""
echo "Colonnes ajoutées:"
echo "  - delivery_address (VARCHAR 500)"
echo "  - delivery_instructions (TEXT)"
echo ""
echo "⚠️  IMPORTANT: Mettre à jour le code pour utiliser ces colonnes!"
echo ""
echo "Fichiers à modifier:"
echo "  - admin-panel-v2/api/orders.php (capture de l'adresse)"
echo "  - database/repositories/OrderRepository.php (INSERT/SELECT)"
echo "  - template-v2/checkout.html (envoi de l'adresse)"
echo ""
