# Installation L'Atelier Pizza Roubaix

## Étape 1: Base de données ✅

Déjà fait! Base de données créée avec 61 produits en 8 catégories.

## Étape 2: Créer un utilisateur admin

Sur le serveur:
```bash
cd /home/zajr1824/atelierpizza.mon-agenceweb.fr
mysql -u zajr1824_atelierpizza -p zajr1824_atelierpizza < instances/atelier-pizza/create_admin.sql
```

**Identifiants par défaut:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Changez le mot de passe dès la première connexion!

## Étape 3: Configurer le backend

Éditer `instances/atelier-pizza/backend-config.php` et remplacer:
```php
'password' => 'VOTRE_MOT_DE_PASSE_ICI',
```
Par le vrai mot de passe MySQL de l'utilisateur `zajr1824_atelierpizza`.

## Étape 4: Configurer l'admin panel

Éditer `snackup/admin/config.php` (ligne 3-5) et remplacer par:
```php
<?php
// Charger la config depuis l'instance
$instanceConfig = require_once __DIR__ . '/../../instances/atelier-pizza/backend-config.php';
```

## Étape 5: Accéder au panel admin

Ouvrir dans le navigateur:
```
https://atelierpizza.mon-agenceweb.fr/snackup/admin/
```

Se connecter avec:
- Username: `admin`
- Password: `admin123`

## Étape 6: Ajouter les photos des produits

Dans le panel admin:
1. Aller dans "Gestion des produits"
2. Pour chaque produit, cliquer sur "Modifier"
3. Uploader une photo (elle sera automatiquement convertie en WebP)

## Étape 7: Configurer Stripe (paiement CB en ligne)

Plus tard, quand tu auras les clés Stripe:

1. Éditer `instances/atelier-pizza/backend-config.php`
2. Remplacer les valeurs `pk_test_...` et `sk_test_...` par tes vraies clés
3. Changer `'mode' => 'test'` en `'mode' => 'live'` pour la production

## Étape 8: Tester le site frontend

Ouvrir:
```
https://atelierpizza.mon-agenceweb.fr/
```

Le site devrait afficher:
- Les 8 catégories
- Les 61 produits avec leurs prix
- Le panier fonctionnel
- Le système de fidélité

## ⚠️ À FAIRE plus tard:

1. **Implémenter le bundle pricing dans cart.js**
   - Actuellement les prix de bundle sont dans la DB mais pas calculés dans le panier
   - Il faut ajouter la logique: "2 pizzas Solo = 13€ au lieu de 15€"

2. **Configurer Stripe**
   - Créer un compte Stripe
   - Ajouter les clés API
   - Tester le paiement CB en ligne

3. **Personnaliser les couleurs/images**
   - Logo du restaurant
   - Photos des produits
   - Bannière d'accueil

4. **Configurer les horaires d'ouverture**
   - Actuellement simplifiés dans la DB
   - À ajuster selon les vrais horaires (coupure déjeuner/dîner)
