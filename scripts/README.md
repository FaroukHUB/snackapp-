# Scripts Génériques Multi-Instance

Scripts utilitaires pour la gestion et le diagnostic des instances restaurant.

## 📋 Scripts Disponibles

### 1. `diagnostic.php` - Diagnostic Multi-Instance

Effectue un diagnostic complet d'une ou plusieurs instances.

**Usage:**
```bash
# Diagnostic d'une instance spécifique
php scripts/diagnostic.php atelier-pizza
php scripts/diagnostic.php marvelous

# Diagnostic de toutes les instances
php scripts/diagnostic.php
```

**Ce qui est vérifié:**
- ✅ Configuration (instances.json + backend-config.php)
- ✅ Connexion base de données
- ✅ Tables essentielles (restaurants, menu_categories, menu_items, etc.)
- ✅ Données restaurant (nom, slug, téléphone, adresse)
- ✅ Menu (catégories, articles, suppléments)
- ✅ APIs publiques (restaurant.php, menu.php)
- ✅ Fichiers de configuration

**Sortie:**
```
╔════════════════════════════════════════════════════════════════════╗
║         DIAGNOSTIC MULTI-INSTANCE - ARCHITECTURE SCALABLE         ║
╚════════════════════════════════════════════════════════════════════╝

==================================================================
  DIAGNOSTIC : ATELIER-PIZZA
==================================================================

1. Configuration
   --------------------------------------------------
✓ Configuration chargée
   - Restaurant ID: 3
   - Instance ID: atelier-pizza-roubaix
   - Devise: EUR
   - Domaines: atelierpizza.fr, www.atelierpizza.fr

2. Base de données
   --------------------------------------------------
✓ Connexion à la base de données OK
✓ Table 'restaurants' existe (3 enregistrements)
✓ Table 'menu_categories' existe (12 enregistrements)
[...]
```

### 2. `deploy.sh` - Déploiement Instance

Déploie et vérifie une instance restaurant.

**Usage:**
```bash
# Déployer une instance spécifique
./scripts/deploy.sh atelier-pizza
./scripts/deploy.sh marvelous
```

**Étapes du déploiement:**
1. ✅ Vérification de l'instance dans instances.json
2. ✅ Chargement de la configuration
3. ✅ Vérification de la base de données
4. ✅ Test des APIs publiques
5. ✅ Vérification des permissions

**Sortie:**
```
========================================
  DÉPLOIEMENT: atelier-pizza
========================================

▶ 1. Vérification de l'instance
✓ Instance 'atelier-pizza' trouvée

▶ 2. Chargement de la configuration
ℹ Restaurant ID: 3
ℹ Instance ID: atelier-pizza-roubaix
ℹ Devise: EUR
✓ Configuration chargée

[...]

========================================
  DÉPLOIEMENT TERMINÉ
========================================
✓ Instance 'atelier-pizza' déployée avec succès
```

## 🚀 Exemples d'Utilisation

### Ajouter une nouvelle instance

```bash
# 1. Créer le dossier et la configuration
mkdir -p instances/nouveau-restaurant
cp instances/demo/backend-config.php instances/nouveau-restaurant/

# 2. Éditer la configuration
nano instances/nouveau-restaurant/backend-config.php

# 3. Enregistrer dans instances.json
nano config/instances.json

# 4. Tester
php scripts/diagnostic.php nouveau-restaurant

# 5. Déployer
./scripts/deploy.sh nouveau-restaurant
```

### Diagnostiquer toutes les instances

```bash
# Diagnostic complet de toutes les instances
php scripts/diagnostic.php

# Résumé:
# ✓ marvelous: OK
# ✓ atelier-pizza: OK
# ✓ nouveau-restaurant: OK
```

### Vérifier une instance après modification

```bash
# Après avoir modifié le menu ou la config
php scripts/diagnostic.php atelier-pizza

# Vérifier les APIs
curl -H "Host: atelierpizza.fr" http://localhost/config/restaurant.php | jq
curl -H "Host: atelierpizza.fr" http://localhost/config/menu.php | jq
```

## 🔧 Dépendances

- PHP 7.4+
- MySQL/MariaDB
- `curl` (pour deploy.sh)
- `jq` (optionnel, pour formater le JSON)

## 📚 Documentation

Pour plus d'informations sur l'architecture scalable:
- [ARCHITECTURE-SCALABLE.md](../ARCHITECTURE-SCALABLE.md)
- [InstanceManager API](../snackup/backend/InstanceManager.php)

## ⚠️ Remarques Importantes

1. **Toujours tester avant de déployer en production**
   ```bash
   php scripts/diagnostic.php <instance>
   ```

2. **Ne jamais hardcoder de noms d'instances dans les scripts**
   - Utiliser l'argument en ligne de commande
   - Utiliser InstanceManager pour la détection

3. **Toujours vérifier le code de retour**
   ```bash
   if php scripts/diagnostic.php atelier-pizza; then
       echo "OK"
   else
       echo "ERREUR"
   fi
   ```

4. **Logs et debugging**
   - Les erreurs sont affichées avec des symboles ✗
   - Le code de retour est 0 si succès, 1 si erreur
   - Utilisez `-v` ou `-vv` pour plus de verbosité (si implémenté)

## 🐛 Troubleshooting

### Erreur "Instance introuvable"
```bash
# Vérifier instances.json
cat config/instances.json | jq

# Lister les instances disponibles
php -r "require 'snackup/backend/InstanceManager.php';
        InstanceManager::init();
        print_r(array_keys(InstanceManager::getAllInstances()));"
```

### Erreur de connexion base de données
```bash
# Vérifier la configuration
cat instances/<instance>/backend-config.php

# Tester la connexion
php -r "require 'snackup/backend/Database.php';
        \$config = require 'instances/<instance>/backend-config.php';
        Database::init(\$config['database']);
        var_dump(Database::getConnection());"
```

### API retourne une erreur
```bash
# Vérifier les logs
tail -f /var/log/apache2/error.log

# Tester manuellement
curl -v -H "Host: domain.com" http://localhost/config/restaurant.php
```

## 📝 Contribuer

Lors de la création de nouveaux scripts:

1. ✅ Utiliser InstanceManager pour la détection d'instance
2. ✅ Accepter le nom d'instance en argument CLI
3. ✅ Retourner un code de retour approprié (0=succès, 1=erreur)
4. ✅ Afficher des messages clairs avec symboles (✓, ✗, ℹ)
5. ✅ Documenter dans ce README

## 🔗 Liens Utiles

- [Documentation InstanceManager](../snackup/backend/InstanceManager.php)
- [Guide d'architecture scalable](../ARCHITECTURE-SCALABLE.md)
- [Test InstanceManager](../test-instance-manager.php)
