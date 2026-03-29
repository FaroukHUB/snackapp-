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

        // Vérifier et masquer les avis non pertinents
        hideWrongReviews();

        // Alt de l'image hero
        const heroImage = document.getElementById('heroImage');
        if (heroImage) {
            heroImage.alt = `${restaurant.name} ${restaurant.brandTagline || ''}`;
        }
    }

    /**
     * Masquer les avis qui ne correspondent pas à ce restaurant
     * Vérifie si les avis mentionnent une ville/adresse différente
     */
    function hideWrongReviews() {
        console.log('🔍 [Dynamic Content] Vérification des avis...');

        const reviewsSection = document.querySelector('.reviews-section');
        if (!reviewsSection) {
            console.log('ℹ️ Pas de section avis trouvée');
            return;
        }

        const reviewCards = document.querySelectorAll('.review-card');
        if (reviewCards.length === 0) {
            console.log('ℹ️ Aucun avis trouvé');
            return;
        }

        let hiddenCount = 0;
        const currentCity = restaurant.location.city;

        reviewCards.forEach(card => {
            const text = card.textContent || '';

            // Vérifier si l'avis mentionne une ville différente
            // (Liste des villes communes à vérifier)
            const cityMentions = ['Ouled Moussa', 'Lille', 'Paris', 'Lyon', 'Marseille'];

            for (const city of cityMentions) {
                if (text.includes(city) && currentCity !== city) {
                    card.style.display = 'none';
                    hiddenCount++;
                    console.log(`❌ Avis caché (mentionne ${city} mais restaurant à ${currentCity})`);
                    break;
                }
            }
        });

        // Si tous les avis sont cachés, masquer toute la section
        if (hiddenCount > 0 && hiddenCount === reviewCards.length) {
            reviewsSection.style.display = 'none';
            console.log('❌ Section avis entièrement cachée (aucun avis pertinent)');
        }

        console.log(`✅ ${hiddenCount} avis cachés sur ${reviewCards.length}`);
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

        // Remplacer le système de points
        if (restaurant.loyalty) {
            updateLoyaltySystem();
        }

        console.log('✅ Page Fidélité mise à jour');
    }

    /**
     * Met à jour les horaires d'ouverture dynamiquement
     */
    function updateOpeningHours() {
        console.log('🕐 [Dynamic Content] Mise à jour des horaires');

        const hoursList = document.querySelector('.hours-list');
        if (!hoursList || !restaurant.openingHours) {
            console.log('ℹ️ Pas de liste d\'horaires trouvée');
            return;
        }

        // Grouper les jours avec les mêmes horaires
        const groups = [];
        let currentGroup = null;

        restaurant.openingHours.forEach((h, idx) => {
            if (h.isClosed) {
                // Ajouter le groupe précédent s'il existe
                if (currentGroup) {
                    groups.push(currentGroup);
                    currentGroup = null;
                }
                // Ajouter le jour fermé
                groups.push({ days: [h.day], hours: 'Fermé', isClosed: true });
            } else {
                const hours = `${h.opens} - ${h.closes}`;
                if (currentGroup && currentGroup.hours === hours) {
                    // Ajouter le jour au groupe actuel
                    currentGroup.days.push(h.day);
                } else {
                    // Nouveau groupe
                    if (currentGroup) {
                        groups.push(currentGroup);
                    }
                    currentGroup = { days: [h.day], hours: hours, isClosed: false };
                }
            }
        });

        // Ajouter le dernier groupe
        if (currentGroup) {
            groups.push(currentGroup);
        }

        // Reconstruire le HTML
        hoursList.innerHTML = groups.map(g => {
            const dayRange = g.days.length > 1 ? `${g.days[0]} - ${g.days[g.days.length - 1]}` : g.days[0];
            return `<li><span>${dayRange}</span> <span>${g.hours}</span></li>`;
        }).join('');

        console.log('✅ Horaires mis à jour');
    }

    /**
     * Met à jour l'adresse dynamiquement
     */
    function updateAddress() {
        console.log('📍 [Dynamic Content] Mise à jour de l\'adresse');

        const addressCard = document.querySelector('.info-card h3');
        if (!addressCard || addressCard.textContent !== 'Adresse') {
            console.log('ℹ️ Carte adresse non trouvée');
            return;
        }

        const addressP = addressCard.parentElement.querySelector('p');
        if (!addressP) {
            console.log('ℹ️ Paragraphe adresse non trouvé');
            return;
        }

        const address = restaurant.location.address || '';
        const postalCode = restaurant.location.postalCode || '';
        const city = restaurant.location.city || '';

        addressP.innerHTML = `${address}<br>${postalCode} ${city}`;

        console.log('✅ Adresse mise à jour');
    }

    /**
     * Met à jour les informations de contact
     */
    function updateContactInfo() {
        console.log('📞 [Dynamic Content] Mise à jour du contact');

        const contactCards = document.querySelectorAll('.info-card h3');
        let contactCard = null;

        contactCards.forEach(h3 => {
            if (h3.textContent === 'Contact') {
                contactCard = h3.parentElement;
            }
        });

        if (!contactCard) {
            console.log('ℹ️ Carte contact non trouvée');
            return;
        }

        const contactP = contactCard.querySelector('p');
        if (contactP) {
            const phoneDisplay = restaurant.contact.phoneDisplay || restaurant.contact.phone;
            contactP.innerHTML = phoneDisplay + '<br>' + (contactP.querySelector('small')?.outerHTML || '<small>En cas de question sur votre commande</small>');
        }

        console.log('✅ Contact mis à jour');
    }

    /**
     * Met à jour le système de points de fidélité
     */
    function updateLoyaltySystem() {
        console.log('💳 [Dynamic Content] Mise à jour du système de fidélité');

        if (!restaurant.loyalty || !restaurant._jsConfig) {
            console.log('ℹ️ Données de fidélité non disponibles');
            return;
        }

        const currency = restaurant._jsConfig.currency || 'EUR';
        const currencyUnit = restaurant.loyalty.currencyUnit || 100;
        const pointsPerUnit = restaurant.loyalty.pointsPerCurrencyUnit || 1;

        // Mettre à jour la visualisation des points (100 DA = 1 POINT)
        const pointsVisual = document.querySelector('.points-visual');
        if (pointsVisual) {
            const pointsValue = pointsVisual.querySelector('.points-value');
            const pointsValueAccent = pointsVisual.querySelectorAll('.points-value')[1];

            if (pointsValue) {
                pointsValue.textContent = `${currencyUnit} ${currency}`;
            }

            if (pointsValueAccent) {
                pointsValueAccent.textContent = pointsPerUnit;
            }
        }

        // Mettre à jour l'exemple (1500 DA = 15 points)
        const exampleP = document.querySelector('.points-example');
        if (exampleP) {
            const exampleAmount = currencyUnit * 15; // 15 unités
            const examplePoints = pointsPerUnit * 15; // 15 points
            exampleP.innerHTML = `<i class="fas fa-lightbulb"></i> Exemple : Une commande de <strong>${exampleAmount} ${currency}</strong> = <strong>${examplePoints} points</strong> cumulés !`;
        }

        // Mettre à jour le texte "Gagnez X points par euro dépensé"
        const cumulezText = document.querySelector('.step-card h3');
        if (cumulezText && cumulezText.textContent === 'Cumulez') {
            const cumulezP = cumulezText.nextElementSibling;
            if (cumulezP) {
                cumulezP.innerHTML = `Gagnez <strong>${pointsPerUnit} point${pointsPerUnit > 1 ? 's' : ''}</strong> par ${currencyUnit} ${currency} dépensé${currencyUnit > 1 ? 's' : ''}`;
            }
        }

        // Mettre à jour les récompenses
        if (restaurant.loyalty.rewards && restaurant.loyalty.rewards.length > 0) {
            updateLoyaltyRewards();
        }

        console.log('✅ Système de fidélité mis à jour');
    }

    /**
     * Met à jour les récompenses de fidélité
     */
    function updateLoyaltyRewards() {
        console.log('🎁 [Dynamic Content] Mise à jour des récompenses');

        const rewardsGrid = document.querySelector('.rewards-grid');
        if (!rewardsGrid) {
            console.log('ℹ️ Grille de récompenses non trouvée');
            return;
        }

        // Reconstruire la grille des récompenses
        rewardsGrid.innerHTML = restaurant.loyalty.rewards.map((reward, idx) => {
            const featured = idx === 2 ? 'featured' : ''; // Le 3ème élément est "featured"
            const icon = reward.icon || 'fa-gift';

            return `
                <div class="reward-card ${featured}">
                    <div class="reward-badge">${reward.pointsRequired} pts</div>
                    <div class="reward-icon"><i class="fas ${icon}"></i></div>
                    <h3>${reward.name}</h3>
                    <p>${reward.description}</p>
                </div>
            `;
        }).join('');

        console.log('✅ Récompenses mises à jour');
    }

    /**
     * Met à jour la page Click & Collect
     */
    function updateClickCollectPage() {
        console.log('🛍️ [Dynamic Content] Mise à jour page Click & Collect');

        replaceTextInPage('Le Marvelous', restaurant.name);
        replaceTextInPage('Marvelous', restaurant.name);
        replaceTextInPage('Fabrik Burger', restaurant.name);

        // Remplacer les horaires
        if (restaurant.openingHours && restaurant.openingHours.length > 0) {
            updateOpeningHours();
        }

        // Remplacer l'adresse
        if (restaurant.location) {
            updateAddress();
        }

        // Remplacer le téléphone
        if (restaurant.contact && restaurant.contact.phone) {
            updateContactInfo();
        }

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
