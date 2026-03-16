/**
 * Configuration Frontend - Instance Demo
 * Domaine: demo.mon-agenceweb.fr
 *
 * Instance de démonstration pour tester la nouvelle plateforme
 */

window.SNACK_CONFIG = {
    "id": "demo",
    "name": "Restaurant Demo",
    "slug": "demo",
    "legalName": "Restaurant Demo SARL",
    "currency": "EUR",
    "currencySymbol": "€",

    "branding": {
        "primaryColor": "#e63946",
        "secondaryColor": "#457b9d",
        "accentColor": "#f1faee",
        "logo": "../instances/demo/assets/logo.png",
        "favicon": "../instances/demo/assets/favicon.ico",
        "tagline": "Instance de démonstration Snackup"
    },

    "contact": {
        "phone": "+33000000000",
        "phoneDisplay": "00 00 00 00 00",
        "email": "contact@demo.mon-agenceweb.fr",
        "whatsapp": "33000000000",
        "address": {
            "street": "1 Rue de la Demo",
            "city": "Paris",
            "postalCode": "75000",
            "country": "France",
            "coordinates": {
                "lat": 48.8566,
                "lng": 2.3522
            }
        },
        "social": {
            "instagram": null,
            "facebook": null,
            "tiktok": null,
            "snapchat": null,
            "twitter": null
        }
    },

    "hours": {
        "monday": {"open": "11:00", "close": "22:00", "closed": false},
        "tuesday": {"open": "11:00", "close": "22:00", "closed": false},
        "wednesday": {"open": "11:00", "close": "22:00", "closed": false},
        "thursday": {"open": "11:00", "close": "22:00", "closed": false},
        "friday": {"open": "11:00", "close": "23:00", "closed": false},
        "saturday": {"open": "11:00", "close": "23:00", "closed": false},
        "sunday": {"open": "12:00", "close": "22:00", "closed": false}
    },

    "menu": {
        "categories": [],
        "loadFromAPI": true,
        "apiEndpoint": "/snackup/backend/api/menu.php"
    },

    "features": {
        "delivery": true,
        "takeout": true,
        "dineIn": true,
        "loyalty": true,
        "preorder": true,
        "onlinePayment": true,
        "clickAndCollect": true,
        "giftCards": false
    },

    "payment": {
        "methods": ["cash", "card", "online"],
        "stripeEnabled": true,
        "stripePublicKey": "pk_test_VOTRE_CLE_PUBLIQUE"
    },

    "delivery": {
        "enabled": true,
        "minimumOrder": 15,
        "deliveryFee": 3,
        "freeDeliveryThreshold": 30,
        "radius": 5,
        "estimatedTime": "30-45 min"
    },

    "seo": {
        "title": "Restaurant Demo - Snackup",
        "description": "Instance de démonstration de la plateforme Snackup",
        "keywords": "restaurant, demo, snackup",
        "ogImage": "../instances/demo/assets/og-image.jpg"
    }
};
