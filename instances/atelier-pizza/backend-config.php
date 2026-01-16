<?php
/**
 * Configuration Backend - L'Atelier Pizza Roubaix
 * Base de données MySQL pour instance atelier-pizza
 */

return [
    'database' => [
        'host' => 'localhost',
        'name' => 'zajr1824_atelierpizza',
        'user' => 'zajr1824_atelierpizza',
        'password' => 'Mariagor6!',
        'charset' => 'utf8mb4'
    ],

    'stripe' => [
        'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE', // Clé publique Stripe
        'secret_key' => 'sk_test_VOTRE_CLE_SECRETE',       // Clé secrète Stripe (JAMAIS commit!)
        'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',  // Secret webhook Stripe
        'mode' => 'test' // 'test' ou 'live'
    ],

    'app' => [
        'name' => "L'Atelier Pizza",
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
    ],

    'contact' => [
        'phone' => '+33320363948',
        'phoneDisplay' => '03 20 36 39 48',
        'email' => 'contact@atelierpizza.fr',
        'whatsappOrdersNumber' => '33320363948'
    ],

    'location' => [
        'address' => '70 Boulevard de la République',
        'city' => 'Roubaix',
        'postalCode' => '59100',
        'country' => 'France',
        'coordinates' => [
            'lat' => 50.702528,
            'lng' => 3.1609485
        ],
        'googleMapsUrl' => 'https://maps.app.goo.gl/keZ3gtC5qM9ozpR18',
        'googleMapsEmbed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2526.9383704890793!2d3.1609484999999995!3d50.702528199999996!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c328e08825ea27%3A0x6129c4e3150e8448!2sL&#39;Atelier%20Pizza%20Roubaix%20-%20Pizzeria%20Halal%20Roubaix!5e0!3m2!1sfr!2sdz!4v1768574384105!5m2!1sfr!2sdz'
    ]
];
