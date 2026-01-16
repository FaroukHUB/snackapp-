# PHASE 4 — MODIFICATIONS MINIMALES

## 📋 Modifications à effectuer (ordre exact)

---

## PHASE 4.1 : Activer MySQL dans l'admin

### Modification 1 : admin-panel-v2/api/products.php

**Fichier** : `admin-panel-v2/api/products.php`
**Ligne** : 210

**AVANT** :
```php
$useMySQL = false;
```

**APRÈS** :
```php
$useMySQL = true;  // ✅ Phase 4.1 : Activer MySQL dans l'admin
```

**Impact** :
- GET `/api/products.php` lit depuis MenuRepository (MySQL)
- POST `/api/products.php` écrit via MenuRepository (MySQL)
- ⚠️ Tous les appels à `saveMenuRuntime()` dans ce fichier seront IGNORÉS car dans la branche `else`

**Test** :
```bash
# 1. Ouvrir l'admin panel
# 2. Vérifier que les catégories/produits s'affichent
# 3. Créer une catégorie de test
# 4. Vérifier en BDD :
mysql -u zajr1824_marvelous -p zajr1824_marvelous -e "SELECT * FROM categories WHERE restaurant_id = 1 ORDER BY id DESC LIMIT 1;"

# 5. Supprimer la catégorie de test
# 6. Vérifier que deleted_at est rempli (soft delete)
```

---

## PHASE 4.2 : Désactiver écritures JSON runtime

### Modification 2 : admin-panel-v2/config.php (saveMenuRuntime)

**Fichier** : `admin-panel-v2/config.php`
**Ligne** : 200

**AVANT** :
```php
function saveMenuRuntime($runtime, $autoSync = false) {
    $runtime = is_array($runtime) ? $runtime : [];
    $runtime['products'] = isset($runtime['products']) && is_array($runtime['products']) ? $runtime['products'] : [];
    // ... reste du code ...
```

**APRÈS** :
```php
function saveMenuRuntime($runtime, $autoSync = false) {
    // ⚠️ PHASE 4.2 : Écritures JSON désactivées (mode MySQL actif)
    // Les données sont écrites directement en BDD via MenuRepository
    if (!SNACK_USE_JSON) {
        error_log('[MIGRATION] saveMenuRuntime() appelé mais écritures JSON désactivées (mode MySQL actif)');
        return true; // Retourner true pour ne pas casser le code appelant
    }

    // Code original (uniquement si SNACK_USE_JSON = true)
    $runtime = is_array($runtime) ? $runtime : [];
    $runtime['products'] = isset($runtime['products']) && is_array($runtime['products']) ? $runtime['products'] : [];
    // ... reste du code INCHANGÉ ...
```

**Impact** :
- Si `SNACK_USE_JSON = false` (mode MySQL) → fonction retourne `true` immédiatement
- `menu.runtime.json` n'est plus modifié
- ✅ Préserve la compatibilité si jamais on doit revenir en mode JSON

**Test** :
```bash
# 1. Noter la date de modification de menu.runtime.json
ls -la config/menu.runtime.json

# 2. Modifier un produit dans l'admin

# 3. Vérifier que menu.runtime.json n'a PAS été modifié
ls -la config/menu.runtime.json
# → Date doit être IDENTIQUE

# 4. Vérifier que la modification est en MySQL
mysql -u zajr1824_marvelous -p zajr1824_marvelous -e "SELECT name, updated_at FROM products WHERE restaurant_id = 1 ORDER BY updated_at DESC LIMIT 1;"
```

---

### Modification 3 : admin-panel-v2/config.php (generatePublicMenuJson)

**Fichier** : `admin-panel-v2/config.php`
**Ligne** : 247

**AVANT** :
```php
function generatePublicMenuJson($runtime) {
    $config = loadConfig();
    if (!$config) return false;
    // ... reste du code ...
```

**APRÈS** :
```php
function generatePublicMenuJson($runtime) {
    // ⚠️ PHASE 4.2 : menu.json généré par menu.php depuis MySQL, pas par l'admin
    if (!SNACK_USE_JSON) {
        error_log('[MIGRATION] generatePublicMenuJson() appelé mais mode MySQL actif - opération ignorée');
        return true;
    }

    // Code original (uniquement si SNACK_USE_JSON = true)
    $config = loadConfig();
    if (!$config) return false;
    // ... reste du code INCHANGÉ ...
```

**Impact** :
- `menu.json` n'est plus régénéré par l'admin
- ✅ menu.php continue de lire depuis MySQL (déjà en place)
- ✅ Fallback menu.json reste disponible pour formules/featured/categoryIcons

**Test** :
```bash
# 1. Noter la date de modification de menu.json
ls -la config/menu.json

# 2. Ajouter un produit dans l'admin

# 3. Vérifier que menu.json n'a PAS été modifié
ls -la config/menu.json
# → Date doit être IDENTIQUE

# 4. Vérifier que le produit apparaît sur le site (menu.php lit MySQL)
curl http://localhost/config/menu.php | jq '.menu.categories[-1].items[-1]'
```

---

## PHASE 4.3 : Implémenter SAFE SWITCH

### Modification 4 : Créer config/.env

**Fichier** : `config/.env` (NOUVEAU)

**Contenu** :
```env
# SnackApp - Configuration Mode Menu
#
# MENU_MODE : Mode de lecture du menu
#   mysql    = Mode normal (lecture/écriture MySQL)
#   json_ro  = Mode secours (lecture JSON uniquement, AUCUNE écriture)
#
# Utilisation:
#   - Mode normal : MENU_MODE=mysql
#   - Rollback temporaire : MENU_MODE=json_ro
#   - Retour normal : MENU_MODE=mysql
#
MENU_MODE=mysql
```

**Impact** :
- Permet de basculer entre MySQL et JSON read-only
- ✅ Rollback instantané en cas d'incident
- ⚠️ Les écritures restent DÉSACTIVÉES dans tous les modes

**Test** :
```bash
# 1. Créer le fichier
echo "MENU_MODE=mysql" > config/.env

# 2. Vérifier les permissions
chmod 644 config/.env

# 3. Tester rollback
echo "MENU_MODE=json_ro" > config/.env

# 4. Vérifier que l'admin lit menu.json
# (après implémentation modification 5)

# 5. Retour en mode MySQL
echo "MENU_MODE=mysql" > config/.env
```

---

### Modification 5 : admin-panel-v2/bootstrap.php (ou config.php)

**Fichier** : `admin-panel-v2/bootstrap.php` (ou début de `config.php` si bootstrap n'existe pas)
**Ligne** : Juste AVANT `define('SNACK_USE_JSON', false);` (actuellement ligne 44 dans snackup/admin/bootstrap.php)

**AVANT** :
```php
// Constantes pour le mode MySQL
define('SNACK_USE_JSON', false);
define('SNACK_RESTAURANT_ID', RESTAURANT_ID);
```

**APRÈS** :
```php
// ⚡ PHASE 4.3 : SAFE SWITCH - Mode de secours configurable
// Charger .env si disponible
$envFile = __DIR__ . '/../config/.env';
$menuMode = 'mysql'; // Par défaut

if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    $menuMode = $envVars['MENU_MODE'] ?? 'mysql';
}

// Définir SNACK_USE_JSON selon le mode
if ($menuMode === 'json_ro') {
    define('SNACK_USE_JSON', true);  // ✅ Rollback temporaire (lecture JSON)
    error_log('[SAFE SWITCH] Mode JSON READ-ONLY activé');
} else {
    define('SNACK_USE_JSON', false); // Mode MySQL normal
}

define('SNACK_RESTAURANT_ID', RESTAURANT_ID);
```

**Impact** :
- Si `MENU_MODE=json_ro` → lit depuis JSON (fallback)
- Si `MENU_MODE=mysql` → lit depuis MySQL (normal)
- ✅ Les écritures restent désactivées (Modification 2 et 3)

**Test** :
```bash
# 1. Mode MySQL (normal)
echo "MENU_MODE=mysql" > config/.env

# 2. Ouvrir l'admin
# → Doit afficher les données MySQL

# 3. Basculer en mode JSON read-only
echo "MENU_MODE=json_ro" > config/.env

# 4. Rafraîchir l'admin
# → Doit afficher les données de menu.json

# 5. Tenter de modifier un produit
# → Doit échouer silencieusement (saveMenuRuntime retourne true mais n'écrit pas)

# 6. Retour en mode MySQL
echo "MENU_MODE=mysql" > config/.env
```

---

## VÉRIFICATION FINALE

### Checklist post-modifications

#### Phase 4.1 ✅
- [ ] `admin-panel-v2/api/products.php` ligne 210 : `$useMySQL = true`
- [ ] Admin affiche les données MySQL
- [ ] Création/modification/suppression écrit en MySQL
- [ ] Aucune erreur en console navigateur

#### Phase 4.2 ✅
- [ ] `admin-panel-v2/config.php` : `saveMenuRuntime()` désactivé si MySQL
- [ ] `admin-panel-v2/config.php` : `generatePublicMenuJson()` désactivé si MySQL
- [ ] `menu.runtime.json` non modifié après changement admin
- [ ] `menu.json` non modifié après changement admin
- [ ] Site public affiche toujours les données (menu.php lit MySQL)

#### Phase 4.3 ✅
- [ ] `config/.env` créé avec `MENU_MODE=mysql`
- [ ] `admin-panel-v2/bootstrap.php` charge .env
- [ ] Mode `json_ro` : admin lit JSON
- [ ] Mode `json_ro` : aucune écriture possible
- [ ] Mode `mysql` : admin lit/écrit MySQL

---

## ROLLBACK EN CAS DE PROBLÈME

### Scénario 1 : Erreur après Phase 4.1

```bash
# Revenir en mode JSON
# Modifier admin-panel-v2/api/products.php ligne 210
$useMySQL = false;  # ⚠️ Rollback temporaire
```

### Scénario 2 : Erreur après Phase 4.2

```bash
# Commenter les checks dans config.php
# Ligne 200 : saveMenuRuntime()
# Ligne 247 : generatePublicMenuJson()

# AVANT (Phase 4.2)
if (!SNACK_USE_JSON) {
    error_log('[MIGRATION] saveMenuRuntime() appelé...');
    return true;
}

# APRÈS (Rollback)
// TEMPORAIRE: Rollback Phase 4.2
// if (!SNACK_USE_JSON) {
//     error_log('[MIGRATION] saveMenuRuntime() appelé...');
//     return true;
// }
```

### Scénario 3 : Erreur après Phase 4.3

```bash
# Basculer en mode JSON read-only
echo "MENU_MODE=json_ro" > config/.env

# OU supprimer .env pour revenir au comportement par défaut
rm config/.env
```

---

## ORDRE D'EXÉCUTION RECOMMANDÉ

1. **Backup complet** : `cp -r /home/user/snackapp- /home/user/snackapp-backup-phase4`
2. **Phase 4.1** : Modification 1 uniquement
3. **Test Phase 4.1** : Vérifier que l'admin fonctionne 100%
4. **Phase 4.2** : Modifications 2 et 3
5. **Test Phase 4.2** : Vérifier que les JSON ne sont plus modifiés
6. **Phase 4.3** : Modifications 4 et 5
7. **Test Phase 4.3** : Tester rollback avec `MENU_MODE=json_ro`
8. **Validation finale** : Exécuter tous les tests de la checklist

---

**⚠️ IMPORTANT** :
- NE PAS faire toutes les modifications en une fois
- Tester CHAQUE phase individuellement
- Garder un backup avant chaque phase
- Vérifier les logs Apache/PHP après chaque modification

---

**Auteur** : Claude (Phase 4 - Modifications Minimales)
**Date** : 2026-01-16
**Version** : 1.0
