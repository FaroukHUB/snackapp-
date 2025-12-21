# Template Nouveau Client SnackApp

## Comment ajouter un nouveau client

### 1. Dupliquer ce dossier
```bash
cp -r clients/_template clients/nom-du-client
```

### 2. Remplir client-config.json
- Infos restaurant
- Couleurs et branding
- Menu et catégories
- Horaires
- Réseaux sociaux

### 3. Ajouter les images dans /images/
- logo.svg
- logo.png (512x512)
- favicon.png (32x32)
- hero.jpg (1200x630)
- Photos produits

### 4. Lancer le script de déploiement
```bash
./deploy.sh nom-du-client
```

## Checklist nouveau client

- [ ] client-config.json rempli
- [ ] Logo vectoriel (SVG)
- [ ] Logo PNG
- [ ] Favicon
- [ ] Image hero
- [ ] Photos produits
- [ ] Menu complet avec prix
- [ ] Horaires vérifiés
- [ ] Liens réseaux sociaux
- [ ] Liens plateformes livraison
- [ ] Domaine acheté et configuré

## Structure des fichiers

```
clients/nom-du-client/
├── client-config.json    # Configuration complète
├── images/
│   ├── logo.svg
│   ├── logo.png
│   ├── favicon.png
│   ├── hero.jpg
│   └── products/
│       ├── burger1.jpg
│       ├── burger2.jpg
│       └── ...
└── README.md
```
