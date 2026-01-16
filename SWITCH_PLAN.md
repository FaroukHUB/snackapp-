# PLAN DE SWITCH MYSQL — PHASE 4

## 📊 ÉTAT ACTUEL (après migration Phase 3)

### ✅ Déjà en MySQL
| Composant | Source | Statut |
|-----------|--------|--------|
| **menu.php** (config/menu.php) | MenuRepository (snackup) | ✅ 100% MySQL |
| **Données migrées** | Tables MySQL (categories, products, supplements) | ✅ Complètes |
| **MenuRepository** | snackup/backend/repositories/MenuRepository.php | ✅ Fonctionnel |

### ⚠️ Encore en JSON
| Composant | Source | Problème |
|-----------|--------|----------|
| **Admin Panel** (admin-panel-v2/api/products.php) | `$useMySQL = false` (ligne 210) | ❌ Lit/écrit JSON |
| **Runtime JSON** | config/menu.runtime.json | ❌ Écritures actives |
| **Public JSON** | config/menu.json | ❌ Regénéré par admin |

---

## 🎯 OBJECTIF PHASE 4

### Phase 4.1 : Activer MySQL dans l'admin
**Résultat** : Admin lit/écrit 100% MySQL (plus de JSON)

### Phase 4.2 : Désactiver écritures JSON runtime
**Résultat** : menu.runtime.json et menu.json en READ-ONLY (fallback uniquement)

### Phase 4.3 : Mode SAFE SWITCH
**Résultat** : Variable d'environnement pour rollback temporaire vers JSON en lecture seule

---

## 🔍 ANALYSE DU CODE

### 1. Définition de $useMySQL

**Fichier** : `snackup/admin/bootstrap.php`
```php
// Ligne 44
define('SNACK_USE_JSON', false);

// Ligne 48
if (!SNACK_USE_JSON && defined('SNACK_RESTAURANT_ID')) {
    MenuRepository::$restaurantId = SNACK_RESTAURANT_ID;
}
```

**Fichier** : `admin-panel-v2/api/products.php`
```php
// Ligne 210
$useMySQL = false;  // ❌ HARDCODÉ À FALSE
```

**Autres fichiers** :
- `admin-panel-v2/index.php` : `$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');`
- `admin-panel-v2/api/orders.php` : `$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');`
- `snackup/admin/webhook.php` : `$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');`

### 2. Lecture menu.php (DÉJÀ MYSQL ✅)

**Fichier** : `config/menu.php`
```php
// Lignes 42-44 : Lecture 100% MySQL
$categories = MenuRepository::getAllCategories();
$supplements = MenuRepository::getAllSupplements();
$categorySupplements = MenuRepository::getCategorySupplements();

// Lignes 56-73 : Fallback menu.json (formules, featured, categoryIcons)
if (file_exists($menuJsonPath)) {
    $menuData = json_decode(file_get_contents($menuJsonPath), true);
    $formules = $menuData['formules'] ?? [];
    $featured = $menuData['featured'] ?? $featured;
    $categoryIcons = $menuData['categoryIcons'] ?? [];
}
```

**✅ CONCLUSION** : menu.php lit déjà MySQL. Fallback JSON uniquement pour formules/featured/categoryIcons.

### 3. Admin Panel - Mode JSON actif

**Fichier** : `admin-panel-v2/api/products.php`

**GET** (ligne 218) :
```php
if ($useMySQL) {
    // Lit depuis MenuRepository (MySQL)
} else {
    // ❌ Lit depuis menu.runtime.json + menu.json (ACTIF)
    $merged = applyRuntimeToConfig($config, $runtime);
}
```

**POST** (ligne 340+) :
```php
if ($useMySQL) {
    // Écrit via MenuRepository::addCategory(), etc.
} else {
    // ❌ Écrit dans menu.runtime.json via saveMenuRuntime() (ACTIF)
    saveMenuRuntime($runtime, true);
}
```

**⚠️ PROBLÈME** : Admin écrit toujours dans JSON au lieu de MySQL.

### 4. Écritures JSON Runtime

**Fichier** : `admin-panel-v2/config.php`

**Fonction** : `saveMenuRuntime($runtime, $autoSync = false)` (ligne 200)
- Écrit dans `config/menu.runtime.json`
- Si `$autoSync = true` → appelle `generatePublicMenuJson()`

**Fonction** : `generatePublicMenuJson($runtime)` (ligne 247)
- Lit menu.json existant
- Fusionne runtime
- Écrit dans `config/menu.json`

**Appels dans products.php** : 14 appels à `saveMenuRuntime()`
- Ligne 635, 709, 774, 834, 934, 972, 1016, 1085, 1122, 1167, 1258, 1333, 1374

**⚠️ PROBLÈME** : À chaque modification admin, menu.runtime.json ET menu.json sont réécrits.

---

## ⚡ PLAN DE SWITCH EN 2 ÉTAPES

### ÉTAPE 1 : Activer MySQL dans l'admin (Phase 4.1)

#### Modifications minimales

**1.1 Activer $useMySQL dans products.php**

**Fichier** : `admin-panel-v2/api/products.php`
```php
// Ligne 210 : AVANT
$useMySQL = false;

// Ligne 210 : APRÈS
$useMySQL = true;  // ✅ ACTIVER MYSQL
```

**Impact** :
- GET : Lit depuis MenuRepository (MySQL) au lieu de JSON
- POST : Écrit via MenuRepository au lieu de saveMenuRuntime()
- ✅ Les repositories Phase 2 sont déjà présents et fonctionnels

**1.2 Vérifier les autres fichiers admin**

**Fichier** : `admin-panel-v2/index.php`
```php
// Ligne 13 : déjà correct (dérive de SNACK_USE_JSON = false)
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
// → $useMySQL = true
```

**Fichier** : `admin-panel-v2/api/orders.php`
```php
// Déjà correct (dérive de SNACK_USE_JSON)
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
```

**Fichier** : `snackup/admin/webhook.php`
```php
// Déjà correct
$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
```

**✅ CONCLUSION Étape 1** :
- **1 seule modification** : `admin-panel-v2/api/products.php` ligne 210
- Tous les autres fichiers sont déjà correctement configurés

---

### ÉTAPE 2 : Désactiver écritures JSON (Phase 4.2)

#### 2.1 Rendre saveMenuRuntime() READ-ONLY

**Fichier** : `admin-panel-v2/config.php`

**Option A** : Ajouter un check au début de la fonction
```php
// Ligne 200 : AVANT
function saveMenuRuntime($runtime, $autoSync = false) {
    // ... écriture ...
}

// APRÈS
function saveMenuRuntime($runtime, $autoSync = false) {
    // ⚠️ MODE MYSQL : Écritures JSON désactivées
    if (!SNACK_USE_JSON) {
        error_log('[MIGRATION] saveMenuRuntime() appelé mais écritures JSON désactivées (mode MySQL actif)');
        return true; // Retourner true pour ne pas casser le code appelant
    }

    // ... écriture JSON (seulement si SNACK_USE_JSON = true) ...
}
```

**Option B** : Remplacer complètement par une fonction no-op
```php
function saveMenuRuntime($runtime, $autoSync = false) {
    // 🚫 DÉSACTIVÉ - Mode MySQL actif
    // Les données sont écrites directement en BDD via repositories
    error_log('[INFO] saveMenuRuntime() appelé en mode MySQL - opération ignorée');
    return true;
}
```

#### 2.2 Rendre generatePublicMenuJson() READ-ONLY

**Fichier** : `admin-panel-v2/config.php`

```php
// Ligne 247 : AVANT
function generatePublicMenuJson($runtime) {
    // ... écriture menu.json ...
}

// APRÈS
function generatePublicMenuJson($runtime) {
    // ⚠️ MODE MYSQL : menu.json généré par menu.php, pas par l'admin
    if (!SNACK_USE_JSON) {
        error_log('[MIGRATION] generatePublicMenuJson() appelé mais mode MySQL actif - opération ignorée');
        return true;
    }

    // ... écriture JSON (seulement si SNACK_USE_JSON = true) ...
}
```

**✅ RÉSULTAT** :
- menu.runtime.json : aucune écriture
- menu.json : aucune réécriture par l'admin
- Fallback : menu.json existant reste disponible en lecture pour formules/featured

---

## 🛡️ MODE SAFE SWITCH (Rollback temporaire)

### Objectif
Permettre un rollback RAPIDE vers JSON en cas d'incident, SANS réactiver les écritures.

### Implémentation

**Fichier** : `config/.env` (nouveau)
```env
# SAFE SWITCH: Mode de secours
# Valeurs possibles:
#   mysql    = Mode normal (MySQL actif)
#   json_ro  = Rollback temporaire (JSON en lecture seule)
MENU_MODE=mysql
```

**Fichier** : `admin-panel-v2/bootstrap.php` (ou config.php)
```php
// Charger .env si disponible
$envFile = __DIR__ . '/../config/.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    $menuMode = $envVars['MENU_MODE'] ?? 'mysql';
} else {
    $menuMode = 'mysql'; // Par défaut
}

// Définir SNACK_USE_JSON selon le mode
if ($menuMode === 'json_ro') {
    define('SNACK_USE_JSON', true);  // ✅ Rollback temporaire
    error_log('[SAFE SWITCH] Mode JSON READ-ONLY activé');
} else {
    define('SNACK_USE_JSON', false); // Mode MySQL normal
}
```

**Fichier** : `admin-panel-v2/config.php`
```php
function saveMenuRuntime($runtime, $autoSync = false) {
    // 🚫 TOUJOURS désactiver les écritures (même en mode json_ro)
    error_log('[SAFE SWITCH] saveMenuRuntime() désactivé (mode read-only)');
    return true;
}

function generatePublicMenuJson($runtime) {
    // 🚫 TOUJOURS désactiver les écritures
    error_log('[SAFE SWITCH] generatePublicMenuJson() désactivé (mode read-only)');
    return true;
}
```

### Procédure de rollback

**En cas d'incident :**
```bash
# 1. Basculer en mode JSON read-only
echo "MENU_MODE=json_ro" > config/.env

# 2. L'admin affiche les données depuis menu.json au lieu de MySQL
# 3. Aucune écriture possible (ni JSON ni MySQL)

# 4. Diagnostiquer le problème
mysql -u zajr1824_marvelous -p zajr1824_marvelous < CHECK_MIGRATION.sql

# 5. Corriger le problème

# 6. Revenir en mode MySQL
echo "MENU_MODE=mysql" > config/.env
```

**✅ GARANTIES** :
- Lecture : JSON (fallback) OU MySQL selon MENU_MODE
- Écriture : **TOUJOURS désactivée** en production (sauf si explicitement réactivée)
- Rollback : 1 ligne de commande

---

## 📋 CHECKLIST PHASE 4

### Phase 4.1 : Activer MySQL admin
- [ ] Modifier `admin-panel-v2/api/products.php` ligne 210 : `$useMySQL = true`
- [ ] Tester GET : vérifier que l'admin affiche les données MySQL
- [ ] Tester POST : créer/modifier/supprimer catégorie/produit/supplément
- [ ] Vérifier que les données sont écrites en MySQL (pas dans JSON)

### Phase 4.2 : Désactiver écritures JSON
- [ ] Modifier `admin-panel-v2/config.php` : `saveMenuRuntime()` → read-only
- [ ] Modifier `admin-panel-v2/config.php` : `generatePublicMenuJson()` → read-only
- [ ] Tester que menu.runtime.json n'est plus modifié
- [ ] Tester que menu.json n'est plus regénéré par l'admin

### Phase 4.3 : Implémenter SAFE SWITCH
- [ ] Créer `config/.env` avec `MENU_MODE=mysql`
- [ ] Modifier `admin-panel-v2/bootstrap.php` : charger .env
- [ ] Tester rollback : `MENU_MODE=json_ro`
- [ ] Vérifier que les écritures restent désactivées en mode json_ro
- [ ] Retour en mode MySQL : `MENU_MODE=mysql`

---

## 📁 FICHIERS À MODIFIER

### Phase 4.1
| Fichier | Ligne | Modification | Risque |
|---------|-------|--------------|--------|
| `admin-panel-v2/api/products.php` | 210 | `$useMySQL = true;` | ⚠️ MOYEN |

### Phase 4.2
| Fichier | Ligne | Modification | Risque |
|---------|-------|--------------|--------|
| `admin-panel-v2/config.php` | 200 | `saveMenuRuntime()` → read-only | ⚠️ FAIBLE |
| `admin-panel-v2/config.php` | 247 | `generatePublicMenuJson()` → read-only | ⚠️ FAIBLE |

### Phase 4.3 (SAFE SWITCH)
| Fichier | Ligne | Modification | Risque |
|---------|-------|--------------|--------|
| `config/.env` | N/A | Créer fichier | ✅ AUCUN |
| `admin-panel-v2/bootstrap.php` | ~30 | Charger .env | ⚠️ FAIBLE |

---

## ⚠️ RISQUES & MITIGATIONS

### Risque 1 : MenuRepository incomplet
**Symptôme** : Fonctionnalités manquantes vs JSON
**Mitigation** :
- Vérifier que TOUTES les méthodes de products.php (mode $useMySQL) existent dans MenuRepository
- Tester CHAQUE endpoint admin après switch

### Risque 2 : Données corrompues
**Symptôme** : Admin affiche des données incorrectes
**Mitigation** :
- Exécuter CHECK_MIGRATION.sql AVANT Phase 4.1
- Vérifier que les counts sont corrects (12 cat, 47 prod, 40 supp)
- Mode SAFE SWITCH permet rollback immédiat

### Risque 3 : Performance MySQL
**Symptôme** : Admin plus lent qu'avec JSON
**Mitigation** :
- Les index sont déjà en place (Phase 1)
- MenuRepository utilise prepared statements
- Monitoring : temps de réponse API

### Risque 4 : Permissions fichiers
**Symptôme** : Impossible de créer .env
**Mitigation** :
- Vérifier `chmod 644 config/.env`
- Alternative : variable d'environnement système

---

## 🎯 RÉSUMÉ

### Avant Phase 4
- menu.php : ✅ MySQL
- Admin : ❌ JSON
- Runtime : ❌ Écritures actives

### Après Phase 4.1
- menu.php : ✅ MySQL
- Admin : ✅ MySQL
- Runtime : ❌ Écritures actives (mais ignorées)

### Après Phase 4.2
- menu.php : ✅ MySQL
- Admin : ✅ MySQL
- Runtime : ✅ READ-ONLY

### Après Phase 4.3 (SAFE SWITCH)
- menu.php : ✅ MySQL (rollback possible)
- Admin : ✅ MySQL (rollback possible)
- Runtime : ✅ READ-ONLY permanent
- Rollback : ✅ 1 ligne de commande

---

**Auteur** : Claude (Phase 4 - Switch MySQL)
**Date** : 2026-01-16
**Version** : 1.0
