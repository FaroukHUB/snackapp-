# Architecture Scalable Multi-Instance

## 🎯 Principe Fondamental

**La scalabilité est la BASE du projet.**
Tout correctif, toute fonctionnalité doit être **générique** et **réutilisable** pour toutes les instances sans modification de code.

## ✅ Règles d'Or de la Scalabilité

### 1. **JAMAIS de hardcoding**
❌ **INTERDIT:**
```php
// ❌ Hardcoding d'instance
if (strpos($host, 'atelierpizza') !== false) {
    $instanceName = 'atelier-pizza';
}

// ❌ Configuration spécifique à une instance dans le code
$currency = ($instance === 'marvelous') ? 'DA' : 'EUR';

// ❌ Chemin hardcodé
require 'instances/atelier-pizza/config.php';
```

✅ **CORRECT:**
```php
// ✅ Détection automatique via InstanceManager
$instanceName = InstanceManager::getCurrentInstance();

// ✅ Configuration dynamique
$currency = InstanceManager::getCurrency();

// ✅ Chargement dynamique
$config = InstanceManager::loadConfig();
```

### 2. **Toujours utiliser InstanceManager**
Pour **TOUT** ce qui concerne les instances:

```php
// Au début de chaque fichier API
require_once __DIR__ . '/snackup/backend/InstanceManager.php';

try {
    // Charger l'instance automatiquement
    $instanceConfig = InstanceManager::loadConfig();
    $restaurantId = InstanceManager::getRestaurantId();
    $currency = InstanceManager::getCurrency();
    $instanceId = InstanceManager::getInstanceId();

    // Initialiser la DB
    Database::init(InstanceManager::getDatabaseConfig());

} catch (Exception $e) {
    // Toujours inclure les infos de debug
    echo json_encode([
        'error' => 'Instance initialization failed',
        'message' => $e->getMessage(),
        'debug' => InstanceManager::getDebugInfo()
    ]);
    exit;
}
```

### 3. **Une seule source de vérité**
Toute la configuration des instances est dans **`config/instances.json`**

```json
{
  "instances": {
    "nouvelle-instance": {
      "name": "Nouvelle Instance",
      "domains": ["nouveau-restaurant.com"],
      "config_path": "instances/nouvelle-instance/backend-config.php",
      "enabled": true
    }
  }
}
```

### 4. **Configuration par instance**
Chaque instance a son propre fichier `backend-config.php`:

```
instances/
├── atelier-pizza/
│   └── backend-config.php    # Config spécifique Atelier Pizza
├── marvelous/
│   └── backend-config.php    # Config spécifique Marvelous
└── nouvelle-instance/
    └── backend-config.php    # Config nouvelle instance
```

Structure obligatoire:
```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'name' => 'db_name',
        'user' => 'db_user',
        'password' => 'db_password',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'Nom du Restaurant',
        'instance_id' => 'instance-slug',
        'restaurant_id' => 1,        // ID dans la table restaurants
        'currency' => 'EUR',         // EUR, DA, USD, etc.
        'timezone' => 'Europe/Paris',
        'locale' => 'fr_FR'
    ],
    'stripe' => [...],
    'email' => [...],
    'theme' => [
        'primary' => '#e63946',
        'primary_dark' => '#d62839',
        'secondary' => '#1a1a2e',
        'accent' => '#ff6fae'
    ]
];
```

## 📋 Comment Ajouter une Nouvelle Instance

### Étape 1: Créer le dossier de l'instance
```bash
mkdir -p instances/nouveau-restaurant
```

### Étape 2: Créer la configuration backend
```bash
cp instances/demo/backend-config.php instances/nouveau-restaurant/backend-config.php
```

Éditer les valeurs (database, app, stripe, etc.)

### Étape 3: Enregistrer l'instance dans instances.json
```json
{
  "instances": {
    "nouveau-restaurant": {
      "name": "Nouveau Restaurant",
      "domains": [
        "nouveau-restaurant.com",
        "www.nouveau-restaurant.com"
      ],
      "config_path": "instances/nouveau-restaurant/backend-config.php",
      "enabled": true
    }
  }
}
```

### Étape 4: Créer les données en base
```sql
-- Créer l'entrée restaurant
INSERT INTO restaurants (name, slug, phone, address, ...)
VALUES ('Nouveau Restaurant', 'nouveau-restaurant', ...);

-- Noter le restaurant_id généré
-- Le mettre dans backend-config.php → 'restaurant_id'
```

### Étape 5: Tester
```bash
php test-instance-manager.php
```

**C'EST TOUT !** Aucune modification de code nécessaire. ✅

## 🔧 API InstanceManager

### Méthodes principales

| Méthode | Description | Retour |
|---------|-------------|--------|
| `InstanceManager::detectInstance()` | Détecte l'instance selon le domaine | string |
| `InstanceManager::getCurrentInstance()` | Retourne le nom de l'instance | string |
| `InstanceManager::loadConfig()` | Charge la configuration complète | array |
| `InstanceManager::getRestaurantId()` | Retourne le restaurant_id | int |
| `InstanceManager::getInstanceId()` | Retourne l'instance_id | string |
| `InstanceManager::getCurrency()` | Retourne la devise | string |
| `InstanceManager::getDatabaseConfig()` | Retourne la config DB | array |
| `InstanceManager::getThemeConfig()` | Retourne la config thème | array |
| `InstanceManager::getDebugInfo()` | Infos de debug | array |

### Exemples d'utilisation

```php
// Récupérer la devise
$currency = InstanceManager::getCurrency(); // EUR, DA, etc.

// Récupérer le restaurant ID
$restaurantId = InstanceManager::getRestaurantId();

// Récupérer la config thème
$theme = InstanceManager::getThemeConfig();
$primaryColor = $theme['primary'] ?? '#e63946';

// Debug
$debug = InstanceManager::getDebugInfo();
print_r($debug);
```

## 🚨 Anti-Patterns à Éviter

### ❌ Créer des scripts spécifiques à une instance
```bash
# ❌ MAUVAIS
DIAGNOSTIC-ATELIER-PIZZA.sh
DEPLOIEMENT-MARVELOUS.sh
TEST-API-NOUVEAU-RESTAURANT.sh
```

### ✅ Créer des scripts génériques
```bash
# ✅ BON
scripts/diagnostic.sh <instance-name>
scripts/deploy.sh <instance-name>
scripts/test-api.sh <instance-name>
```

### ❌ Conditions sur le nom d'instance
```php
// ❌ MAUVAIS
if ($instanceName === 'atelier-pizza') {
    // Logique spécifique
}
```

### ✅ Utiliser la configuration
```php
// ✅ BON
if (InstanceManager::getCurrency() === 'EUR') {
    // Logique basée sur la devise, pas l'instance
}
```

## 📊 Architecture du Projet

```
snackapp/
├── config/
│   ├── instances.json              ← SOURCE DE VÉRITÉ (routing)
│   ├── restaurant.php              ← API (utilise InstanceManager)
│   └── menu.php                    ← API (utilise InstanceManager)
│
├── instances/
│   ├── atelier-pizza/
│   │   └── backend-config.php      ← Config Atelier Pizza
│   ├── marvelous/
│   │   └── backend-config.php      ← Config Marvelous
│   └── demo/
│       └── backend-config.php      ← Template
│
├── snackup/
│   └── backend/
│       ├── InstanceManager.php     ← GESTIONNAIRE CENTRAL
│       ├── Database.php
│       └── repositories/
│
└── scripts/
    ├── diagnostic.sh <instance>    ← Scripts génériques
    └── deploy.sh <instance>
```

## ✅ Checklist Avant Chaque Commit

- [ ] Aucun hardcoding de nom d'instance dans le code
- [ ] Aucun hardcoding de domaine dans le code
- [ ] Utilisation de InstanceManager pour toute détection d'instance
- [ ] Configuration dans `instances.json` ou `backend-config.php`
- [ ] Scripts génériques (pas spécifiques à une instance)
- [ ] Testé avec au moins 2 instances différentes
- [ ] Documentation mise à jour si nouvelle fonctionnalité

## 🧪 Tests

### Test manuel
```bash
php test-instance-manager.php
```

### Test d'une API
```bash
# Simuler un domaine
curl -H "Host: marvelous.mon-agenceweb.fr" http://localhost/config/restaurant.php
curl -H "Host: atelierpizza.fr" http://localhost/config/menu.php
```

## 📚 Ressources

- **InstanceManager:** `snackup/backend/InstanceManager.php`
- **Config instances:** `config/instances.json`
- **Tests:** `test-instance-manager.php`
- **Template config:** `instances/demo/backend-config.php`

## 🎓 Principes de Design

1. **DRY (Don't Repeat Yourself)**: Un seul endroit pour la logique d'instance
2. **OCP (Open/Closed Principle)**: Ouvert à l'extension (nouvelle instance), fermé à la modification (pas de changement de code)
3. **Single Responsibility**: InstanceManager gère TOUT ce qui concerne les instances
4. **Configuration over Code**: Ajout d'instance = config, pas code

---

**Rappel:** La scalabilité est NON-NÉGOCIABLE. Chaque PR doit respecter ces principes. 🚀
