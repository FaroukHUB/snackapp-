# 📖 Guide Complet - Le Marvelous Crêperie

Ce guide explique comment modifier tous les aspects du site web de Le Marvelous, même sans utiliser l'admin.

---

## 📁 Structure du Projet

```
le-marvelous/
├── admin-panel-v2/          # Panel d'administration
│   ├── api/                 # API backend
│   ├── products-manager.php # Gestion produits/menu
│   ├── config.php          # Configuration backend
│   └── sync-menu.php       # Synchronisation menu
├── template-v2/            # Site public (frontend)
│   ├── css/
│   │   └── style.css       # ⭐ STYLES PRINCIPAUX
│   ├── js/
│   │   ├── config.js       # Configuration frontend
│   │   ├── products.js     # Logique produits/panier
│   │   └── cart.js         # Gestion du panier
│   ├── index.html          # Page d'accueil
│   └── cart.html           # Page panier
└── config/
    ├── menu.json           # ⭐ MENU PRINCIPAL
    ├── restaurant.json     # Infos restaurant
    └── menu.runtime.json   # Modifications admin (auto)
```

---

## 🎨 Modifier les Couleurs

### Fichier : `template-v2/css/style.css`

**Lignes 6-49** : Variables CSS pour toutes les couleurs

```css
:root {
    /* Couleur principale du restaurant */
    --primary: #e63946;        /* Rouge principal */
    --primary-dark: #c1121f;   /* Rouge foncé */
    --primary-light: #ffeaec;  /* Rouge clair */

    /* Couleurs neutres */
    --white: #ffffff;
    --gray-50: #f9fafb;        /* Fond de page */
    --gray-100: #f3f4f6;
    --gray-800: #1f2937;       /* Texte principal */
    --gray-900: #111827;       /* Texte très foncé */

    /* Couleurs sémantiques */
    --success: #10b981;        /* Vert (titres catégories) */
    --warning: #f59e0b;        /* Orange */
    --error: #ef4444;          /* Rouge erreur */
    --info: #3b82f6;           /* Bleu */
}
```

**Pour changer la couleur principale du site :**
1. Modifiez `--primary` (ligne 8)
2. Modifiez `--primary-dark` pour une version plus foncée
3. Modifiez `--primary-light` pour une version plus claire

**Exemples de couleurs populaires :**
- Vert : `#10b981`
- Bleu : `#3b82f6`
- Violet : `#8b5cf6`
- Orange : `#f59e0b`

---

## 📝 Modifier le Menu et les Produits

### Fichier : `config/menu.json`

C'est le fichier principal du menu. Structure :

```json
{
    "menu": {
        "categories": [
            {
                "id": "crepes-salees-signature",
                "name": "Crêpes Salées Signature",
                "description": "Nos créations maison",
                "items": [
                    {
                        "id": "la-marvelous",
                        "name": "La Marvelous",
                        "description": "Notre signature!",
                        "priceSolo": 850,
                        "priceMenu": 1200,
                        "status": "available",
                        "badge": "Signature ⭐",
                        "image": "images/uploads/marvelous.jpg"
                    }
                ]
            }
        ]
    }
}
```

**Ajouter un nouveau produit :**
1. Trouvez la catégorie dans `menu.json`
2. Ajoutez un objet dans `items` :

```json
{
    "id": "mon-produit",
    "name": "Mon Nouveau Produit",
    "description": "Description du produit",
    "priceSolo": 500,
    "status": "available"
}
```

**Prix en centimes** : 500 = 5.00 DA, 850 = 8.50 DA

---

## 🏪 Informations du Restaurant

### Fichier : `config/restaurant.json`

```json
{
    "name": "Le Marvelous",
    "slogan": "L'excellence de la crêperie algéroise",
    "phone": "+213 123 456 789",
    "address": "Alger, Algérie",
    "hours": {
        "monday": "11:00 - 23:00",
        "tuesday": "11:00 - 23:00",
        ...
    },
    "social": {
        "facebook": "https://facebook.com/lemarvelous",
        "instagram": "https://instagram.com/lemarvelous"
    }
}
```

---

## 🎯 Modifier les Titres et Textes

### Page d'accueil : `template-v2/index.html`

**Titre du site (ligne ~20-30) :**
```html
<title>Le Marvelous - Crêperie Algéroise</title>
```

**Section Hero (ligne ~100-120) :**
```html
<div class="hero-content">
    <h1>Le Marvelous</h1>
    <p class="hero-subtitle">L'excellence de la crêperie algéroise</p>
</div>
```

**Section catégories (ligne ~350) :**
```html
<div class="category-icons-section">
    <div class="category-icons-container">
        <!-- Icônes des catégories -->
    </div>
</div>
```

---

## 🎨 Personnalisation Avancée CSS

### Modifier le fond de la page

**Fichier : `template-v2/css/style.css` (ligne 154)**

```css
body {
    background-color: var(--gray-50);  /* Blanc cassé */
}
```

Options :
- Blanc pur : `#ffffff`
- Gris clair : `#f9fafb`
- Noir : `#000000`

### Titres de catégories en couleur

**Fichier : `template-v2/css/style.css` (ligne 666-679)**

```css
.product-section h2 {
    color: var(--success);  /* Vert actuel */
}

.product-section h2 i {
    color: var(--success);  /* Icône verte */
}
```

### Cartes produits

**Fichier : `template-v2/css/style.css` (ligne 690+)**

```css
.product-card {
    background: var(--white);
    border-radius: var(--border-radius);  /* 12px */
    box-shadow: var(--shadow);
}
```

### Boutons

**Fichier : `template-v2/css/style.css` (recherchez `.btn-primary`)**

```css
.btn-primary {
    background: var(--primary);
    color: white;
}
```

---

## 📱 Modifier les Modals de Personnalisation

### Ajouter un modal pour un produit

**Exemple : Crêpe Kids Salée**

**1. Dans `menu.json`, ajoutez au produit :**

```json
{
    "id": "crepe-kids-salee",
    "name": "Crêpe Kids Salée",
    "hasKidsOptions": true,
    "kidsOptions": {
        "crepeTypes": [
            {"id": "crepe-fumee", "name": "Crêpe Fumée", "price": 0},
            {"id": "crepe-poulet", "name": "Crêpe Poulet", "price": 0}
        ],
        "sauces": [
            {"id": "mayonnaise", "name": "Mayonnaise", "price": 0},
            {"id": "ketchup", "name": "Ketchup", "price": 0}
        ]
    }
}
```

**2. Le code JS dans `js/products.js` gère automatiquement l'affichage**

---

## 🔧 Configuration Technique

### Délai avant fermeture automatique

**Fichier : `config/le-marvelous.config.js`**

```javascript
const CONFIG = {
    restaurantId: 'le-marvelous',
    delivery: {
        enabled: false
    },
    serviceHours: {
        opening: '11:00',
        closing: '23:00'
    }
};
```

---

## 🚀 Déploiement

### Déployer un fichier modifié

**Après modification locale, utilisez wget sur le serveur :**

```bash
cd /home/zajr1824/Marvelous.mon-agenceweb.fr

# Déployer le CSS
wget -O template-v2/css/style.css "https://raw.githubusercontent.com/VOTRE_USER/VOTRE_REPO/BRANCHE/deployments/le-marvelous/template-v2/css/style.css?t=$(date +%s)"

# Déployer le menu
wget -O config/menu.json "https://raw.githubusercontent.com/VOTRE_USER/VOTRE_REPO/BRANCHE/deployments/le-marvelous/config/menu.json?t=$(date +%s)"

# Déployer index.html
wget -O template-v2/index.html "https://raw.githubusercontent.com/VOTRE_USER/VOTRE_REPO/BRANCHE/deployments/le-marvelous/template-v2/index.html?t=$(date +%s)"
```

**Note :** Le `?t=$(date +%s)` ajoute un timestamp pour éviter le cache

---

## 🛠️ Admin Panel

### Fichiers importants

**`admin-panel-v2/products-manager.php`**
- Interface de gestion des produits
- Ligne 650+ : Modal d'édition de produit
- Ligne 702 : Bouton supprimer
- Ligne 690-695 : Sélecteur de statut

**`admin-panel-v2/api/products.php`**
- API backend pour gérer les produits
- Ligne 544-583 : Suppression de produit
- Ligne 519-542 : Changement de statut
- Ligne 464-517 : Modification de produit

**`admin-panel-v2/sync-menu.php`**
- Synchronise les modifications admin vers le site public
- Filtre les produits supprimés
- Met à jour les statuts et prix

### Comment ça fonctionne

1. Admin modifie un produit → Sauvegardé dans `menu.runtime.json`
2. `sync-menu.php` est appelé automatiquement
3. Les changements sont appliqués à `menu.json`
4. Le site public affiche les nouveautés

---

## 🎨 Changer le Logo

**Fichier : `template-v2/index.html` (ligne ~90)**

```html
<div class="header">
    <div class="logo">
        <img src="images/logo.png" alt="Le Marvelous">
    </div>
</div>
```

Remplacez `images/logo.png` par votre logo

---

## 🖼️ Images des Produits

### Ajouter une image à un produit

**Via l'admin :** Utilisez le bouton "Photo du produit" dans le modal d'édition

**Manuellement :**
1. Uploadez l'image dans `images/uploads/`
2. Dans `menu.json`, ajoutez :

```json
{
    "id": "mon-produit",
    "name": "Mon Produit",
    "image": "images/uploads/mon-image.jpg"
}
```

---

## 🔍 Recherche et Filtres

**Fichier : `template-v2/js/products.js` (ligne 1420+)**

La fonction `filterProducts()` gère la recherche. Elle cherche dans :
- Noms de produits
- Descriptions

---

## 📊 Prix et Devises

**Format des prix :** En centimes (DA)
- 500 = 5.00 DA
- 1200 = 12.00 DA
- 850 = 8.50 DA

**Modifier le symbole de devise :**

**Fichier : `template-v2/js/config.js`**

```javascript
formatPrice(price) {
    return `${(price / 100).toFixed(2)} DA`;  // Changez "DA" ici
}
```

---

## 🐛 Debugging

### Logs de synchronisation

Les logs sont dans `/dev/null` actuellement (désactivés).

Pour activer les logs, modifiez la config PHP pour écrire dans un fichier :

```bash
# Dans php.ini ou .htaccess
error_log = /var/log/php-errors.log
```

Ensuite, consultez les logs :

```bash
tail -f /var/log/php-errors.log
```

Vous verrez :
```
[SYNC] Début synchronisation menu.json
[SYNC] Produits avec statuts: 15
[SYNC] Produits supprimés: ["crepe-kids"]
[SYNC] Suppression produit: crepe-kids de catégorie
```

---

## 🎯 Checklist Modification Rapide

### Changer la couleur principale
1. ✅ Ouvrir `template-v2/css/style.css`
2. ✅ Ligne 8 : modifier `--primary: #VOTRE_COULEUR;`
3. ✅ Déployer le fichier CSS

### Ajouter un produit
1. ✅ Ouvrir `config/menu.json`
2. ✅ Trouver la bonne catégorie
3. ✅ Ajouter l'objet produit dans `items`
4. ✅ Déployer le fichier menu.json

### Changer les textes
1. ✅ Ouvrir `template-v2/index.html`
2. ✅ Rechercher le texte à modifier
3. ✅ Remplacer
4. ✅ Déployer index.html

---

## 🆘 Problèmes Fréquents

### Les modifications n'apparaissent pas
**Solution :** Videz le cache du navigateur (Ctrl+Shift+R)

### Les produits supprimés réapparaissent
**Solution :** Vérifiez que `sync-menu.php` est bien déployé avec le code de filtrage des produits supprimés

### Les changements de statut ne fonctionnent pas
**Solution :** Appelez manuellement la synchronisation :
```bash
curl "https://marvelous.mon-agenceweb.fr/admin-panel-v2/sync-menu.php?sync=1"
```

---

## 📞 Support

Pour toute question technique, consultez :
- GitHub du projet
- Documentation Claude Code
- Ce guide

---

**Dernière mise à jour :** Décembre 2024
**Version :** 2.0
