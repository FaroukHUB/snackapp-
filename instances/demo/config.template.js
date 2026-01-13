// Configuration Template - À adapter pour votre restaurant
window.SNACK_CONFIG = {
    "id": "votre-restaurant",
    "name": "Votre Restaurant",
    "slug": "votre-restaurant",
    "legalName": "Votre Restaurant SARL",
    "currency": "EUR",  // Devise à afficher (DA, EUR, USD, MAD, TND, etc.)

    "branding": {
        "primaryColor": "#e63946",
        "logo": "../instances/demo/assets/logo.png",
        "tagline": "Votre slogan ici"
    },

    "contact": {
        "phone": "+213 XX XX XX XX XX",
        "email": "contact@votre-restaurant.com",
        "address": {
            "street": "Votre adresse",
            "city": "Votre ville",
            "postalCode": "XXXXX",
            "country": "Algérie"
        },
        "social": {
            "instagram": "https://instagram.com/votre-compte",
            "facebook": "https://facebook.com/votre-page",
            "tiktok": null,
            "snapchat": null
        }
    },

    "hours": {
        "monday": {"open": "08:00", "close": "22:00"},
        "tuesday": {"open": "08:00", "close": "22:00"},
        "wednesday": {"open": "08:00", "close": "22:00"},
        "thursday": {"open": "08:00", "close": "22:00"},
        "friday": {"open": "08:00", "close": "22:00"},
        "saturday": {"open": "08:00", "close": "23:00"},
        "sunday": {"open": "09:00", "close": "23:00"}
    },

    "menu": {
        "categories": []
    },

    "features": {
        "delivery": true,
        "takeout": true,
        "dineIn": true,
        "loyalty": true,
        "preorder": true
    }
};
