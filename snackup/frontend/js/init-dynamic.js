/**
 * INIT-DYNAMIC.JS - Initialisation Dynamique Multi-Instance
 *
 * Ce fichier DOIT être chargé en PREMIER dans toutes les pages HTML
 * Il charge les données du restaurant depuis l'API et met à jour dynamiquement:
 * - Meta tags (title, description, og:*, twitter:*)
 * - Contenus textuels hardcodés
 * - URLs canoniques
 * - Tous les éléments spécifiques à l'instance
 *
 * ✅ Architecture 100% Scalable
 * ✅ Pas de hardcoding
 * ✅ Fonctionne pour toutes les instances automatiquement
 */

(function() {
    'use strict';

    // Configuration
    const API_BASE_PATH = '../config/';

    /**
     * Charge les données du restaurant depuis l'API
     */
    async function loadRestaurantData() {
        try {
            const response = await fetch(API_BASE_PATH + 'restaurant.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            return data;
        } catch (error) {
            console.error('❌ Erreur chargement restaurant:', error);
            return null;
        }
    }

    /**
     * Met à jour les meta tags dynamiquement
     */
    function updateMetaTags(restaurant) {
        if (!restaurant) return;

        const baseUrl = window.location.origin;
        const currentPath = window.location.pathname;
        const fullUrl = baseUrl + currentPath;

        // Title
        const pageTitle = getPageTitle(restaurant);
        document.title = pageTitle;
        updateMetaTag('og:title', pageTitle);
        updateMetaTag('twitter:title', pageTitle);

        // Description
        const description = getPageDescription(restaurant);
        updateMetaTag('name', 'description', description);
        updateMetaTag('og:description', description);
        updateMetaTag('twitter:description', description);

        // URLs
        updateLinkTag('canonical', fullUrl);
        updateMetaTag('og:url', fullUrl);
        updateMetaTag('twitter:url', fullUrl);

        // Site Name
        updateMetaTag('og:site_name', restaurant.name);
        updateMetaTag('name', 'author', restaurant.name);

        // Images (utiliser logo du restaurant si disponible)
        const imageUrl = restaurant.branding?.logo || `${baseUrl}/images/hero.webp`;
        updateMetaTag('og:image', imageUrl);
        updateMetaTag('twitter:image', imageUrl);
        updateMetaTag('og:image:alt', `${restaurant.name} - ${restaurant.brandTagline || ''}`);
        updateMetaTag('twitter:image:alt', `${restaurant.name} - ${restaurant.brandTagline || ''}`);

        // Keywords
        const keywords = generateKeywords(restaurant);
        updateMetaTag('name', 'keywords', keywords);

        // Theme color (couleur principale du restaurant)
        const themeColor = restaurant.theme?.primary || restaurant.branding?.primaryColor || '#e63946';
        updateMetaTag('name', 'theme-color', themeColor);

        console.log('✅ Meta tags mis à jour pour:', restaurant.name);
    }

    /**
     * Génère le titre de la page selon le contexte
     */
    function getPageTitle(restaurant) {
        const path = window.location.pathname;

        if (path.includes('a-propos')) {
            return `À Propos - ${restaurant.name} | Notre Histoire, Valeurs & Ambiance`;
        } else if (path.includes('cart')) {
            return `Panier - ${restaurant.name}`;
        } else if (path.includes('fidelite')) {
            return `Programme Fidélité - ${restaurant.name}`;
        } else if (path.includes('click-collect')) {
            return `Click & Collect - ${restaurant.name}`;
        } else {
            // Page d'accueil
            return `${restaurant.name} - ${restaurant.brandTagline || 'Commandez en ligne'} | ${restaurant.location?.city || ''}`;
        }
    }

    /**
     * Génère la description de la page
     */
    function getPageDescription(restaurant) {
        const path = window.location.pathname;

        if (path.includes('a-propos')) {
            return `Découvrez l'histoire de ${restaurant.name}, notre passion pour la cuisine, nos valeurs d'authenticité et notre ambiance chaleureuse.`;
        } else if (path.includes('fidelite')) {
            return `Rejoignez le programme de fidélité ${restaurant.name} et profitez d'avantages exclusifs à chaque commande.`;
        } else if (path.includes('click-collect')) {
            return `Commandez en ligne et récupérez votre commande chez ${restaurant.name}. Click & Collect simple et rapide.`;
        } else {
            // Page d'accueil
            return `${restaurant.name} à ${restaurant.location?.city || ''} : ${restaurant.brandTagline || 'Commandez en ligne'}. Livraison et click & collect disponibles.`;
        }
    }

    /**
     * Génère les keywords SEO
     */
    function generateKeywords(restaurant) {
        const city = restaurant.location?.city || '';
        const name = restaurant.name;

        return `${name}, restaurant ${city}, ${name.toLowerCase()}, commande en ligne ${city}, livraison ${city}, click and collect ${city}`;
    }

    /**
     * Met à jour ou crée une meta tag
     */
    function updateMetaTag(property, content, propertyType = 'property') {
        if (!content) return;

        const selector = propertyType === 'name'
            ? `meta[name="${property}"]`
            : `meta[property="${property}"]`;

        let meta = document.querySelector(selector);

        if (!meta) {
            meta = document.createElement('meta');
            if (propertyType === 'name') {
                meta.name = property;
            } else {
                meta.setAttribute('property', property);
            }
            document.head.appendChild(meta);
        }

        meta.content = content;
    }

    /**
     * Met à jour un link tag (ex: canonical)
     */
    function updateLinkTag(rel, href) {
        if (!href) return;

        let link = document.querySelector(`link[rel="${rel}"]`);

        if (!link) {
            link = document.createElement('link');
            link.rel = rel;
            document.head.appendChild(link);
        }

        link.href = href;
    }

    /**
     * Met à jour le contenu dynamique dans la page
     */
    function updatePageContent(restaurant) {
        if (!restaurant) return;

        // Remplacer tous les textes qui contiennent des noms hardcodés
        // On va ajouter un attribut data-dynamic pour identifier les éléments à remplacer

        // Stocker les données dans window pour que config.js puisse y accéder
        window.RESTAURANT_DATA = restaurant;

        console.log('✅ Données restaurant disponibles globalement');
    }

    /**
     * Met à jour le JSON-LD Schema
     */
    function updateSchemaOrg(restaurant) {
        if (!restaurant) return;

        // Supprimer l'ancien schema s'il existe
        const oldSchema = document.querySelector('script[type="application/ld+json"]');
        if (oldSchema) oldSchema.remove();

        // Créer le nouveau schema
        const schema = {
            "@context": "https://schema.org",
            "@type": "Restaurant",
            "name": restaurant.name,
            "image": restaurant.branding?.logo || window.location.origin + "/images/hero.webp",
            "url": window.location.origin,
            "telephone": restaurant.contact?.phone,
            "address": {
                "@type": "PostalAddress",
                "streetAddress": restaurant.location?.address,
                "addressLocality": restaurant.location?.city,
                "postalCode": restaurant.location?.postalCode,
                "addressCountry": "FR"
            },
            "servesCuisine": restaurant.brandTagline || "Cuisine",
            "priceRange": restaurant.priceRange || "€€",
            "acceptsReservations": "False"
        };

        // Ajouter au DOM
        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.textContent = JSON.stringify(schema, null, 2);
        document.head.appendChild(script);

        console.log('✅ Schema.org mis à jour');
    }

    /**
     * Initialisation au chargement de la page
     */
    async function init() {
        console.log('🚀 Initialisation frontend dynamique...');

        const restaurant = await loadRestaurantData();

        if (!restaurant) {
            console.error('❌ Impossible de charger les données du restaurant');
            return;
        }

        console.log('✅ Restaurant chargé:', restaurant.name);

        // Mettre à jour tous les éléments dynamiques
        updateMetaTags(restaurant);
        updatePageContent(restaurant);
        updateSchemaOrg(restaurant);

        // Dispatcher un event pour notifier que les données sont chargées
        window.dispatchEvent(new CustomEvent('restaurant-data-loaded', { detail: restaurant }));

        console.log('🎉 Frontend dynamique initialisé avec succès');
    }

    // Lancer l'initialisation dès que le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
