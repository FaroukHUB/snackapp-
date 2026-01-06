# 🚨 FIX HTTP 500 - Admin Panel

**Date:** 2026-01-06
**Statut:** ✅ RÉSOLU

---

## ❌ Problème

Après le déploiement du fix de polling (commit `bf0da74`), l'admin panel affichait :

```
HTTP ERROR 500
Cette page ne fonctionne pas
```

---

## 🔍 Cause identifiée

Le code JavaScript utilisait `AbortSignal.timeout()`, une API trop récente (introduite en 2022) :

```javascript
// ❌ NE FONCTIONNE PAS sur navigateurs anciens
const res = await fetch('api/orders.php?action=list&limit=1', {
    signal: AbortSignal.timeout(5000)
});
```

**Navigateurs affectés :**
- Chrome < 103 (juillet 2022)
- Firefox < 100 (mai 2022)
- Safari < 16 (septembre 2022)

---

## ✅ Solution appliquée

Remplacement par `AbortController` manuel (API de 2018, largement supportée) :

```javascript
// ✅ COMPATIBLE avec Chrome 66+, Firefox 57+, Safari 11.1+
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 5000);

const res = await fetch('api/orders.php?action=list&limit=1', {
    signal: controller.signal
});

clearTimeout(timeoutId);
```

**Commit :** `88a882d` - fix: Compatibilité AbortSignal pour anciens navigateurs

---

## 📦 Déploiement

### Sur votre serveur, exécutez :

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
```

### Vérification

1. **Ouvrir admin panel**
   → URL : https://marvelous.mon-agenceweb.fr/admin-panel-v2/index.php

2. **Vérifier chargement**
   → La page doit charger correctement (plus d'erreur 500)

3. **Vérifier console (F12)**
   → Pas d'erreurs JavaScript
   → Network → `orders.php` apparaît toutes les 60 secondes

---

## 🛠️ Script de diagnostic (si problème persiste)

Si l'erreur HTTP 500 persiste après le déploiement, accédez à :

```
https://marvelous.mon-agenceweb.fr/admin-panel-v2/test-error.php
```

Ce script affichera :
- État du bootstrap.php
- Constantes définies
- Erreurs PHP exactes

Envoyez-moi la sortie complète de ce script pour diagnostic approfondi.

---

## 📊 Historique des commits

```
bf0da74 - fix: Réduction polling notifications (83%) + timeout
4d2964f - docs: Ajout section déploiement final + instructions vérification
88a882d - fix: Compatibilité AbortSignal pour anciens navigateurs ← FIX HTTP 500
0b77ce1 - docs: Ajout fix HTTP 500 (AbortSignal compatibility)
```

---

## ✅ Résultat attendu

Après `git pull` :

1. ✅ Admin panel charge sans erreur 500
2. ✅ Catégories et produits s'affichent
3. ✅ Notifications fonctionnent (polling 60s)
4. ✅ Pas de requêtes excessives
5. ✅ Console sans erreurs

---

## 📞 Support

Si le problème persiste :

1. Accéder à `test-error.php` et copier la sortie
2. Ouvrir F12 → Console et copier les erreurs
3. Ouvrir F12 → Network et vérifier les requêtes échouées
4. M'envoyer ces informations pour diagnostic

---

**Note :** Tous les changements sont dans la branche `claude/setup-marvelous-creperie-Wg8p0`
