<?php
/**
 * Configuration Base de Données - Le Marvelous
 * Hébergement: o2switch
 *
 * INSTRUCTIONS:
 * 1. Dans cPanel o2switch, créer une base de données MySQL
 * 2. Créer un utilisateur MySQL et lui donner tous les droits
 * 3. Remplacer les valeurs ci-dessous par les vraies
 * 4. Renommer ce fichier en config.php dans le dossier database/
 */

return [
    'database' => [
        // Serveur MySQL o2switch (généralement localhost)
        'host' => 'localhost',

        // Port MySQL standard
        'port' => 3306,

        // Nom de la base de données (format: username_dbname)
        // Ex: zajr1824_marvelous
        'dbname' => 'zajr1824_marvelous',

        // Utilisateur MySQL (format: username_user)
        // Ex: zajr1824_marvelous
        'username' => 'zajr1824_marvelous',

        // Mot de passe MySQL (défini lors de la création de l'utilisateur)
        'password' => 'CHANGER_MOI',

        // Encodage UTF-8
        'charset' => 'utf8mb4'
    ]
];
