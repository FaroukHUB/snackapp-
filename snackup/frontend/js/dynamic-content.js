/**
 * DYNAMIC-CONTENT.JS - Remplacement dynamique du contenu HTML
 *
 * Ce script remplace TOUT le contenu hardcodé par les vraies données
 * depuis l'API restaurant.php
 *
 * ✅ Architecture 100% Scalable
 * ✅ Fonctionne pour toutes les instances automatiquement
 */

(function() {
    'use strict';

    // Attendre que RESTAURANT_DATA soit chargé par init-meta.js
    window.addEventListener('DOMContentLoaded', function() {
        // Si les données sont déjà là, on démarre
        if (window.RESTAURANT_DATA) {
            initDynamicContent();
        } else {
            // Sinon on attend l'event de init-meta.js
            window.addEventListener('restaurant-data-loaded', function(e) {
                initDynamicContent();
            });
        }
    });

    async function initDynamicContent() {
        console.log('🔄 Initialisation du contenu dynamique...');

        // Si pas de données, les charger
        if (!window.RESTAURANT_DATA) {
            window.RESTAURANT_DATA = await loadRestaurantData();
        }

        const restaurant = window.RESTAURANT_DATA;
        if (!restaurant) {
            console.error('❌ Impossible de charger les données du restaurant');
            return;
        }

        // Remplacer le contenu selon la page
        const path = window.location.pathname;

        if (path.includes('index.html') || path.endsWith('/') || path.endsWith('/snackup/frontend/')) {
            updateHomePage(restaurant);
        } else if (path.includes('a-propos')) {
            updateAboutPage(restaurant);
        } else if (path.includes('fidelite')) {
            updateLoyaltyPage(restaurant);
        } else if (path.includes('click-collect')) {
            updateClickCollectPage(restaurant);
        }

        // Contenu commun à toutes les pages
        updateCommonContent(restaurant);

        console.log('✅ Contenu dynamique initialisé');
    }

    /**
     * Charge les données du restaurant
     */
    async function loadRestaurantData() {
        try {
            const response = await fetch('../config/restaurant.php');
            if (!response.ok) throw new Error('Failed to load restaurant data');
            return await response.json();
        } catch (error) {
            console.error('❌ Erreur chargement restaurant:', error);
            return null;
        }
    }

    /**
     * Met à jour le contenu commun (header, footer, contact)
     */
    function updateCommonContent(restaurant) {
        // Logo et nom dans le header
        const logoTexts = document.querySelectorAll('[data-restaurant-name]');
        logoTexts.forEach(el => el.textContent = restaurant.name);

        // Téléphone
        const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
        phoneLinks.forEach(link => {
            link.href = `tel:${restaurant.contact.phone}`;
            if (link.textContent.includes('+') || link.textContent.match(/\d/)) {
                link.textContent = restaurant.contact.phoneDisplay || restaurant.contact.phone;
            }
        });

        // WhatsApp
        const whatsappLinks = document.querySelectorAll('a[href*="wa.me"], a[href*="whatsapp"]');
        if (restaurant.contact.whatsappOrdersNumber) {
            whatsappLinks.forEach(link => {
                link.href = `https://wa.me/${restaurant.contact.whatsappOrdersNumber}`;
            });
        }

        // Adresse
        const addressElements = document.querySelectorAll('[data-restaurant-address]');
        addressElements.forEach(el => {
            el.textContent = restaurant.location.address;
        });

        // Ville
        const cityElements = document.querySelectorAll('[data-restaurant-city]');
        cityElements.forEach(el => {
            el.textContent = restaurant.location.city;
        });

        // Email
        const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
        if (restaurant.contact.email) {
            emailLinks.forEach(link => {
                link.href = `mailto:${restaurant.contact.email}`;
                if (link.textContent.includes('@')) {
                    link.textContent = restaurant.contact.email;
                }
            });
        }

        // Footer - nom du restaurant
        const footerName = document.querySelector('footer h3, footer .footer-brand');
        if (footerName && footerName.textContent.includes('Marvelous')) {
            footerName.textContent = restaurant.name;
        }

        // Copyright
        const copyrightElements = document.querySelectorAll('[data-copyright], .copyright');
        copyrightElements.forEach(el => {
            const year = new Date().getFullYear();
            el.textContent = `© ${year} ${restaurant.name}. Tous droits réservés.`;
        });
    }

    /**
     * Met à jour la page d'accueil
     */
    function updateHomePage(restaurant) {
        // Hero - Titre principal
        const heroTitle = document.querySelector('.hero h1, .hero-title, h1');
        if (heroTitle && heroTitle.textContent.includes('Marvelous')) {
            heroTitle.textContent = restaurant.name;
        }

        // Hero - Tagline
        const heroTagline = document.querySelector('.hero .tagline, .hero p, .hero-subtitle');
        if (heroTagline && restaurant.brandTagline) {
            heroTagline.textContent = restaurant.brandTagline;
        }

        // Remplacer les avis Google si présents
        // Les avis seront chargés depuis la base de données si disponibles
        // Pour l'instant on les cache s'ils sont hardcodés pour Marvelous
        const reviewCards = document.querySelectorAll('.review-card, .testimonial');
        reviewCards.forEach(card => {
            const reviewText = card.textContent || card.innerHTML;
            if (reviewText.includes('Marvelous') || reviewText.includes('Ouled Moussa')) {
                // Masquer les avis hardcodés de Marvelous
                card.style.display = 'none';
            }
        });

        // Section "À propos" sur la home
        const aboutSection = document.querySelector('.about-section, #about');
        if (aboutSection) {
            const aboutTitle = aboutSection.querySelector('h2');
            if (aboutTitle && aboutTitle.textContent.includes('Marvelous')) {
                aboutTitle.textContent = `À propos de ${restaurant.name}`;
            }

            const aboutText = aboutSection.querySelector('p');
            if (aboutText && aboutText.textContent.includes('Marvelous')) {
                aboutText.textContent = `Découvrez ${restaurant.name}, votre ${restaurant.brandTagline || 'restaurant'} situé à ${restaurant.location.city}.`;
            }
        }
    }

    /**
     * Met à jour la page À Propos
     */
    function updateAboutPage(restaurant) {
        // Titre principal
        const mainTitle = document.querySelector('h1, .page-title');
        if (mainTitle) {
            mainTitle.textContent = `À propos de ${restaurant.name}`;
        }

        // Remplacer TOUS les "Marvelous" par le vrai nom
        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);

        // Remplacer "Ouled Moussa" par la vraie ville
        if (restaurant.location.city !== 'Ouled Moussa') {
            replaceTextInPage('Ouled Moussa', restaurant.location.city);
        }

        // Tagline
        const taglines = document.querySelectorAll('.tagline, .subtitle, .lead');
        taglines.forEach(el => {
            if (restaurant.brandTagline && el.textContent.includes('Diner')) {
                el.textContent = restaurant.brandTagline;
            }
        });

        // Section "Bienvenue"
        const welcomeSection = document.querySelector('.welcome-section, .intro');
        if (welcomeSection && restaurant.brandTagline) {
            const welcomeText = welcomeSection.querySelector('p');
            if (welcomeText) {
                welcomeText.textContent = `Au cœur de ${restaurant.location.city}, ${restaurant.name} est bien plus qu'un simple restaurant. C'est un lieu où se mêlent ${restaurant.brandTagline || 'passion culinaire'} et convivialité.`;
            }
        }
    }

    /**
     * Met à jour la page Fidélité
     */
    function updateLoyaltyPage(restaurant) {
        // Titre
        const title = document.querySelector('h1, .page-title');
        if (title) {
            title.textContent = `Programme Fidélité ${restaurant.name}`;
        }

        // Remplacer tous les "Marvelous"
        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);

        // Description
        const description = document.querySelector('.program-description, .intro p');
        if (description) {
            description.textContent = `Rejoignez le programme de fidélité ${restaurant.name} et profitez d'avantages exclusifs à chaque commande !`;
        }
    }

    /**
     * Met à jour la page Click & Collect
     */
    function updateClickCollectPage(restaurant) {
        // Titre
        const title = document.querySelector('h1, .page-title');
        if (title) {
            title.textContent = `Click & Collect - ${restaurant.name}`;
        }

        // Remplacer tous les "Marvelous"
        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);

        // Adresse de récupération
        const addressInfo = document.querySelector('.pickup-address, .address-info');
        if (addressInfo) {
            addressInfo.innerHTML = `
                <strong>Adresse de récupération :</strong><br>
                ${restaurant.name}<br>
                ${restaurant.location.address}<br>
                ${restaurant.location.postalCode || ''} ${restaurant.location.city}
            `;
        }

        // Téléphone pour questions
        const contactInfo = document.querySelector('.contact-info');
        if (contactInfo && restaurant.contact.phone) {
            const phoneText = contactInfo.querySelector('p, span');
            if (phoneText) {
                phoneText.textContent = `Des questions ? Appelez-nous au ${restaurant.contact.phoneDisplay || restaurant.contact.phone}`;
            }
        }
    }

    /**
     * Remplace un texte dans toute la page
     */
    function replaceTextInPage(oldText, newText) {
        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        const nodesToReplace = [];
        let node;

        while (node = walker.nextNode()) {
            if (node.nodeValue.includes(oldText)) {
                nodesToReplace.push(node);
            }
        }

        nodesToReplace.forEach(node => {
            node.nodeValue = node.nodeValue.replace(new RegExp(oldText, 'g'), newText);
        });
    }

    /**
     * Utilitaire : Échapper le HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
