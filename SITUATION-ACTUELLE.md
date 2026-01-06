# 🚨 SITUATION ACTUELLE - Migration MySQL

**Date:** 2026-01-06
**Status:** ⚠️ **MIGRATION INCOMPLÈTE - RESTAURATION NÉCESSAIRE**

---

## 🔍 PROBLÈME DÉTECTÉ

Après analyse du diagnostic (`diagnose-menu.php`), la migration MySQL a écrasé des données critiques :

### Données perdues dans menu.json actuel :
- ❌ **21 produits avec prix = 0** (Pâtisserie, Jus, Soda, Café, etc.)
- ❌ **0 options spéciales** (pâtisserieOptions, beverageOptions)
- ❌ **0 formules** (toutes disparues)
- ❌ Badge, customizationNote, requiresChoice

### Cause :
La migration MySQL initiale n'a **PAS migré** tous les produits/prix de l'ancien `menu.json`. Le script `generateMenuJson.php` génère menu.json UNIQUEMENT depuis MySQL, qui est incomplet.

---

## ✅ SOLUTION IMMÉDIATE : RESTAURATION

### Sur le serveur, exécutez :

```bash
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0
bash database/restore-menu.sh
```

Cela va restaurer `menu.json` depuis le backup du **5 janvier 13:43** (47K, le plus récent et complet).

---

## 🎯 ÉTAT ACTUEL DU SYSTÈME

### ✅ Ce qui fonctionne :
- **Admin Panel** : Lit depuis MySQL (OK)
- **Site Web** : Lit depuis menu.json (sera OK après restauration)
- **MySQL** : Contient données partielles (catégories + certains produits)

### ⚠️ Ce qui est désactivé :
- `regenerateMenuJson()` désactivé dans **TOUS** les endpoints API
- Les modifications via l'admin vont dans MySQL mais **NE METTENT PAS À JOUR** menu.json
- Le site web **NE VERRA PAS** les modifications faites dans l'admin

---

## 🤔 DÉCISION À PRENDRE

Vous avez **2 options** :

### Option 1 : MySQL pour NOUVELLES catégories uniquement (RECOMMANDÉ)
- Garder menu.json existant INTACT
- MySQL seulement pour catégories/produits créés APRÈS migration
- Script fusion menu.json + MySQL pour générer le fichier final
- **Avantage** : Pas de perte de données
- **Inconvénient** : Système hybride

### Option 2 : Migrer TOUT vers MySQL
- Créer nouveau script migration qui migre **TOUT** :
  * Tous les produits avec prix corrects
  * Toutes les options (pâtisserieOptions, beverageOptions)
  * Toutes les formules
  * Tous les badges, customizationNote, requiresChoice
- Régénérer menu.json depuis MySQL complet
- **Avantage** : Système 100% MySQL pur
- **Inconvénient** : Risque de perte si migration échoue

---

## 📊 TOKENS RESTANTS

**~76k tokens** restants (suffisant pour implémenter l'option choisie)

---

## 🚀 INSTRUCTIONS DE REPRISE

1. **Restaurer menu.json** : `bash database/restore-menu.sh`
2. **Choisir option** (1 ou 2)
3. **Me le dire** pour que je continue

---

## ⚠️ IMPORTANT

**NE MODIFIEZ PAS** le menu via l'admin tant que vous n'avez pas :
1. Restauré menu.json
2. Choisi et implémenté une des 2 options

Sinon les modifications seront dans MySQL mais invisibles sur le site.
