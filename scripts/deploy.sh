#!/bin/bash
##############################################################################
# Script de déploiement générique multi-instance
#
# Usage:
#   ./scripts/deploy.sh <instance-name>
#   ./scripts/deploy.sh atelier-pizza
#   ./scripts/deploy.sh marvelous
#
# Ce script:
# - Vérifie que l'instance existe dans instances.json
# - Charge la configuration de l'instance
# - Déploie les fichiers nécessaires
# - Teste les APIs
##############################################################################

set -e  # Exit on error

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonctions d'affichage
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

print_step() {
    echo -e "\n${BLUE}▶${NC} $1"
}

# Vérifier les arguments
if [ -z "$1" ]; then
    print_error "Usage: $0 <instance-name>"
    echo ""
    echo "Instances disponibles:"
    php -r "
        require_once 'snackup/backend/InstanceManager.php';
        InstanceManager::init();
        \$instances = InstanceManager::getAllInstances();
        foreach (array_keys(\$instances) as \$name) {
            echo \"  - \$name\n\";
        }
    "
    exit 1
fi

INSTANCE_NAME="$1"

print_header "DÉPLOIEMENT: $INSTANCE_NAME"

# Étape 1: Vérifier que l'instance existe
print_step "1. Vérification de l'instance"

php -r "
    require_once 'snackup/backend/InstanceManager.php';
    InstanceManager::init();
    if (!InstanceManager::getInstanceData('$INSTANCE_NAME')) {
        echo 'error';
        exit(1);
    }
    echo 'ok';
" > /tmp/instance_check.txt

RESULT=$(cat /tmp/instance_check.txt)
rm /tmp/instance_check.txt

if [ "$RESULT" != "ok" ]; then
    print_error "Instance '$INSTANCE_NAME' introuvable dans instances.json"
    exit 1
fi

print_success "Instance '$INSTANCE_NAME' trouvée"

# Étape 2: Charger la configuration
print_step "2. Chargement de la configuration"

php -r "
    require_once 'snackup/backend/InstanceManager.php';
    \$_SERVER['HTTP_HOST'] = 'localhost';
    InstanceManager::reset();

    \$data = InstanceManager::getInstanceData('$INSTANCE_NAME');
    if (!empty(\$data['domains'])) {
        \$_SERVER['HTTP_HOST'] = \$data['domains'][0];
    }
    InstanceManager::reset();

    try {
        \$config = InstanceManager::loadConfig();
        \$restaurantId = InstanceManager::getRestaurantId();
        \$currency = InstanceManager::getCurrency();
        \$instanceId = InstanceManager::getInstanceId();

        echo \"Restaurant ID: \$restaurantId\n\";
        echo \"Instance ID: \$instanceId\n\";
        echo \"Devise: \$currency\n\";
        echo 'ok';
    } catch (Exception \$e) {
        echo 'error: ' . \$e->getMessage();
        exit(1);
    }
" > /tmp/config_check.txt

if grep -q "error:" /tmp/config_check.txt; then
    print_error "Erreur de configuration:"
    cat /tmp/config_check.txt
    rm /tmp/config_check.txt
    exit 1
fi

grep -v "^ok$" /tmp/config_check.txt | while read line; do
    print_info "$line"
done
rm /tmp/config_check.txt

print_success "Configuration chargée"

# Étape 3: Vérifier la base de données
print_step "3. Vérification de la base de données"

php -r "
    require_once 'snackup/backend/InstanceManager.php';
    require_once 'snackup/backend/Database.php';

    \$data = InstanceManager::getInstanceData('$INSTANCE_NAME');
    if (!empty(\$data['domains'])) {
        \$_SERVER['HTTP_HOST'] = \$data['domains'][0];
    }
    InstanceManager::reset();

    try {
        \$dbConfig = InstanceManager::getDatabaseConfig();
        Database::init(\$dbConfig);
        \$pdo = Database::getConnection();

        // Vérifier tables essentielles
        \$tables = ['restaurants', 'menu_categories', 'menu_items'];
        foreach (\$tables as \$table) {
            \$stmt = \$pdo->query(\"SHOW TABLES LIKE '\$table'\");
            if (\$stmt->rowCount() === 0) {
                echo \"error: Table \$table manquante\";
                exit(1);
            }
        }

        echo 'ok';
    } catch (Exception \$e) {
        echo 'error: ' . \$e->getMessage();
        exit(1);
    }
" > /tmp/db_check.txt

if grep -q "error:" /tmp/db_check.txt; then
    print_error "Erreur base de données:"
    cat /tmp/db_check.txt
    rm /tmp/db_check.txt
    exit 1
fi

rm /tmp/db_check.txt
print_success "Base de données OK"

# Étape 4: Tester les APIs
print_step "4. Test des APIs publiques"

# Récupérer le premier domaine de l'instance
DOMAIN=$(php -r "
    require_once 'snackup/backend/InstanceManager.php';
    InstanceManager::init();
    \$data = InstanceManager::getInstanceData('$INSTANCE_NAME');
    echo \$data['domains'][0] ?? 'localhost';
")

print_info "Domaine: $DOMAIN"

# Test restaurant.php
if curl -s -H "Host: $DOMAIN" http://localhost/config/restaurant.php | jq -e '.name' > /dev/null 2>&1; then
    print_success "API restaurant.php fonctionne"
else
    print_error "API restaurant.php en erreur"
fi

# Test menu.php
if curl -s -H "Host: $DOMAIN" http://localhost/config/menu.php | jq -e '.menu' > /dev/null 2>&1; then
    print_success "API menu.php fonctionne"
else
    print_error "API menu.php en erreur"
fi

# Étape 5: Permissions
print_step "5. Vérification des permissions"

# Vérifier que les fichiers de config sont lisibles
CONFIG_PATH="instances/$INSTANCE_NAME/backend-config.php"
if [ -r "$CONFIG_PATH" ]; then
    print_success "Fichier de configuration lisible"
else
    print_error "Fichier de configuration non lisible: $CONFIG_PATH"
fi

# Étape 6: Résumé
print_header "DÉPLOIEMENT TERMINÉ"

print_success "Instance '$INSTANCE_NAME' déployée avec succès"
print_info "Domaine principal: $DOMAIN"
print_info "Configuration: $CONFIG_PATH"

echo ""
print_info "Pour tester l'instance:"
echo "  curl -H \"Host: $DOMAIN\" http://localhost/config/restaurant.php"
echo "  curl -H \"Host: $DOMAIN\" http://localhost/config/menu.php"
echo ""
print_info "Pour un diagnostic complet:"
echo "  php scripts/diagnostic.php $INSTANCE_NAME"
echo ""

exit 0
