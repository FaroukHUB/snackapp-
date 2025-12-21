# SnackApp - Plateforme de commande en ligne pour restaurants

## Vue d'ensemble
SnackApp est une solution white-label de commande en ligne pour restaurants. Chaque client a son propre domaine.

## Structure du projet

```
snackapp/
├── template-v2/          # Template client (frontend)
│   ├── index.html        # Page menu principal
│   ├── cart.html         # Panier
│   ├── click-collect.html
│   ├── fidelite.html
│   ├── css/
│   ├── js/
│   └── config/           # Config spécifique client
│
├── admin-panel-v2/       # Panel admin (PHP)
│   ├── index.php
│   ├── api/
│   └── ...
│
├── clients/              # Configs clients
│   ├── _template/        # Template à dupliquer
│   └── le-marvelous/     # Premier client (en cours)
│
├── config/               # Config Fabrik Burger (démo)
│   ├── menu.json
│   └── restaurant.json
│
└── images/               # Images produits
```

## Clients actifs
| Client | Domaine | Status |
|--------|---------|--------|
| Fabrik Burger | fabrikburger.fr | ✅ Actif (démo) |
| Le Marvelous | lemarvelous.fr | 🔄 En cours |

## Déploiement
- Hébergement: o2switch
- Chaque client = 1 domaine séparé
- Voir `clients/_template/README.md` pour ajouter un client

## Stack technique
- Frontend: HTML/CSS/JS vanilla
- Backend: PHP
- Base de données: MySQL (SQLite en option)
- Pas de framework = léger et rapide

## Commandes utiles
```bash
# Pull les derniers changements
git pull origin main

# Déployer un nouveau client
cp -r clients/_template clients/nouveau-client
# Puis remplir client-config.json
```

## Contact
Projet développé par Farouk
