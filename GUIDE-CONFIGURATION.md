# Guide SnackApp - Configuration & Multi-Instance

---

## Table des matières

1. [Configuration des Sons](#1-configuration-des-sons)
2. [Ajouter une Nouvelle Instance](#2-ajouter-une-nouvelle-instance)
3. [Structure des Fichiers](#3-structure-des-fichiers)
4. [Personnalisation Rapide](#4-personnalisation-rapide)
5. [Points Importants](#5-points-importants)

---

## 1. Configuration des Sons

### Fichier : `/snackup/admin/notification-sound.js`

#### Son MP3 principal (ligne 16)

```javascript
this.audioFile = new Audio('assets/sounds/ateliersounds.mp3');
```

- **Emplacement des fichiers** : `/snackup/admin/assets/sounds/`
- **Pour changer** : remplace le nom du fichier à la ligne 16
- **Formats supportés** : MP3, WAV, OGG

#### Volume (ligne 18)

```javascript
this.audioFile.volume = 0.9;
```

- Valeur entre `0` (muet) et `1` (volume max)
- Exemple : `0.5` = 50% du volume

#### Bip automatique (lignes 44-45)

Ce son est généré par le navigateur (WebAudio), pas depuis un fichier.

```javascript
osc1.frequency.value = 659.25; // Note E5
osc2.frequency.value = 880.0;  // Note A5
```

**Pour désactiver le bip** : commenter la ligne 87
```javascript
// this.generateBeep();
```

**Pour modifier les fréquences** : changer les valeurs Hz aux lignes 44-45

### Tableau des fréquences musicales

| Note | Fréquence (Hz) | Description |
|------|----------------|-------------|
| C4 (Do) | 261.63 | Grave |
| E4 (Mi) | 329.63 | |
| G4 (Sol) | 392.00 | |
| A4 (La) | 440.00 | Référence standard |
| C5 (Do) | 523.25 | Medium |
| E5 (Mi) | **659.25** | ← Actuel (osc1) |
| G5 (Sol) | 783.99 | |
| A5 (La) | **880.00** | ← Actuel (osc2) |
| C6 (Do) | 1046.50 | Aigu |

**Site pour tester les fréquences** : https://www.szynalski.com/tone-generator/

### Exemples de combinaisons

```javascript
// Son doux (grave)
osc1.frequency.value = 440.0;   // A4
osc2.frequency.value = 523.25;  // C5

// Son "ding" classique
osc1.frequency.value = 523.25;  // C5
osc2.frequency.value = 659.25;  // E5

// Son d'alerte (aigu)
osc1.frequency.value = 880.0;   // A5
osc2.frequency.value = 1046.50; // C6
```

---

## 2. Ajouter une Nouvelle Instance

### Étape 1 : Dupliquer le dossier

```
/snackup/  → Instance existante (ex: L'Atelier Pizza)
/snackup2/ → Nouvelle instance (ex: Burger Factory)
```

Copier tout le contenu de `/snackup/` vers `/snackup2/`

### Étape 2 : Base de données

#### Option A - Même base, données séparées par restaurant_id

Les tables utilisent déjà un champ `restaurant_id` pour identifier le restaurant.

1. Ajouter le nouveau restaurant dans la table `restaurants`
2. Récupérer l'ID généré
3. Configurer cet ID dans les fichiers de config

#### Option B - Base séparée (recommandé)

1. Créer une nouvelle base de données : `snackapp_instance2`
2. Importer le schéma SQL depuis `/snackup/database/migrations/`
3. Configurer la connexion dans `/snackup2/config/database.php`

### Étape 3 : Configuration Backend

#### Fichier : `/snackup2/config/database.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'snackapp_instance2');  // Nom de la base
define('DB_USER', 'root');                 // Utilisateur
define('DB_PASS', 'motdepasse');           // Mot de passe
```

#### Fichier : `/snackup2/config/menu.php`

```php
$restaurantId = 2;  // ID unique du restaurant
```

Modifier aussi :
- Les catégories
- Les produits
- Les suppléments
- Les formules

#### Fichier : `/snackup2/config/settings.json`

```json
{
  "restaurant": {
    "name": "Burger Factory",
    "primaryColor": "#f59e0b",
    "phone": "0320000000"
  },
  "delivery": {
    "enabled": true,
    "zones": [...]
  },
  "payment": {
    "cash": true,
    "card": true,
    "online": false
  }
}
```

### Étape 4 : Configuration Frontend

#### Images à remplacer

Dans `/snackup2/frontend/images/` :
- Logo du restaurant
- Image hero (bannière)
- Favicon
- Images des produits (dans `/products/`)

#### Sons à personnaliser

Dans `/snackup2/admin/assets/sounds/` :
- Remplacer le fichier MP3 de notification
- Modifier la ligne 16 de `notification-sound.js`

### Étape 5 : Admin

#### Créer un compte admin

Accéder à la base de données et ajouter un utilisateur dans `admin_users` :

```sql
INSERT INTO admin_users (username, password_hash, role, restaurant_id)
VALUES ('admin', 'hash_du_mot_de_passe', 'owner', 2);
```

Ou utiliser l'interface admin existante si disponible.

#### URLs d'accès

| Interface | URL |
|-----------|-----|
| Admin | `https://tondomaine.com/snackup2/admin/` |
| Site client | `https://tondomaine.com/snackup2/frontend/` |

### Étape 6 : Domaines (optionnel)

#### Sous-domaines

```apache
atelierpizza.tondomaine.com → /snackup/frontend/
burgerfactory.tondomaine.com → /snackup2/frontend/
```

#### Domaines séparés

```apache
atelierpizza.fr → /snackup/frontend/
burgerfactory.fr → /snackup2/frontend/
```

Configuration dans le `.htaccess` ou la config du serveur web (Apache/Nginx).

---

## 3. Structure des Fichiers

```
/snackup/
│
├── admin/                      # Interface administration
│   ├── index.php               # Dashboard principal
│   ├── login.php               # Page de connexion
│   ├── products-manager.php    # Gestion du menu
│   ├── promo-manager.php       # Gestion des codes promo
│   ├── livreurs-manager.php    # Gestion des livreurs
│   ├── settings-manager.php    # Paramètres avancés
│   ├── clients.php             # Liste des clients
│   ├── api/                    # API admin
│   │   ├── orders.php          # API commandes
│   │   ├── products.php        # API produits
│   │   └── settings.php        # API paramètres
│   ├── assets/
│   │   └── sounds/             # Sons de notification
│   │       └── ateliersounds.mp3
│   └── notification-sound.js   # Gestion audio
│
├── backend/
│   ├── api/                    # API publique
│   │   ├── menu.php            # Récupérer le menu
│   │   ├── order.php           # Passer commande
│   │   └── settings.php        # Récupérer paramètres
│   └── repositories/           # Accès base de données
│       ├── OrderRepository.php
│       ├── MenuRepository.php
│       └── CustomerRepository.php
│
├── config/
│   ├── database.php            # Connexion BDD
│   ├── menu.php                # Menu du restaurant
│   └── settings.json           # Paramètres généraux
│
├── frontend/                   # Site client
│   ├── index.html              # Page d'accueil / Menu
│   ├── cart.html               # Panier
│   ├── fidelite.html           # Programme fidélité
│   ├── loyalty-card.php        # Carte fidélité client
│   ├── css/
│   │   ├── style.css           # Styles principaux
│   │   ├── home.css            # Styles page d'accueil
│   │   └── pages.css           # Styles pages annexes
│   ├── js/
│   │   ├── config.js           # Configuration frontend
│   │   ├── cart.js             # Gestion du panier
│   │   ├── products.js         # Affichage produits
│   │   └── checkout.js         # Validation commande
│   └── images/
│       ├── logo.png            # Logo
│       ├── hero.jpg            # Bannière
│       └── products/           # Images des produits
│
└── database/
    └── migrations/             # Scripts SQL
        ├── create_tables.sql
        └── create_upsell_rules.sql
```

---

## 4. Personnalisation Rapide

### Tableau de référence

| Élément | Fichier | Clé/Ligne à modifier |
|---------|---------|----------------------|
| Nom du restaurant | `config/settings.json` | `restaurant.name` |
| Couleur principale | `config/settings.json` | `restaurant.primaryColor` |
| Téléphone | `config/settings.json` | `restaurant.phone` |
| Logo | `frontend/images/` | Remplacer le fichier |
| Favicon | `frontend/images/` | `favicon-32x32.png` |
| Menu complet | `config/menu.php` | Catégories et produits |
| Horaires | Admin → Réglages | Section "Horaires" |
| Son notification | `admin/assets/sounds/` | + ligne 16 de `notification-sound.js` |
| Volume son | `notification-sound.js` | Ligne 18 : `volume = 0.9` |
| Zones de livraison | Admin → Paramètres → Livraison | Ajouter/modifier villes |
| Frais de livraison | Admin → Paramètres → Livraison | Par ville |
| Moyens de paiement | Admin → Paramètres → Paiements | Activer/désactiver |
| Codes promo | Admin → Codes Promo | Créer/modifier |

### Changements visuels (CSS)

Les variables CSS sont dans `/frontend/css/style.css` :

```css
:root {
  --primary-color: #e63946;    /* Couleur principale */
  --secondary-color: #1d3557;  /* Couleur secondaire */
  --bg-color: #0f0f1a;         /* Fond */
  --text-color: #ffffff;       /* Texte */
}
```

---

## 5. Points Importants

### Isolation des instances

- Chaque instance est **totalement indépendante**
- Sa propre base de données (ou restaurant_id séparé)
- Ses propres administrateurs
- Son propre menu et paramètres

### Le restaurant_id

Présent dans presque toutes les tables :
- `orders.restaurant_id`
- `menu_items.restaurant_id`
- `customers.restaurant_id`
- etc.

Permet de filtrer les données par restaurant.

### Stockage des images

- **Produits** : `/frontend/images/products/`
- **Logo/Hero** : `/frontend/images/`
- **Admin** : `/admin/assets/`

### Sauvegardes recommandées

À sauvegarder régulièrement :
1. **Base de données** : export SQL complet
2. **Dossier `/snackup/`** : tout le code et images
3. **Fichiers de config** : `database.php`, `settings.json`, `menu.php`

### Mises à jour du code

Quand tu mets à jour le code :
1. Faire une sauvegarde d'abord
2. Mettre à jour chaque instance séparément
3. Tester chaque instance après mise à jour
4. Ne pas écraser les fichiers de config personnalisés

### Debugging

- Logs PHP : vérifier les logs du serveur
- Console navigateur : F12 → Console
- API : tester avec l'onglet Network (F12)

---

## Support

Pour toute question ou problème, vérifier :
1. Les logs serveur
2. La console navigateur
3. Les permissions des fichiers/dossiers
4. La connexion à la base de données

---

*Document créé pour SnackApp - Dernière mise à jour : Janvier 2026*
