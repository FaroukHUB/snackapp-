/**
 * META INITIALIZATION - Multi-instance support
 * Charge les métadonnées du restaurant depuis l'API et met à jour le HTML
 * Ce script doit s'exécuter AVANT tous les autres
 */

(async function() {
    'use strict';

    try {
        // Charger les données du restaurant
        const response = await fetch('../config/restaurant.php');
        if (!response.ok) {
            throw new Error('Failed to load restaurant data');
        }

        const restaurant = await response.json();

        // Stocker globalement pour utilisation par d'autres scripts
        window.RESTAURANT_DATA = restaurant;

        // 1. Mettre à jour le title
        const titleText = `${restaurant.name} - ${restaurant.brandTagline || 'Commandez en ligne'} | ${restaurant.location.city}`;
        document.title = titleText;

        // 2. Mettre à jour les meta tags
        updateMetaTag('description', `${restaurant.name} à ${restaurant.location.city} : ${restaurant.brandTagline || 'Découvrez notre menu'}`);
        updateMetaTag('author', restaurant.name);

        // 3. Open Graph
        updateMetaTag('og:title', titleText, 'property');
        updateMetaTag('og:description', restaurant.brandTagline || `Découvrez ${restaurant.name}`, 'property');
        updateMetaTag('og:site_name', restaurant.name, 'property');
        updateMetaTag('og:url', window.location.href, 'property');

        // 4. Twitter Card
        updateMetaTag('twitter:title', titleText);
        updateMetaTag('twitter:description', restaurant.brandTagline || `Découvrez ${restaurant.name}`);

        // 5. Canonical
        const canonical = document.querySelector('link[rel="canonical"]');
        if (canonical) {
            canonical.href = window.location.origin + window.location.pathname;
        }

        // 6. Theme color
        if (restaurant.branding?.primaryColor || restaurant.theme?.primary) {
            const themeColor = restaurant.branding?.primaryColor || restaurant.theme?.primary;
            updateMetaTag('theme-color', themeColor);
            updateMetaTag('msapplication-TileColor', themeColor);
        }

        // 7. Mettre à jour Schema.org JSON-LD
        updateSchemaOrg(restaurant);

        // 8. Stocker la devise globalement
        window.CURRENCY = restaurant.priceRange || restaurant._jsConfig?.currency || 'EUR';
        window.CURRENCY_SYMBOL = getCurrencySymbol(window.CURRENCY);

        console.log('✅ Meta tags initialized for:', restaurant.name);
        console.log('   Currency:', window.CURRENCY);

    } catch (error) {
        console.error('❌ Failed to initialize meta tags:', error);
        // Fallback: utiliser EUR par défaut
        window.CURRENCY = 'EUR';
        window.CURRENCY_SYMBOL = '€';
    }

    /**
     * Helper: Update meta tag
     */
    function updateMetaTag(name, content, attr = 'name') {
        let meta = document.querySelector(`meta[${attr}="${name}"]`);
        if (!meta) {
            meta = document.createElement('meta');
            meta.setAttribute(attr, name);
            document.head.appendChild(meta);
        }
        meta.content = content;
    }

    /**
     * Helper: Get currency symbol
     */
    function getCurrencySymbol(currency) {
        const symbols = {
            'EUR': '€',
            'USD': '$',
            'GBP': '£',
            'DA': 'DA',
            'DZD': 'DA',
            'MAD': 'MAD'
        };
        return symbols[currency] || currency;
    }

    /**
     * Update Schema.org structured data
     */
    function updateSchemaOrg(restaurant) {
        // Trouver le script JSON-LD existant
        let schemaScript = document.querySelector('script[type="application/ld+json"]');

        if (!schemaScript) {
            schemaScript = document.createElement('script');
            schemaScript.type = 'application/ld+json';
            document.head.appendChild(schemaScript);
        }

        // Construire le nouveau schema
        const schema = {
            "@context": "https://schema.org",
            "@type": "Restaurant",
            "name": restaurant.name,
            "url": window.location.origin,
            "telephone": restaurant.contact.phone,
            "priceRange": restaurant.priceRange,
            "address": {
                "@type": "PostalAddress",
                "streetAddress": restaurant.location.address,
                "addressLocality": restaurant.location.city,
                "postalCode": restaurant.location.postalCode || "",
                "addressCountry": restaurant.location.countryCode || "FR"
            }
        };

        // Ajouter les coordonnées GPS si disponibles
        if (restaurant.location.latitude && restaurant.location.longitude) {
            schema.geo = {
                "@type": "GeoCoordinates",
                "latitude": restaurant.location.latitude,
                "longitude": restaurant.location.longitude
            };
        }

        schemaScript.textContent = JSON.stringify(schema, null, 2);
    }
})();
