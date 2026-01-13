# 🚀 SNACKUP - Architecture Produit/Instances

Ce projet contient **Snackup**, un produit SaaS pour restaurants, ainsi que les instances clientes.

## 📂 Structure du Projet

```
snackapp-/
├── snackup/                    # ✨ PRODUIT GÉNÉRIQUE (ne pas modifier pour un client)
│   ├── frontend/               # Templates front réutilisables
│   ├── backend/                # API et logique métier
│   ├── admin/                  # Panel d'administration
│   └── README.md               # Documentation produit
│
├── instances/                  # 🏪 INSTANCES CLIENTS
│   ├── marvelous/              # Instance Le Marvelous (production)
│   │   ├── config.js           # Config frontend
│   │   ├── menu.json           # Menu runtime
│   │   ├── backend-config.php  # Config DB
│   │   └── assets/             # Logo, images
│   │
│   └── demo/                   # Template vide pour nouvelles instances
│       ├── config.template.js
│       ├── backend-config.template.php
│       └── README.md
│
└── (Dossiers existants - MARVELOUS EN PRODUCTION)
    ├── template-v2/            # ⚠️ NE PAS TOUCHER - Marvelous actuel
    ├── admin-panel-v2/         # ⚠️ NE PAS TOUCHER - Admin Marvelous
    ├── database/               # ⚠️ NE PAS TOUCHER - DB Marvelous
    ├── config/                 # ⚠️ NE PAS TOUCHER - Config Marvelous
    └── images/                 # ⚠️ NE PAS TOUCHER - Images Marvelous
```

---

## 🎯 Logique Produit vs Instance

### ✅ PRODUIT (snackup/)
**Code générique, réutilisable pour TOUS les restaurants**

Contient :
- Structure HTML/CSS/JS générique
- Logique métier (panier, commandes, fidélité, précommande)
- API backend (CRUD, gestion commandes)
- Admin complet (gestion produits, catégories, options, horaires)
- Structure de base de données

**⚠️ RÈGLE ABSOLUE** : Ce dossier ne doit contenir **AUCUNE** donnée spécifique (nom, menu, logo, etc.)

### 🏪 INSTANCE (instances/nom/)
**Configuration et données spécifiques à UN restaurant**

Contient :
- Nom du restaurant
- Logo, couleurs, branding
- Menu (catégories, produits)
- Horaires d'ouverture
- Adresse, téléphone, réseaux sociaux
- Configuration base de données

---

## 🆕 Créer une Nouvelle Instance

### Étape 1 : Copier le template
```bash
cp -r instances/demo instances/mon-nouveau-restaurant
cd instances/mon-nouveau-restaurant
```

### Étape 2 : Configurer le frontend
Éditer `config.template.js` :
```javascript
{
    "id": "mon-restaurant",
    "name": "Mon Restaurant",
    "branding": {
        "primaryColor": "#e63946",
        "logo": "../instances/mon-restaurant/assets/logo.png"
    },
    "contact": {
        "phone": "+213 XX XX XX XX XX",
        "address": { ... }
    },
    "hours": { ... }
}
```

### Étape 3 : Configurer le backend
Éditer `backend-config.template.php` :
```php
[
    'database' => [
        'host' => 'localhost',
        'dbname' => 'mon_restaurant_db',
        'username' => 'mon_user',
        'password' => 'mon_password'
    ]
]
```

### Étape 4 : Créer la base de données
```bash
# Importer la structure depuis snackup/backend/schema.sql
mysql -u root -p mon_restaurant_db < snackup/backend/schema.sql
```

### Étape 5 : Ajouter le menu via l'admin
- Accéder à `/admin-panel-v2`
- Créer les catégories
- Ajouter les produits
- Configurer les options

---

## ⚠️ RÈGLES IMPORTANTES

### ❌ NE JAMAIS :
- Modifier `snackup/` pour un client spécifique
- Copier une instance existante (Marvelous, etc.) pour créer un nouveau client
- Hardcoder des données dans le produit
- Toucher aux dossiers `template-v2/`, `admin-panel-v2/`, `database/` (Marvelous actuel)

### ✅ TOUJOURS :
- Partir de `instances/demo/` pour créer une nouvelle instance
- Mettre les données spécifiques dans `instances/{nom}/`
- Configurer via l'admin panel
- Garder le produit générique

---

## 📚 Documentation

- **Produit Snackup** : `snackup/README.md`
- **Instance Marvelous** : `instances/marvelous/README.md`
- **Template Demo** : `instances/demo/README.md`

---

## 🔄 Workflow Git

**Branche unique** : `main` (ou `master`)

Tous les changements sont commités sur la même branche :
- Mises à jour du produit → tous les clients en bénéficient
- Changements de config instance → n'affectent que le client concerné

---

## 🚀 Déploiement

Pour déployer une instance :

1. Copier `snackup/` sur le serveur
2. Copier `instances/{nom}/` sur le serveur
3. Pointer les configs vers l'instance voulue
4. Importer la base de données
5. Configurer via l'admin

---

**Snackup** - La solution SaaS pour restaurants
**Version** : 1.0.0
**Architecture** : Produit/Instances
