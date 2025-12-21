# Le Marvelous - Guide de Déploiement

## Fichiers prêts à déployer

Ce dossier contient tout ce qu'il faut pour Le Marvelous.

## Étapes de déploiement

### 1. Modifier le mot de passe MySQL

Ouvre le fichier `database/config.php` et remplace `VOTRE_MOT_DE_PASSE` par le vrai mot de passe MySQL que tu as créé sur o2switch.

### 2. Uploader sur o2switch

1. Compresse tout le contenu de ce dossier en ZIP
2. Va dans le gestionnaire de fichiers cPanel
3. Va dans le dossier `marvelous.mon-agenceweb.fr`
4. Supprime tout ce qu'il y a dedans
5. Upload le ZIP
6. Extrais le ZIP
7. Déplace les fichiers extraits à la racine (pas dans un sous-dossier)

### 3. Tester

- Site public: https://marvelous.mon-agenceweb.fr/
- Admin: https://marvelous.mon-agenceweb.fr/admin-panel-v2/
- Login admin: `Marvelous2025!`

## Structure des fichiers

```
marvelous.mon-agenceweb.fr/
├── index.html              ← Redirige vers template-v2/
├── template-v2/            ← Site public
├── admin-panel-v2/         ← Panel admin
├── database/               ← Classes PHP MySQL
│   └── config.php          ← MODIFIER LE MOT DE PASSE ICI
├── config/                 ← Configuration
│   └── le-marvelous.config.js
└── images/                 ← Images (à ajouter)
```

## Images à ajouter

Tu dois ajouter tes propres images dans le dossier `images/`:
- `marvel-logo.png` - Logo du restaurant
- `marvel-hero.jpg` - Image hero (bannière principale)
- Images des produits (optionnel)

## Informations

- **Restaurant**: Le Marvelous
- **Adresse**: Riad City, Cité OMS 562 logements, Ouled Moussa
- **Téléphone**: 0556 78 21 94
- **WhatsApp**: +213 556 78 21 94
- **Instagram**: @lemarvelous.50
