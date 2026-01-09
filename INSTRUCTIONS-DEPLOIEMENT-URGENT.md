# 🚨 DÉPLOIEMENT URGENT - Fix Erreurs API

**Date:** 2026-01-09
**Branche:** `claude/setup-marvelous-creperie-Wg8p0`
**Problème résolu:** Erreurs 404/503 sur api/products.php + crashes JavaScript

---

## ⚡ Déploiement Rapide (2 minutes)

### Sur votre serveur o2switch:

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
```

---

## 🔍 Vérification Immédiate

### 1. Ouvrir le diagnostic:
**URL:** https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-api-status.php

**Attendu:** Tous les tests en vert ✅

---

### 2. Tester l'admin:
**URL:** https://marvelous.mon-agenceweb.fr/admin-panel-v2/

1. Se connecter
2. Aller dans "Produits" (sidebar)
3. Vérifier que la liste s'affiche

**Si erreur affichée:**
- Message clair avec code d'erreur
- Bouton "Réessayer"
- Lien vers diagnostic

---

## 📦 Changements Déployés

### Commit `57eabc5` - Fix erreurs API

#### Fichiers modifiés:
1. **admin-panel-v2/assets/js/products.js**
   - Vérification response.ok avant parsing JSON
   - Vérification Content-Type
   - Gestion d'erreur robuste
   - Interface d'erreur visuelle

2. **admin-panel-v2/test-api-status.php** (nouveau)
   - Diagnostic complet de l'API
   - Test MySQL + tables
   - Validation menu.json
   - Test direct de l'API

### Commit `5a69314` - Documentation

3. **DIAGNOSTIC-ERRORS.md** (nouveau)
   - Guide complet de diagnostic
   - Solutions pour chaque type d'erreur
   - Checklist de vérification

---

## 🐛 Si le Problème Persiste

### Scénario 1: Test diagnostic échoue

**Ouvrir:** https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-api-status.php

**Si MySQL rouge ❌:**
```bash
# Vérifier MySQL
systemctl status mysql

# Tester connexion
mysql -u zajr1824_marvelous -p zajr1824_marvelous
```

**Si permissions rouge ❌:**
```bash
chmod 755 ~/Marvelous.mon-agenceweb.fr/config
chmod 755 ~/Marvelous.mon-agenceweb.fr/admin-panel-v2/data
```

---

### Scénario 2: Admin affiche toujours erreur

**Console navigateur (F12):**
```
API Error: 503 Service Unavailable
```

**Action:**
```bash
# Voir logs PHP
tail -50 ~/logs/error.log
tail -50 ~/logs/php_errors.log

# Tester API en CLI
cd ~/Marvelous.mon-agenceweb.fr/admin-panel-v2
php api/products.php
```

---

### Scénario 3: Page blanche

**Cause probable:** Erreur PHP fatale

**Action:**
```bash
# Activer affichage erreurs temporairement
echo "ini_set('display_errors', 1);" | cat - admin-panel-v2/index.php > temp && mv temp admin-panel-v2/index.php

# Recharger la page et noter l'erreur
# Puis désactiver:
sed -i "1d" admin-panel-v2/index.php
```

---

## 📊 État Actuel du Système

### ✅ Ce qui fonctionne:
- Admin panel login
- Gestion commandes
- Notifications de commandes
- Système livreurs WhatsApp
- Site web (template-v2)

### ⚠️ Ce qui PEUT ne pas fonctionner:
- Section "Produits" de l'admin (API products.php)
- Basculer disponibilité produits
- Too Good To Go

### 🎯 Objectif de ce déploiement:
Restaurer les fonctionnalités ⚠️ ci-dessus en:
1. Fixant les crashes JavaScript
2. Affichant des erreurs claires
3. Fournissant un outil de diagnostic

---

## 📞 Retour d'Information

### Si ça fonctionne ✅:
Confirmez-moi que:
- [ ] test-api-status.php est tout vert
- [ ] Admin > Produits affiche la liste
- [ ] Aucune erreur JavaScript console

### Si ça ne fonctionne pas ❌:
Envoyez-moi:
1. URL du diagnostic: `test-api-status.php`
2. Screenshot de l'erreur admin
3. Copie de la console (F12 > Console)
4. Dernières lignes logs:
   ```bash
   tail -50 ~/logs/error.log
   ```

---

## 🔄 Prochaines Étapes

Une fois ce fix déployé et vérifié:

1. **Tester toutes les fonctionnalités admin**
   - Produits: liste, disponibilité, TGTG
   - Commandes: notifications, livreurs
   - Stats, clients, loyalty

2. **Optimisations possibles:**
   - Cache API pour réduire charge MySQL
   - Pagination liste produits
   - Lazy loading images

3. **Monitoring:**
   - Logs d'erreur quotidiens
   - Alerte si API > 500ms
   - Dashboard santé système

---

**Temps estimé:** 2 min déploiement + 5 min vérification
**Impact:** Critique (restaure admin produits)
**Risque:** Minimal (améliore seulement gestion erreur)

---

**Besoin d'aide?** Je suis là pour vous accompagner dans le déploiement et le diagnostic ! 🚀
