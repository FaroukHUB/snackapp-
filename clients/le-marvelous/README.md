# Le Marvelous - Guide de Déploiement

## Informations Client
- **Restaurant**: Le Marvelous
- **Type**: Diner 50's - Crêperie
- **Localisation**: Ouled Moussa, Algérie
- **Domaine**: marvelous.mon-agenceweb.fr
- **Devise**: DZD (Dinar Algérien)

## Étapes de Déploiement sur o2switch

### 1. Créer la Base de Données MySQL

Dans cPanel o2switch:

1. Aller dans **Bases de données MySQL**
2. Créer une nouvelle base: `zajr1824_marvelous`
3. Créer un utilisateur: `zajr1824_marvelous`
4. Associer l'utilisateur à la base avec **TOUS LES PRIVILÈGES**

### 2. Importer le Schéma

1. Aller dans **phpMyAdmin**
2. Sélectionner la base `zajr1824_marvelous`
3. Onglet **Importer**
4. D'abord importer: `database/schema.sql` (structure)
5. Puis importer: `database/migrations/add_loyalty_system.sql`
6. Enfin importer: `clients/le-marvelous/init-database.sql` (données)

### 3. Configurer la Connexion

1. Copier `config.o2switch.php` vers `database/config.php`
2. Modifier les valeurs:
   ```php
   'dbname' => 'zajr1824_marvelous',
   'username' => 'zajr1824_marvelous',
   'password' => 'VOTRE_MOT_DE_PASSE',
   ```

### 4. Upload des Fichiers

Via le gestionnaire de fichiers cPanel ou FTP:

1. Uploader tout le contenu du repo dans le dossier du domaine
2. S'assurer que les permissions sont correctes:
   - Dossiers: 755
   - Fichiers: 644
   - `admin-panel-v2/data/`: 755 (écriture)

### 5. Configuration Admin Panel

Modifier `admin-panel-v2/config.php`:

```php
define('ADMIN_PASSWORD', 'Marvelous2025!');
define('CONFIG_FILE', __DIR__ . '/../config/le-marvelous.config.js');
```

### 6. Test

1. Accéder à `https://marvelous.mon-agenceweb.fr/admin-panel-v2/`
2. Connexion: `admin` / `Marvelous2025!`
3. Vérifier que le menu apparaît correctement

## Structure des Fichiers

```
marvelous.mon-agenceweb.fr/
├── admin-panel-v2/          # Panel admin
├── template-v2/             # Site public
├── database/
│   ├── config.php           # Config MySQL (à créer)
│   ├── Database.php
│   ├── repositories/
│   └── schema.sql
├── config/
│   └── le-marvelous.config.js
├── images/
│   └── le-marvelous/        # Images produits
└── clients/
    └── le-marvelous/
        └── client-config.json
```

## Spécificités Le Marvelous

- **Salle Femmes**: Espace réservé aux femmes
- **Salle Familles**: Espace réservé aux familles
- **Vendredi**: Horaires spéciaux (pause prière 11h30-15h30)
- **Livraison**: WhatsApp uniquement (+213 556 78 21 94)
- **Paiement**: Espèces uniquement

## Contacts

- WhatsApp: +213 556 78 21 94
- Instagram: @lemarvelous.50
