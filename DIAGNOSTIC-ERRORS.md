# 🔧 Guide de Diagnostic - Erreurs API

**Date:** 2026-01-09
**Problème:** Erreurs 404/503 sur `api/products.php` côté admin et site

---

## 📋 Symptômes

### Côté Admin:
```
failed to load resource: the server responded with a status of 404
api/products.php:1 Failed to load resource: the server responded with a status of 503
VM52:1 Uncaught SyntaxError: Invalid or unexpected token
```

### Côté Site:
```
failed to load resource: the server responded with a status of 404
api/products.php:1 Failed to load resource: the server responded with a status of 503
```

---

## ✅ Corrections Apportées

### 1. **Amélioration Gestion d'Erreur JavaScript**

**Fichier:** `admin-panel-v2/assets/js/products.js`

**Avant:**
```javascript
const response = await fetch('api/products.php?action=list');
const data = await response.json(); // ❌ Crash si réponse non-JSON
```

**Après:**
```javascript
const response = await fetch('api/products.php?action=list');

// Vérifier status HTTP
if (!response.ok) {
    showApiError('Impossible de charger les produits', response.status);
    return;
}

// Vérifier Content-Type JSON
const contentType = response.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
    showApiError('Réponse API invalide (non-JSON)', response.status);
    return;
}

const data = await response.json(); // ✅ Safe parsing
```

**Avantages:**
- ✅ Plus de crash JavaScript "SyntaxError"
- ✅ Messages d'erreur clairs à l'utilisateur
- ✅ Bouton "Réessayer" pour recharger
- ✅ Lien vers page de diagnostic

---

### 2. **Script de Diagnostic Complet**

**Fichier:** `admin-panel-v2/test-api-status.php` (nouveau)

**Tests effectués:**
1. ✅ Vérification existence `api/products.php`
2. ✅ Chargement `bootstrap.php` sans erreur
3. ✅ Connexion MySQL + test tables
4. ✅ Validation `menu.json` (structure + données)
5. ✅ Permissions fichiers/dossiers
6. ✅ Environnement PHP (version, extensions, limites)
7. ✅ Exécution directe de l'API avec sortie JSON

---

## 🚀 Déploiement sur o2switch

### Étape 1: Pull les changements

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
```

**Commits ajoutés:**
- `57eabc5` - fix: Amélioration gestion erreurs API + script diagnostic

---

### Étape 2: Ouvrir le diagnostic

**URL:** https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-api-status.php

**Interprétation des résultats:**

#### ✅ Tout est OK
```
✅ Fichier existe: /home/.../api/products.php
✅ bootstrap.php chargé sans erreur
✅ Connexion MySQL réussie
✅ Table 'restaurants': 2 enregistrements
✅ Table 'categories': 15 enregistrements
✅ Table 'products': 87 enregistrements
✅ menu.json valide: 12 catégories
✅ API exécutée sans crash PHP
✅ Réponse JSON valide
```
→ **L'API fonctionne correctement** ✅

---

#### ❌ Erreur MySQL

```
❌ Erreur MySQL: SQLSTATE[HY000] [2002] Connection refused
```

**Solutions:**
1. Vérifier que MySQL est démarré:
   ```bash
   systemctl status mysql
   # ou
   service mysql status
   ```

2. Vérifier les identifiants dans `database/config.php`:
   ```php
   'host' => 'localhost',
   'dbname' => 'zajr1824_marvelous',
   'username' => 'zajr1824_marvelous',
   'password' => 'Mariagor6!',
   ```

3. Tester connexion manuellement:
   ```bash
   mysql -u zajr1824_marvelous -p zajr1824_marvelous
   # Entrer le mot de passe
   ```

---

#### ❌ Erreur Permissions

```
❌ /home/.../config NOT writable
❌ /home/.../admin-panel-v2/data NOT writable
```

**Solution:**
```bash
chmod 755 config database admin-panel-v2/data
chmod 644 config/*.json
chmod 644 admin-panel-v2/data/*.json
```

---

#### ❌ API retourne HTML au lieu de JSON

```
❌ Réponse n'est pas du JSON valide
<!DOCTYPE html>
<html>
<head><title>500 Internal Server Error</title></head>
...
```

**Causes possibles:**
1. **Erreur PHP fatale** → Consulter logs:
   ```bash
   tail -f ~/logs/error.log
   # ou
   tail -f /var/log/apache2/error.log
   ```

2. **bootstrap.php échoue** → Vérifier les require_once
3. **Extension PHP manquante** → Installer `php-mysql`:
   ```bash
   apt-get install php-mysql
   # ou
   yum install php-mysql
   ```

---

### Étape 3: Tester l'admin

1. Ouvrir l'admin: https://marvelous.mon-agenceweb.fr/admin-panel-v2/
2. Se connecter
3. Aller dans "Produits" (sidebar)

**Résultat attendu:**
- ✅ Liste des produits s'affiche
- ✅ Catégories visibles
- ✅ Too Good To Go fonctionne

**Si erreur affichée:**
- ⚠️ Message d'erreur clair (ex: "Impossible de charger les produits - Code: 503")
- 🔄 Bouton "Réessayer"
- 🔍 Lien vers diagnostic

---

## 🔍 Diagnostic Approfondi

### Tester l'API en ligne de commande

```bash
cd ~/Marvelous.mon-agenceweb.fr/admin-panel-v2
php -r "
\$_GET['action'] = 'list';
include 'api/products.php';
"
```

**Résultat attendu:** JSON valide
```json
{"success":true,"products":[...]}
```

**Si erreur PHP:**
```
PHP Fatal error: ...
```
→ Corriger l'erreur indiquée

---

### Vérifier logs Apache/PHP

```bash
# Logs Apache
tail -100 ~/logs/error.log

# Logs PHP (o2switch)
tail -100 ~/logs/php_errors.log

# Temps réel
tail -f ~/logs/error.log
```

**Erreurs courantes:**
- `Fatal error: require_once(): Failed opening required` → Chemin incorrect
- `SQLSTATE[HY000] [2002]` → MySQL non accessible
- `Call to undefined function` → Extension PHP manquante

---

### Tester depuis le navigateur (DevTools)

1. Ouvrir DevTools (F12)
2. Onglet "Network"
3. Recharger la page admin
4. Chercher `products.php?action=list`

**Analyser la réponse:**
- **Status 200 + JSON** → ✅ OK
- **Status 404** → Fichier introuvable (vérifier chemin)
- **Status 500/503** → Erreur serveur (consulter logs)
- **Status 403** → Problème permissions ou CSRF

**Voir la réponse brute:**
- Clic droit sur la requête → "Copy as cURL"
- Exécuter dans le terminal pour voir la vraie réponse

---

## 🐛 Problèmes Connus

### 1. Session expire trop vite
**Symptôme:** Erreur CSRF ou déconnexion fréquente

**Solution:**
```php
// bootstrap.php (déjà corrigé)
ini_set('session.gc_maxlifetime', 86400); // 24h
```

---

### 2. Cache navigateur obsolète
**Symptôme:** Anciennes erreurs persistent après correction

**Solution:**
1. Vider cache navigateur (Ctrl+Shift+Delete)
2. Hard refresh (Ctrl+F5)
3. Ou désactiver cache dans DevTools

---

### 3. .htaccess bloque l'API
**Symptôme:** 403 Forbidden sur toutes les requêtes API

**Solution:**
```bash
# Vérifier .htaccess
cat admin-panel-v2/.htaccess

# Si trop restrictif, commenter les règles problématiques
```

---

## 📞 Support

Si le problème persiste après avoir suivi ce guide:

1. **Envoyer les résultats du diagnostic:**
   ```bash
   curl https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-api-status.php > diagnostic.html
   ```

2. **Envoyer les derniers logs:**
   ```bash
   tail -100 ~/logs/error.log > error.log
   tail -100 ~/logs/php_errors.log > php_errors.log
   ```

3. **Tester l'API en CLI:**
   ```bash
   cd ~/Marvelous.mon-agenceweb.fr/admin-panel-v2
   php api/products.php 2>&1 > api_output.txt
   ```

Envoyer ces 3 fichiers pour analyse approfondie.

---

## ✅ Checklist de Vérification

Avant de déclarer le problème résolu, vérifier:

- [ ] `test-api-status.php` affiche tout en vert
- [ ] Admin charge la liste des produits
- [ ] Peut basculer disponibilité d'un produit
- [ ] Too Good To Go fonctionne
- [ ] Aucune erreur JavaScript dans la console
- [ ] Notifications de commandes fonctionnent
- [ ] Site web charge le menu correctement

---

**Dernière mise à jour:** 2026-01-09
**Commit:** `57eabc5`
