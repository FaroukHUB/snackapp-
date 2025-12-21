# 📦 INSTRUCTIONS UPLOAD ADMIN PANEL V2

## 🚀 ÉTAPES D'INSTALLATION

### 1. Upload sur o2switch

1. **Télécharge** le fichier `admin-panel-v2.zip` (30 KB - pas le 18 KB!)
2. **Connexion o2switch** → File Manager (Gestionnaire de fichiers)
3. **Navigation** : `/public_html/snack.mon-agenceweb.fr/`
4. **Upload** : Glisse le ZIP dans le gestionnaire
5. **Extraction** : Clic droit sur le ZIP → Extract (Extraire)
6. **Vérification** : Tu devrais voir le dossier `admin-panel-v2/`

### 2. Vérifier les permissions

**IMPORTANT** : Le dossier `admin-panel-v2/data/` doit être accessible en écriture.

Via File Manager :
- Clic droit sur `admin-panel-v2/data/`
- **Change Permissions** (Modifier les permissions)
- Mettre **755** ou cocher : `Read`, `Write`, `Execute` pour Owner

### 3. Tester l'installation

**Diagnostic automatique** :
```
https://snack.mon-agenceweb.fr/admin-panel-v2/test.php
```

Ce fichier va vérifier :
- ✅ Tous les fichiers présents
- ✅ Permissions du dossier data/
- ✅ Configuration restaurant
- ✅ APIs fonctionnelles

### 4. Accéder à l'admin

**URL** : `https://snack.mon-agenceweb.fr/admin-panel-v2/`

**Mot de passe** : `fabrik2025`

---

## 🐛 PROBLÈMES COURANTS

### ❌ "Les boutons ne fonctionnent pas"

**Causes possibles :**

1. **Fichiers JS manquants**
   - Vérifier que `assets/js/customers.js` et `products.js` existent
   - Re-extraire le ZIP complet (30 KB)

2. **Erreur JavaScript dans la console**
   - Ouvrir F12 (Outils développeur)
   - Onglet "Console"
   - Vérifier les erreurs rouges
   - Me les envoyer si tu ne comprends pas

3. **Dossier data/ non accessible**
   - Permissions incorrectes
   - Lancer `test.php` pour diagnostic

4. **Cache navigateur**
   - Ctrl + F5 (forcer le rechargement)
   - Ou vider le cache

### ❌ "Erreur 403 Forbidden"

**Solution** : Supprimer `.htaccess` s'il existe dans `admin-panel-v2/`

### ❌ "Page blanche"

**Cause** : Erreur PHP

**Solution** :
1. Activer affichage erreurs :
   - Créer fichier `debug.php` dans admin-panel-v2/
   - Contenu :
   ```php
   <?php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   require 'index.php';
   ?>
   ```
2. Accéder à `debug.php` au lieu de `index.php`
3. Me copier l'erreur affichée

---

## 📝 FICHIERS IMPORTANTS

```
admin-panel-v2/
├── index.php          ← Page principale
├── login.php          ← Authentification
├── config.php         ← Configuration (mot de passe ici)
├── test.php           ← Diagnostic (nouveau)
│
├── api/
│   ├── orders.php     ← Gestion commandes
│   ├── customers.php  ← CRM clients
│   ├── products.php   ← Produits + TGTG
│   └── stats.php      ← Statistiques
│
├── assets/js/
│   ├── app.js         ← Core application
│   ├── customers.js   ← Interface clients
│   └── products.js    ← Interface produits
│
└── data/              ← Base de données JSON (doit être writable!)
    ├── orders.json
    ├── customers.json
    ├── tgtg.json
    ├── loyalty_points.json
    └── settings.json
```

---

## 🔧 CHANGEMENT MOT DE PASSE

Éditer `admin-panel-v2/config.php` ligne 8 :

```php
define('ADMIN_PASSWORD', 'TON_NOUVEAU_MOT_DE_PASSE');
```

---

## 💡 ASTUCES

### Vérifier que tout fonctionne :

1. **Login** → Doit afficher page avec Fabrik Burger
2. **Commandes** → 3 commandes de test visibles
3. **Clients** → 4 clients de test visibles
4. **Produits** → Liste par catégories
5. **Stats** → Graphiques affichés
6. **Too Good To Go** → Section verte en haut

### Console navigateur (F12) :

Si ça bug, regarde dans F12 → Console :
- ❌ **Erreurs rouges** : Fichier JS manquant ou erreur code
- ⚠️ **Warnings jaunes** : Pas grave
- ✅ **Rien ou logs bleus** : Tout va bien

---

## 📞 BESOIN D'AIDE ?

Si après avoir :
1. Vérifié `test.php`
2. Forcé le rechargement (Ctrl+F5)
3. Regardé la console F12

Ça ne fonctionne toujours pas → **Envoie-moi** :
- Screenshot de `test.php`
- Screenshot console F12 (onglet Console)
- Le message d'erreur exact

Je corrigerai immédiatement ! 🚀
