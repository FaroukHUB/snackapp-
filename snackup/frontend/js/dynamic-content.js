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

    let restaurant = null;

    // Attendre que le DOM soit prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        console.log('🔄 [Dynamic Content] Initialisation...');

        // Essayer plusieurs sources de données
        if (window.RESTAURANT_DATA) {
            console.log('✅ [Dynamic Content] Données trouvées dans window.RESTAURANT_DATA');
            restaurant = window.RESTAURANT_DATA;
            applyContent();
        } else if (window.Config && window.Config.restaurant) {
            console.log('✅ [Dynamic Content] Données trouvées dans window.Config.restaurant');
            restaurant = window.Config.restaurant;
            applyContent();
        } else {
            // Écouter l'event de init-meta.js
            console.log('⏳ [Dynamic Content] En attente des données...');
            window.addEventListener('restaurant-data-loaded', function(e) {
                console.log('✅ [Dynamic Content] Event reçu');
                restaurant = e.detail || window.RESTAURANT_DATA;
                applyContent();
            });

            // Fallback: charger directement
            setTimeout(loadAndApply, 1000);
        }
    }

    async function loadAndApply() {
        if (restaurant) return; // Déjà chargé

        console.log('📥 [Dynamic Content] Chargement direct des données...');
        try {
            const response = await fetch('../../config/restaurant.php');
            if (response.ok) {
                restaurant = await response.json();
                console.log('✅ [Dynamic Content] Données chargées:', restaurant);
                applyContent();
            }
        } catch (error) {
            console.error('❌ [Dynamic Content] Erreur chargement:', error);
        }
    }

    function applyContent() {
        if (!restaurant || !restaurant.name) {
            console.error('❌ [Dynamic Content] Données invalides');
            return;
        }

        console.log('🎨 [Dynamic Content] Application du contenu pour:', restaurant.name);

        // Appliquer selon la page
        const path = window.location.pathname;

        if (path.includes('index.html') || path.endsWith('/') || path.endsWith('/frontend/')) {
            updateHomePage();
        } else if (path.includes('a-propos')) {
            updateAboutPage();
        } else if (path.includes('fidelite')) {
            updateLoyaltyPage();
        } else if (path.includes('click-collect')) {
            updateClickCollectPage();
        }

        // Contenu commun
        updateCommonContent();

        console.log('✅ [Dynamic Content] Terminé');
    }

    /**
     * Met à jour la page d'accueil
     */
    function updateHomePage() {
        console.log('🏠 [Dynamic Content] Mise à jour page accueil');

        // Hero - utiliser les IDs spécifiques
        const heroTitle = document.getElementById('heroTitle');
        const heroSubtitle = document.getElementById('heroSubtitle');

        if (heroTitle) {
            heroTitle.textContent = restaurant.name;
            console.log('✅ Hero title mis à jour:', restaurant.name);
        }

        if (heroSubtitle && restaurant.brandTagline) {
            heroSubtitle.textContent = restaurant.brandTagline;
            console.log('✅ Hero subtitle mis à jour:', restaurant.brandTagline);
        }

        // IMPORTANT: Masquer les avis Google si contiennent Ouled Moussa ou Marvelous
        hideWrongReviews();

        // Alt de l'image hero
        const heroImage = document.getElementById('heroImage');
        if (heroImage) {
            heroImage.alt = `${restaurant.name} ${restaurant.brandTagline || ''}`;
        }
    }

    /**
     * Masquer les avis qui ne correspondent pas à ce restaurant
     */
    function hideWrongReviews() {
        console.log('🔍 [Dynamic Content] Vérification des avis...');

        const reviewsSection = document.querySelector('.reviews-section');
        if (!reviewsSection) {
            console.log('ℹ️ Pas de section avis trouvée');
            return;
        }

        // Si on n'est PAS Le Marvelous, cacher toute la section Google Reviews
        // Car tous ces avis sont pour Le Marvelous à Ouled Moussa
        if (!restaurant.name.includes('Marvelous')) {
            reviewsSection.style.display = 'none';
            console.log('❌ Section Google Reviews cachée (avis pour un autre restaurant)');
            return;
        }

        // Si on EST Le Marvelous, vérifier quand même chaque avis
        const reviewCards = document.querySelectorAll('.review-card');
        let hiddenCount = 0;

        reviewCards.forEach(card => {
            const text = card.textContent || '';

            // Si l'avis mentionne Ouled Moussa mais qu'on n'est PAS à Ouled Moussa
            if (text.includes('Ouled Moussa') && restaurant.location.city !== 'Ouled Moussa') {
                card.style.display = 'none';
                hiddenCount++;
                console.log('❌ Avis caché (Ouled Moussa)');
            }
        });

        // Si tous les avis sont cachés, masquer toute la section
        if (hiddenCount > 0 && hiddenCount === reviewCards.length) {
            reviewsSection.style.display = 'none';
            console.log('❌ Section avis entièrement cachée (tous les avis cachés)');
        }

        console.log(`✅ ${hiddenCount} avis cachés`);
    }

    /**
     * Met à jour la page À Propos
     */
    function updateAboutPage() {
        console.log('📖 [Dynamic Content] Mise à jour page À Propos');

        // Remplacer tous les "Le Marvelous" et "Marvelous"
        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);

        // Remplacer Ouled Moussa
        if (restaurant.location.city !== 'Ouled Moussa') {
            replaceTextInPage('Ouled Moussa', restaurant.location.city);
        }

        console.log('✅ Page À Propos mise à jour');
    }

    /**
     * Met à jour la page Fidélité
     */
    function updateLoyaltyPage() {
        console.log('⭐ [Dynamic Content] Mise à jour page Fidélité');

        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);
        replaceTextInPage('Fabrik Burger', restaurant.name);

        console.log('✅ Page Fidélité mise à jour');
    }

    /**
     * Met à jour la page Click & Collect
     */
    function updateClickCollectPage() {
        console.log('🛍️ [Dynamic Content] Mise à jour page Click & Collect');

        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);
        replaceTextInPage('Fabrik Burger', restaurant.name);

        console.log('✅ Page Click & Collect mise à jour');
    }

    /**
     * Met à jour le contenu commun (header, footer, meta)
     */
    function updateCommonContent() {
        console.log('🔧 [Dynamic Content] Mise à jour contenu commun');

        // Logo et nom dans le header
        const logoSpans = document.querySelectorAll('.logo span, .logo-text');
        logoSpans.forEach(span => {
            if (span.textContent.includes('Marvelous') || span.textContent.includes('Fabrik')) {
                span.textContent = restaurant.name;
            }
        });

        // Footer
        const footerHeading = document.querySelector('footer h3');
        if (footerHeading && (footerHeading.textContent.includes('Marvelous') || footerHeading.textContent.includes('Fabrik'))) {
            footerHeading.textContent = restaurant.name;
        }

        // Copyright
        const year = new Date().getFullYear();
        const copyrightPara = document.querySelector('footer p:last-child');
        if (copyrightPara && copyrightPara.textContent.includes('©')) {
            copyrightPara.textContent = `© ${year} ${restaurant.name}. Tous droits réservés.`;
        }

        // Téléphones
        document.querySelectorAll('a[href^="tel:"]').forEach(link => {
            if (restaurant.contact.phone) {
                link.href = `tel:${restaurant.contact.phone}`;
                const text = link.textContent.trim();
                if (text.match(/^[\d\s\+\(\)]+$/)) {
                    link.textContent = restaurant.contact.phoneDisplay || restaurant.contact.phone;
                }
            }
        });

        // WhatsApp
        if (restaurant.contact.whatsappOrdersNumber) {
            document.querySelectorAll('a[href*="wa.me"]').forEach(link => {
                const whatsappNum = restaurant.contact.whatsappOrdersNumber.replace(/[^0-9]/g, '');
                link.href = `https://wa.me/${whatsappNum}`;
            });
        }

        // Adresses
        const addressParagraphs = document.querySelectorAll('footer p, .address, .location');
        addressParagraphs.forEach(p => {
            const text = p.textContent;
            if (text.includes('Riad City') || text.includes('Ouled Moussa')) {
                if (restaurant.location.city !== 'Ouled Moussa') {
                    p.textContent = p.textContent
                        .replace(/Riad City.*Ouled Moussa/g, restaurant.location.address)
                        .replace('Ouled Moussa', restaurant.location.city);
                }
            }
        });

        console.log('✅ Contenu commun mis à jour');
    }

    /**
     * Remplace un texte dans toute la page
     */
    function replaceTextInPage(oldText, newText) {
        if (oldText === newText) return;

        const walker = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    // Ignorer les scripts et styles
                    if (node.parentElement.tagName === 'SCRIPT' ||
                        node.parentElement.tagName === 'STYLE') {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );

        const nodesToReplace = [];
        let node;

        while (node = walker.nextNode()) {
            if (node.nodeValue && node.nodeValue.includes(oldText)) {
                nodesToReplace.push(node);
            }
        }

        nodesToReplace.forEach(node => {
            node.nodeValue = node.nodeValue.replace(new RegExp(oldText, 'g'), newText);
        });

        console.log(`🔄 Remplacé "${oldText}" par "${newText}" (${nodesToReplace.length} occurrences)`);
    }

})();
