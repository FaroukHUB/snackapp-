<?php
/**
 * SnackApp v1 - Configuration Database
 *
 * INSTRUCTIONS:
 * 1. Copier ce fichier vers config.php
 * 2. Remplir avec vos identifiants MySQL
 * 3. Ne JAMAIS commit config.php (déjà dans .gitignore)
 *
 * MAMP (local):
 *   host: localhost
 *   port: 8889 (ou 3306)
 *   username: root
 *   password: root
 *
 * O2Switch (production):
 *   host: localhost
 *   port: 3306
 *   username: [votre_username_o2switch]
 *   password: [votre_password]
 *   dbname: [votre_dbname]
 */

return [
    'database' => [
        'host' => 'localhost',
        'port' => 8889,           // MAMP default, 3306 pour production
        'dbname' => 'snackapp',
        'username' => 'root',
        'password' => 'root',
        'charset' => 'utf8mb4'
    ],

    // Restaurant par défaut (pour migration)
    'default_restaurant' => [
        'slug' => 'fabrik-burger',
        'name' => 'Fabrik Burger'
    ]
];
