# 🚨 INSTRUCTIONS URGENTES - Correction Admin Panel

**Problème:** Admin panel affiche erreur 400 Bad Request + aucun produit/catégorie visible

**Cause:** 21 produits sans prix (priceSolo manquant) dans menu.json

---

## ✅ SOLUTION (5 minutes)

### Sur votre serveur, exécutez ces commandes :

```bash
# 1. Pull les derniers changements
cd ~/Marvelous.mon-agenceweb.fr
git pull origin claude/setup-marvelous-creperie-Wg8p0

# 2. Corriger les prix manquants
php database/fix-missing-prices.php

# 3. Vérifier que tout est OK
php database/diagnose-menu.php
```

### Résultat attendu :
- ✅ 21 produits corrigés (priceSolo = 0 ajouté)
- ✅ 0 prix manquants dans le diagnostic
- ✅ Admin panel fonctionne et affiche toutes les catégories/produits

---

## 📋 Produits corrigés

Ces produits recevront `priceSolo = 0` car leur prix réel vient des options :

**Avec options spéciales :**
- Pâtisserie (pâtisserieOptions: Brownie, Cookie, Muffin)
- Soda (beverageOptions: Coca, Fanta, Sprite)
- Jus (beverageOptions: Orange, Pomme)
- Jus Frais (beverageOptions: Orange, Carotte, Mixte)
- Smoothie (beverageOptions: Fraise, Mangue, Banane)

**Produits classiques :**
- Sandwichs (Thon, Poulet Fumé, Poulet Mariné)
- Wraps Poulet
- Salade Composée
- Tous les cafés et boissons chaudes
- Eaux minérales
- Cocktails maison
- Menu Enfant (crêpes)

---

## ⚠️ IMPORTANT

Après avoir exécuté le script :
1. **Rechargez l'admin panel** dans votre navigateur (Ctrl+F5)
2. Vous devriez voir toutes vos catégories et produits
3. Le site web fonctionne normalement

---

## 🔍 Si ça ne fonctionne toujours pas

Envoyez-moi le résultat de ces commandes :
```bash
php database/diagnose-menu.php
curl -I https://marvelous.mon-agenceweb.fr/admin-panel-v2/api/products.php
```
