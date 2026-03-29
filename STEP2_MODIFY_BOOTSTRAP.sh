#!/bin/bash
# STEP 2/3 : Rendre restaurant_id dynamique dans bootstrap.php
# À exécuter sur le serveur SSH

echo "=== STEP 2/3 : Modification bootstrap.php pour restaurant_id dynamique ==="
echo ""

cd atelierpizza.mon-agenceweb.fr

# MODIFICATION 1 : Charger InstanceManager après ligne 41
echo "1. Ajout chargement InstanceManager..."
sed -i '41a\
\
// Charger InstanceManager pour récupérer la config de l'"'"'instance\
require_once SNACK_ROOT . '"'"'/snackup/backend/InstanceManager.php'"'"';\
\
// Récupérer la config de l'"'"'instance active\
$instanceConfig = InstanceManager::getActiveConfig();\
$restaurantId = $instanceConfig['"'"'restaurant_id'"'"'] ?? 2; // Fallback Le Marvelous' admin-panel-v2/bootstrap.php

# MODIFICATION 2 : Changer les require_once pour utiliser snackup/backend/repositories/
echo "2. Correction chemins repositories..."
sed -i "s|SNACK_DB_PATH . '/repositories/RestaurantRepository.php'|SNACK_ROOT . '/snackup/backend/repositories/RestaurantRepository.php'|g" admin-panel-v2/bootstrap.php
sed -i "s|SNACK_DB_PATH . '/repositories/MenuRepository.php'|SNACK_ROOT . '/snackup/backend/repositories/MenuRepository.php'|g" admin-panel-v2/bootstrap.php
sed -i "s|SNACK_DB_PATH . '/repositories/OrderRepository.php'|SNACK_ROOT . '/snackup/backend/repositories/OrderRepository.php'|g" admin-panel-v2/bootstrap.php
sed -i "s|SNACK_DB_PATH . '/repositories/CustomerRepository.php'|SNACK_ROOT . '/snackup/backend/repositories/CustomerRepository.php'|g" admin-panel-v2/bootstrap.php
sed -i "s|SNACK_DB_PATH . '/repositories/PromoCodeRepository.php'|SNACK_ROOT . '/snackup/backend/repositories/PromoCodeRepository.php'|g" admin-panel-v2/bootstrap.php

# MODIFICATION 3 : Ajouter initialisation MenuRepository après les require
echo "3. Ajout initialisation MenuRepository..."
sed -i '/PromoCodeRepository.php/a\
\
// Initialiser MenuRepository avec le restaurant_id de l'"'"'instance\
MenuRepository::$restaurantId = $restaurantId;' admin-panel-v2/bootstrap.php

# MODIFICATION 4 : Remplacer SNACK_RESTAURANT_ID hardcodé par la variable dynamique
echo "4. Remplacement SNACK_RESTAURANT_ID..."
sed -i 's/define('"'"'SNACK_RESTAURANT_ID'"'"', 2);/define('"'"'SNACK_RESTAURANT_ID'"'"', $restaurantId);/g' admin-panel-v2/bootstrap.php

echo ""
echo "=== Vérification syntaxe PHP ==="
php -l admin-panel-v2/bootstrap.php

echo ""
echo "=== Affichage des modifications (lignes 36-85) ==="
sed -n '36,85p' admin-panel-v2/bootstrap.php

echo ""
echo "✅ STEP 2/3 TERMINÉ"
echo ""
echo "Vérifiez que :"
echo "- InstanceManager est chargé après ligne 41"
echo "- Les repositories sont chargés depuis snackup/backend/repositories/"
echo "- MenuRepository::\$restaurantId est initialisé avec \$restaurantId"
echo "- SNACK_RESTAURANT_ID utilise \$restaurantId (pas hardcodé 2)"
