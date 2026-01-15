<?php
/**
 * Configuration Base de Données - Le Marvelous
 * Hébergement: o2switch
 */

return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => 'zajr1824_marvelous',
        'username' => 'zajr1824_marvelous',
        'password' => 'Mariagor6!',
        'charset' => 'utf8mb4'
    ],

    'app' => [
        'name' => 'Le Marvelous',
        'instance_id' => 'marvelous',
        'restaurant_id' => 1, // ID dans la base de données
        'timezone' => 'Africa/Algiers',
        'locale' => 'fr_FR',
        'currency' => 'DA' // Dinar Algérien
    ],

    'stripe' => [
        'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE',
        'secret_key' => 'sk_test_VOTRE_CLE_SECRETE',
        'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',
        'mode' => 'test'
    ],

    'email' => [
        'from' => 'contact@marvelous.com',
        'from_name' => 'Le Marvelous',
        'admin' => 'contact@marvelous.com'
    ]
];
