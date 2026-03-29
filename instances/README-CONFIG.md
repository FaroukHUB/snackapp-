# Configuration des Instances - Format Standard

## Structure du fichier backend-config.php

Chaque instance doit avoir un fichier `instances/{instance-name}/backend-config.php` avec la structure suivante :

```php
<?php
return [
    'database' => [
        'host' => '127.0.0.1',           // ou 'localhost'
        'dbname' => 'nom_de_la_base',    // ⚠️ Utiliser 'dbname', pas 'name'
        'user' => 'utilisateur_db',      // Aussi accepté: 'username'
        'password' => 'mot_de_passe',
        'charset' => 'utf8mb4',          // Toujours utf8mb4
        'port' => 3306                   // Optionnel, défaut: 3306
    ],

    'app' => [
        'name' => 'Nom du Restaurant',   // Nom d'affichage
        'instance_id' => 'slug',         // Slug unique (minuscules, tirets)
        'restaurant_id' => 1,            // ID dans la table restaurants
        'timezone' => 'Europe/Paris',    // Timezone PHP
        'locale' => 'fr_FR',
        'currency' => 'EUR'              // EUR, DA, etc.
    ],

    'stripe' => [
        'publishable_key' => 'pk_...',
        'secret_key' => 'sk_...',
        'webhook_secret' => 'whsec_...',
        'mode' => 'test'                 // 'test' ou 'live'
    ],

    'email' => [
        'from' => 'noreply@example.com',
        'from_name' => 'Restaurant',
        'admin' => 'admin@example.com'
    ],

    'contact' => [
        'phone' => '+33123456789',
        'phoneDisplay' => '01 23 45 67 89',
        'email' => 'contact@example.com',
        'whatsappOrdersNumber' => '33123456789'
    ],

    'location' => [
        'address' => 'Adresse',
        'city' => 'Ville',
        'postalCode' => 'Code postal',
        'country' => 'Pays',
        'coordinates' => [
            'lat' => 0.0,
            'lng' => 0.0
        ],
        'googleMapsUrl' => 'https://...',
        'googleMapsEmbed' => 'https://...'
    ],

    'features' => [
        'auto_category_supplements' => false,
        'loyalty_card' => true,
        'online_payment' => true,
        'click_and_collect' => true,
        'delivery' => true
    ]
];
```

## ⚠️ Clés importantes pour la base de données

Le système supporte plusieurs formats pour la compatibilité :

- **Database name** : `'dbname'` (recommandé) ou `'name'` (legacy)
- **Database user** : `'user'` (recommandé) ou `'username'` (legacy)

**Recommandation** : Utiliser `'dbname'` et `'user'` pour les nouvelles instances.

## Fichiers modifiés pour supporter les deux formats

- `snackup/admin/config.php` : Support automatique de 'dbname' et 'name'
- `snackup/backend/Database.php` : Support natif des deux formats

## Test de configuration

Utilisez le fichier de test fourni :

```bash
php snackup/admin/test-config.php
```

Ce script vérifie :
- ✅ Chargement de l'instance
- ✅ Constantes définies correctement
- ✅ Session active
- ✅ Connexion à la base de données
- ✅ Restaurant trouvé

## Sécurité

⚠️ **IMPORTANT** : Ne jamais commiter les mots de passe réels !

- Les fichiers `backend-config.php` doivent être en `.gitignore`
- Utiliser des variables d'environnement en production
- Utiliser des mots de passe forts et uniques par instance
