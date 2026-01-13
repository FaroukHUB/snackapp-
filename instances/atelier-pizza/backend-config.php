<?php
/**
 * Configuration Backend - L'Atelier Pizza Roubaix
 * Base de données MySQL pour instance atelier-pizza
 */

return [
    'database' => [
        'host' => 'localhost',
        'name' => 'atelier_pizza_db',  // À créer sur o2switch
        'user' => 'atelier_pizza_user', // Utilisateur MySQL
        'password' => 'VOTRE_MOT_DE_PASSE_ICI', // À définir
        'charset' => 'utf8mb4'
    ],

    'stripe' => [
        'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE', // Clé publique Stripe
        'secret_key' => 'sk_test_VOTRE_CLE_SECRETE',       // Clé secrète Stripe (JAMAIS commit!)
        'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',  // Secret webhook Stripe
        'mode' => 'test' // 'test' ou 'live'
    ],

    'app' => [
        'instance_id' => 'atelier-pizza-roubaix',
        'restaurant_id' => 3, // ID unique pour cette instance dans la DB
        'timezone' => 'Europe/Paris',
        'locale' => 'fr_FR',
        'currency' => 'EUR'
    ],

    'email' => [
        'from' => 'atelierpizzaroubaix@gmail.com',
        'from_name' => "L'Atelier Pizza Roubaix",
        'admin' => 'atelierpizzaroubaix@gmail.com'
    ]
];
