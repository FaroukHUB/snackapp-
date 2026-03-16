<?php
/**
 * Configuration Backend - Instance Demo
 * Domaine: demo.mon-agenceweb.fr
 *
 * Instance de démonstration pour tester la nouvelle plateforme
 */

return [
    'database' => [
        'host' => '127.0.0.1',
        'name' => 'zajr1824_demo',
        'user' => 'zajr1824_demo',
        'password' => 'CHANGE_ME',
        'charset' => 'utf8mb4'
    ],

    'stripe' => [
        'publishable_key' => 'pk_test_VOTRE_CLE_PUBLIQUE',
        'secret_key' => 'sk_test_VOTRE_CLE_SECRETE',
        'webhook_secret' => 'whsec_VOTRE_WEBHOOK_SECRET',
        'mode' => 'test'
    ],

    'app' => [
        'name' => 'Restaurant Demo',
        'instance_id' => 'demo',
        'restaurant_id' => 99, // ID unique pour l'instance demo
        'timezone' => 'Europe/Paris',
        'locale' => 'fr_FR',
        'currency' => 'EUR'
    ],

    'features' => [
        'auto_category_supplements' => false,
        'loyalty_card' => true,
        'online_payment' => true,
        'click_and_collect' => true,
        'delivery' => true
    ],

    'email' => [
        'from' => 'demo@mon-agenceweb.fr',
        'from_name' => 'Restaurant Demo',
        'admin' => 'demo@mon-agenceweb.fr'
    ],

    'contact' => [
        'phone' => '+33000000000',
        'phoneDisplay' => '00 00 00 00 00',
        'email' => 'contact@demo.mon-agenceweb.fr',
        'whatsappOrdersNumber' => '33000000000'
    ],

    'location' => [
        'address' => '1 Rue de la Demo',
        'city' => 'Paris',
        'postalCode' => '75000',
        'country' => 'France',
        'coordinates' => [
            'lat' => 48.8566,
            'lng' => 2.3522
        ],
        'googleMapsUrl' => 'https://maps.google.com',
        'googleMapsEmbed' => ''
    ]
];
