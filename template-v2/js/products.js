/* ============================================
   PRODUCTS.JS - Products & Formules Display
   Template V2 - SnackApp
   ============================================ */

const Products = {
    // Current modal state
    currentProduct: null,
    currentQuantity: 1,
    selectedSupplements: [],
    removedIngredients: [],
    menuType: 'solo', // 'solo' or 'menu'
    selectedDrink: null, // For menu drink selection

    /**
     * Capitalize first letter of a string
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    /**
     * Initialize products display
     */
    init() {
        this.renderHero();
        this.renderFeatured();
        this.renderSidebar();
        this.renderFormules();
        this.renderAllCategories();
        this.renderRestaurantInfo();
        this.setupModal();
        this.setupSearch();
        this.setupImageErrorHandling();
        this.applySectionBackgrounds();
    },

    /**
     * Render hero section
     */
    renderHero() {
        const heroTitle = document.getElementById('heroTitle');
        const heroSubtitle = document.getElementById('heroSubtitle');

        if (heroTitle && Config.restaurant?.name) {
            heroTitle.textContent = `Bienvenue chez ${Config.restaurant.name}`;
        }
        if (heroSubtitle && Config.restaurant?.brandTagline) {
            heroSubtitle.textContent = Config.restaurant.brandTagline;
        }
    },

    /**
     * Render featured products section
     */
    renderFeatured() {
        const grid = document.getElementById('featuredGrid');
        const section = document.getElementById('featuredSection');
        const titleEl = document.getElementById('featuredTitle');
        const subtitleEl = document.getElementById('featuredSubtitle');

        if (!grid) return;

        const featured = Config.featured;
        if (!featured?.enabled || !featured?.items?.length) {
            section?.classList.add('hidden');
            return;
        }

        // Update title and subtitle if provided
        if (titleEl && featured.title) {
            titleEl.textContent = featured.title;
        }
        if (subtitleEl && featured.subtitle) {
            subtitleEl.textContent = featured.subtitle;
        }

        // Get featured products
        const featuredProducts = featured.items
            .map(id => Config.getProduct(id))
            .filter(p => p && p.status !== 'unavailable');

        if (featuredProducts.length === 0) {
            section?.classList.add('hidden');
            return;
        }

        grid.innerHTML = featuredProducts.map(product => {
            const price = product.priceSolo || product.price || 0;
            const desc = product.description ? product.description.substring(0, 60) + (product.description.length > 60 ? '...' : '') : '';
            return `
                <div class="featured-card">
                    <div class="product-image-wrapper" onclick="Products.openProductModal('${product.id}')">
                        <img src="../${product.image}" alt="${product.name}" class="product-image"
                             onerror="this.style.display='none'">
                        ${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
                    </div>
                    <div class="product-info">
                        <h3 class="product-name" onclick="Products.openProductModal('${product.id}')">${product.name}</h3>
                        <p class="product-desc">${desc}</p>
                        <div class="product-footer">
                            <span class="product-price">${Config.formatPrice(price)}</span>
                            <button class="product-add-btn" onclick="event.stopPropagation(); Products.openProductModal('${product.id}')">Ajouter</button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Apply section alternate backgrounds using theme color
     */
    applySectionBackgrounds() {
        const primaryColor = Config.restaurant?.theme?.primary || '#e63946';
        // Create a very light tint of the primary color (5% opacity)
        const lightTint = this.hexToRgba(primaryColor, 0.03);
        document.documentElement.style.setProperty('--section-alt-bg', lightTint);
    },

    /**
     * Convert hex color to rgba
     */
    hexToRgba(hex, alpha) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        if (result) {
            const r = parseInt(result[1], 16);
            const g = parseInt(result[2], 16);
            const b = parseInt(result[3], 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        return `rgba(230, 57, 70, ${alpha})`; // fallback
    },

    /**
     * Setup global image error handling
     */
    setupImageErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                e.target.style.display = 'none';
                // Add placeholder icon
                const wrapper = e.target.closest('.product-image-wrapper, .modal-image, .formule-image');
                if (wrapper && !wrapper.querySelector('.image-placeholder')) {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'image-placeholder';
                    placeholder.innerHTML = '<i class="fas fa-utensils"></i>';
                    wrapper.style.position = 'relative';
                    wrapper.appendChild(placeholder);
                }
            }
        }, true);
    },

    /**
     * Render sidebar navigation
     */
    renderSidebar() {
        const nav = document.getElementById('categoryNav');
        if (!nav) return;

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        let html = '';

        // Add Featured link first (if enabled)
        const featured = Config.featured;
        if (featured?.enabled && featured?.items?.length > 0) {
            html += `
                <li>
                    <a href="#featuredSection" class="active">
                        <i class="fas fa-star"></i>
                        ${featured.title || 'Sélection pour vous'}
                    </a>
                </li>
            `;
        }

        // Add category links
        categories.forEach(cat => {
            const icon = icons[cat.id] || 'fa-utensils';
            // Only add if category has items
            if (cat.items && cat.items.length > 0) {
                html += `
                    <li>
                        <a href="#${cat.id}">
                            <i class="fas ${icon}"></i>
                            ${cat.name}
                        </a>
                    </li>
                `;
            }
        });

        // Add Formules link (only if formules are available)
        const formules = Config.getAvailableFormules();
        if (formules.length > 0) {
            html += `
                <li>
                    <a href="#formulesSection">
                        <i class="fas fa-fire"></i>
                        Nos Formules
                    </a>
                </li>
            `;
        }

        nav.innerHTML = html;

        // Setup scroll spy
        this.setupScrollSpy();
    },

    /**
     * Setup scroll spy for active nav highlighting
     */
    setupScrollSpy() {
        const sections = document.querySelectorAll('.product-section, .formules-section, .featured-section');
        const navLinks = document.querySelectorAll('#categoryNav a');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === `#${id}`) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }, {
            rootMargin: '-20% 0px -70% 0px'
        });

        sections.forEach(section => observer.observe(section));
    },

    /**
     * Render formules section
     */
    renderFormules() {
        const grid = document.getElementById('formulesGrid');
        const section = document.getElementById('formulesSection');
        if (!grid) return;

        const formules = Config.getAvailableFormules();

        if (formules.length === 0) {
            section?.classList.add('hidden');
            return;
        }

        grid.innerHTML = formules.map(formule => {
            const hasImage = formule.image && formule.image.trim() !== '';
            const imageHtml = hasImage
                ? `<div class="formule-image-wrapper">
                       <img src="../${formule.image}" alt="${formule.name}" class="formule-image"
                            onerror="this.parentElement.innerHTML='<div class=\\'formule-image-placeholder\\'><i class=\\'fas fa-box-open\\'></i></div>'">
                   </div>`
                : `<div class="formule-image-wrapper">
                       <div class="formule-image-placeholder"><i class="fas fa-box-open"></i></div>
                   </div>`;

            return `
                <div class="formule-card" onclick="Products.openFormuleModal('${formule.id}')">
                    ${formule.badge ? `<span class="formule-savings">${formule.badge}</span>` : ''}
                    <div class="formule-card-content">
                        ${imageHtml}
                        <div class="formule-info">
                            <h3 class="formule-name">${formule.name}</h3>
                            <p class="formule-description">${formule.description}</p>
                            <div class="formule-footer">
                                <div class="formule-price">
                                    <span class="current">${Config.formatPrice(formule.price)}</span>
                                    ${formule.originalPrice ? `<span class="original">${Config.formatPrice(formule.originalPrice)}</span>` : ''}
                                </div>
                                <button type="button" class="formule-add-btn" data-formule-id="${formule.id}"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render all product categories
     */
    renderAllCategories() {
        const container = document.getElementById('productsContainer');
        if (!container) return;

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        // Filter out empty categories
        const nonEmptyCategories = categories.filter(cat => cat.items && cat.items.length > 0);

        container.innerHTML = nonEmptyCategories.map(cat => `
            <section class="product-section" id="${cat.id}">
                <h2>
                    <i class="fas ${icons[cat.id] || 'fa-utensils'}"></i>
                    ${cat.name}
                </h2>
                <div class="products-grid">
                    ${this.renderProducts(cat.items || [], cat.id)}
                </div>
            </section>
        `).join('');
    },

    /**
     * Render products for a category
     */
    renderProducts(products, categoryId) {
        if (!products || !Array.isArray(products)) return '';
        return products.map(product => {
            const isUnavailable = product.status === 'unavailable';
            const price = product.price || product.priceSolo || 0;
            const cardClick = isUnavailable ? '' : `Products.openProductModal('${product.id}')`;

            return `
                <div class="product-card ${isUnavailable ? 'unavailable' : ''}"
                     data-product-id="${product.id}"
                     onclick="${cardClick}">
                    <div class="product-image-wrapper">
                        <img src="../${product.image}" alt="${product.name}" class="product-image"
                             onerror="this.style.display='none'">
                        ${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">${product.name}</h3>
                        <p class="product-description">${product.description || ''}</p>
                        <div class="product-footer">
                            <span class="product-price">${Config.formatPrice(price)}</span>
                            ${!isUnavailable ? `<button class="product-add-btn">Ajouter</button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render restaurant info sections
     */
    renderRestaurantInfo() {
        // Logo and name
        const logoImg = document.getElementById('logoImg');
        const logoText = document.getElementById('logoText');
        if (logoImg && Config.restaurant?.logo) {
            logoImg.src = '../' + Config.restaurant.logo;
            logoImg.alt = Config.restaurant.name;
        }
        if (logoText) {
            logoText.textContent = Config.restaurant?.name || 'Restaurant';
        }

        // Status
        const status = document.getElementById('restaurantStatus');
        if (status) {
            const isOpen = Config.isOpen();
            status.innerHTML = `
                <span class="status-dot ${isOpen ? 'open' : 'closed'}"></span>
                <span>${isOpen ? 'Ouvert' : 'Fermé'}</span>
            `;
        }

        // Address
        const address = document.getElementById('restaurantAddress');
        if (address) {
            address.innerHTML = `
                <i class="fas fa-map-marker-alt"></i>
                <span>${Config.getFullAddress()}</span>
            `;
        }

        // Contact
        const contact = document.getElementById('contactContent');
        if (contact) {
            const c = Config.getContact();
            const social = Config.getSocial();

            let html = `<div class="contact-cards">
                <div class="contact-card">
                    <div class="contact-card-icon"><i class="fas fa-phone"></i></div>
                    <div class="contact-card-info">
                        <div class="contact-card-label">Téléphone</div>
                        <div class="contact-card-value"><a href="tel:${c.phone}">${c.phoneDisplay || c.phone}</a></div>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="contact-card-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="contact-card-info">
                        <div class="contact-card-label">Adresse</div>
                        <div class="contact-card-value">${Config.getFullAddress()}</div>
                    </div>
                </div>
            </div>`;

            if (social.instagram || social.facebook || social.tiktok) {
                html += '<div class="social-links">';
                if (social.instagram) {
                    html += `<a href="${social.instagram}" target="_blank" class="social-link instagram"><i class="fab fa-instagram"></i></a>`;
                }
                if (social.facebook) {
                    html += `<a href="${social.facebook}" target="_blank" class="social-link facebook"><i class="fab fa-facebook-f"></i></a>`;
                }
                if (social.tiktok) {
                    html += `<a href="${social.tiktok}" target="_blank" class="social-link tiktok"><i class="fab fa-tiktok"></i></a>`;
                }
                html += '</div>';
            }

            contact.innerHTML = html;
        }

        // Hours
        const hours = document.getElementById('horairesContent');
        if (hours) {
            const openingHours = Config.getOpeningHours();
            const days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            const todayIndex = new Date().getDay();
            const todayName = days[todayIndex];

            hours.innerHTML = `
                <div class="hours-grid">
                    ${openingHours.map(h => {
                        let timeStr = '';
                        if (h.slots && Array.isArray(h.slots)) {
                            timeStr = h.slots.map(s => `${s.opens} - ${s.closes}`).join(' / ');
                        } else {
                            timeStr = `${h.opens} - ${h.closes}`;
                        }
                        const isToday = h.day.toLowerCase() === todayName;
                        return `
                            <div class="hours-row ${isToday ? 'today' : ''}">
                                <span class="hours-day">${this.capitalize(h.day)}</span>
                                <span class="hours-time">${timeStr}</span>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        // FAQ
        const faq = document.getElementById('faqContent');
        if (faq) {
            const faqItems = Config.getFAQ();
            if (faqItems.length > 0) {
                faq.innerHTML = faqItems.map(item => `
                    <div class="faq-item">
                        <div class="faq-question">${item.question}</div>
                        <div class="faq-answer">${item.answer}</div>
                    </div>
                `).join('');
            } else {
                document.getElementById('faq')?.classList.add('hidden');
            }
        }

        // Delivery Platforms
        const platformsGrid = document.getElementById('platformsGrid');
        if (platformsGrid) {
            const platforms = Config.getPlatforms();
            console.log('Platforms loaded:', platforms);
            // Check if platforms is an array with items
            if (Array.isArray(platforms) && platforms.length > 0) {
                // Platform logos as inline SVG for best quality
                const platformLogos = {
                    'uber-eats': `<svg viewBox="0 0 134 24" fill="currentColor" class="platform-logo"><path d="M53.67 14.17c0 2.31-1.7 4.08-4.02 4.08s-4.02-1.77-4.02-4.08c0-2.31 1.7-4.08 4.02-4.08s4.02 1.77 4.02 4.08zm-2.54 0c0-1.14-.6-1.93-1.48-1.93s-1.48.79-1.48 1.93.6 1.93 1.48 1.93 1.48-.79 1.48-1.93zm11.78-3.83h2.4v7.66h-2.4v-.89c-.6.71-1.48 1.14-2.54 1.14-2.14 0-3.88-1.77-3.88-4.08s1.74-4.08 3.88-4.08c1.06 0 1.94.43 2.54 1.14v-.89zm0 3.83c0-1.14-.6-1.93-1.56-1.93s-1.56.79-1.56 1.93.6 1.93 1.56 1.93 1.56-.79 1.56-1.93zm8.1-3.83h2.4v7.66h-2.4v-.89c-.6.71-1.48 1.14-2.54 1.14-2.14 0-3.88-1.77-3.88-4.08s1.74-4.08 3.88-4.08c1.06 0 1.94.43 2.54 1.14v-.89zm0 3.83c0-1.14-.6-1.93-1.56-1.93s-1.56.79-1.56 1.93.6 1.93 1.56 1.93 1.56-.79 1.56-1.93zm10.28.25v3.58h-2.4v-3.33c0-.89-.43-1.35-1.14-1.35-.79 0-1.31.54-1.31 1.52v3.16h-2.4v-7.66h2.4v.79c.51-.63 1.23-.97 2.14-.97 1.65 0 2.71 1.14 2.71 3.26zm8.52-4.08v7.66h-2.4v-.89c-.6.71-1.48 1.14-2.54 1.14-2.14 0-3.88-1.77-3.88-4.08s1.74-4.08 3.88-4.08c1.06 0 1.94.43 2.54 1.14v-.89h2.4zm-2.4 3.83c0-1.14-.6-1.93-1.56-1.93s-1.56.79-1.56 1.93.6 1.93 1.56 1.93 1.56-.79 1.56-1.93zm17.19 3.83h-2.74l-2.28-2.96-1.06 1.1v1.86h-2.4V5.18h2.4v8.63l3.09-3.47h2.88l-3.26 3.55 3.37 4.11zm6.52-4.33c0 .17-.02.43-.06.63h-5.18c.23.89.89 1.35 1.82 1.35.71 0 1.27-.26 1.69-.77l1.56 1.27c-.71.97-1.82 1.52-3.33 1.52-2.54 0-4.19-1.69-4.19-4.02 0-2.37 1.69-4.14 4.06-4.14 2.28 0 3.63 1.69 3.63 4.16zm-2.37-.71c-.09-.85-.63-1.44-1.44-1.44-.77 0-1.31.54-1.48 1.44h2.92zm8.04-.29v3.58h-2.4v-3.33c0-.89-.43-1.35-1.14-1.35-.79 0-1.31.54-1.31 1.52v3.16h-2.4v-7.66h2.4v.79c.51-.63 1.23-.97 2.14-.97 1.65 0 2.71 1.14 2.71 3.26zm3.26-5.62c0 .77-.63 1.4-1.4 1.4s-1.4-.63-1.4-1.4.63-1.4 1.4-1.4 1.4.63 1.4 1.4zm-2.6 2.95h2.4v7.66h-2.4v-7.66zm8.99 0h2.4v7.66h-2.4v-.89c-.6.71-1.48 1.14-2.54 1.14-2.14 0-3.88-1.77-3.88-4.08s1.74-4.08 3.88-4.08c1.06 0 1.94.43 2.54 1.14v-.89zm0 3.83c0-1.14-.6-1.93-1.56-1.93s-1.56.79-1.56 1.93.6 1.93 1.56 1.93 1.56-.79 1.56-1.93zM14.25 4.99C14.25 2.23 12.02 0 9.26 0H0v18h4.55v-4.44h4.71c2.76 0 4.99-2.23 4.99-4.99v-3.58zm-4.55 3.58c0 .61-.5 1.1-1.1 1.1H4.55V4.89h4.05c.6 0 1.1.49 1.1 1.1v2.58zm12.23 9.43c2.76 0 4.99-2.23 4.99-4.99V5H22.4v8.01c0 .61-.5 1.1-1.1 1.1h-2.86c-.61 0-1.1-.49-1.1-1.1V5h-4.55v8.01c0 2.76 2.23 4.99 4.99 4.99h4.15zm21.49-8.89c.61 0 1.1.49 1.1 1.1v7.79h4.55v-7.79c0-2.76-2.23-4.99-4.99-4.99h-4.15c-2.76 0-4.99 2.23-4.99 4.99v7.79h4.55v-7.79c0-.61.49-1.1 1.1-1.1h2.83z"/></svg>`,
                    'deliveroo': `<svg viewBox="0 0 200 40" fill="currentColor" class="platform-logo"><path d="M44.3 12.2c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5 7.5-3.3 7.5-7.5-3.4-7.6-7.5-7.6zm0 11.4c-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9 3.8 1.8 3.8 3.9-1.7 3.8-3.8 3.8zm95-11.4c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5 7.5-3.3 7.5-7.5-3.3-7.6-7.5-7.6zm0 11.4c-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9 3.8 1.8 3.8 3.9-1.7 3.8-3.8 3.8zm-69.5-11.4c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5 7.5-3.3 7.5-7.5-3.3-7.6-7.5-7.6zm0 11.4c-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9 3.8 1.8 3.8 3.9-1.7 3.8-3.8 3.8zM28.3 5.3h-3.7v21.4h3.7V5.3zm155.1 7h-3.7v14.4h3.7V12.3zM181 5.3h-3.7v21.4h3.7V5.3zM56.2 12.3h-3.7v14.4h3.7V12.3zm105.9 7c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5 7.5-3.3 7.5-7.5-3.4-7.6-7.5-7.6zm0 11.4c-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9 3.8 1.8 3.8 3.9-1.7 3.8-3.8 3.8zm-83.9-7c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5 7.5-3.3 7.5-7.5-3.3-7.6-7.5-7.6zm0 11.4c-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9 3.8 1.8 3.8 3.9-1.7 3.8-3.8 3.8zm18.9 3.6l5.4-14.4h-4l-3.4 9.5-3.5-9.5h-4l5.5 14.4h4zm20.7-14.4h-3.7v14.4h3.7V12.3zm36.9 5.5c0-3.6 2.3-5.5 5.2-5.5 2.1 0 3.6.9 4.5 2.5l-3 1.8c-.4-.7-1-1-1.6-1-1.2 0-1.9.9-1.9 2.2v.1c0 1.4.7 2.3 1.9 2.3.7 0 1.3-.4 1.7-1l3 1.7c-.9 1.6-2.5 2.6-4.6 2.6-3.1 0-5.2-2.2-5.2-5.5v-.2zm-31.9-8.5h3.7v4.1l2.9 4.1h-4.5l-2.1-3.1v-.1l-2.1 3.1h-4.5l2.9-4.1V5.3h3.7zm43.9 0h3.7v21.4h-3.7V5.3zm-106.5 0h-3.7v21.4h3.7V5.3zM15.5 12.3c-4.2 0-7.5 3.4-7.5 7.6s3.3 7.5 7.5 7.5c2.9 0 5.4-1.6 6.7-4l-3.2-1.9c-.6 1-1.8 1.7-3.1 1.7-2.1 0-3.8-1.7-3.8-3.8s1.7-3.9 3.8-3.9c1.3 0 2.4.6 3 1.5l3.2-1.8c-1.3-2.1-3.7-3.4-6.6-3.4v.5z"/></svg>`,
                    'just-eat': `<svg viewBox="0 0 120 24" fill="currentColor" class="platform-logo"><text x="0" y="18" font-family="Arial, sans-serif" font-size="16" font-weight="bold">Just Eat</text></svg>`
                };

                let html = platforms.map(p => {
                    const logo = platformLogos[p.id] || `<i class="fas fa-external-link-alt"></i>`;
                    const platformClass = p.id || '';
                    return `<a href="${p.url}" target="_blank" class="platform-card ${platformClass}">
                        <div class="platform-logo-wrapper">${logo}</div>
                        <span class="platform-name">${p.name}</span>
                        <span class="platform-cta">Commander <i class="fas fa-arrow-right"></i></span>
                    </a>`;
                }).join('');

                platformsGrid.innerHTML = html;
            } else {
                // Hide the section and sidebar link
                document.getElementById('commander')?.classList.add('hidden');
                const commanderSection = document.getElementById('commander');
                if (commanderSection) commanderSection.style.display = 'none';
                const sidebarLink = document.getElementById('sidebarPlatforms');
                if (sidebarLink) sidebarLink.style.display = 'none';
            }
        }

        // Google Maps
        const mapContainer = document.getElementById('mapContainer');
        const directionsBtn = document.getElementById('directionsBtn');
        if (mapContainer) {
            const fullAddress = Config.getFullAddress();
            if (fullAddress) {
                const encodedAddress = encodeURIComponent(fullAddress);
                mapContainer.innerHTML = `
                    <iframe
                        src="https://www.google.com/maps?q=${encodedAddress}&output=embed"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                `;
                // Set directions button link (opens in Google Maps / GPS)
                if (directionsBtn) {
                    directionsBtn.href = `https://www.google.com/maps/dir/?api=1&destination=${encodedAddress}`;
                }
            } else {
                document.getElementById('localisation')?.classList.add('hidden');
                document.getElementById('localisation').style.display = 'none';
            }
        }
    },

    /**
     * Setup product modal
     */
    setupModal() {
        const modal = document.getElementById('productModal');
        const backdrop = modal?.querySelector('.modal-backdrop');
        const closeBtn = document.getElementById('modalClose');

        backdrop?.addEventListener('click', () => this.closeModal());
        closeBtn?.addEventListener('click', () => this.closeModal());

        // Quantity buttons
        document.getElementById('qtyMinus')?.addEventListener('click', () => {
            if (this.currentQuantity > 1) {
                this.currentQuantity--;
                this.updateModalUI();
            }
        });

        document.getElementById('qtyPlus')?.addEventListener('click', () => {
            this.currentQuantity++;
            this.updateModalUI();
        });

        // Add to cart button
        document.getElementById('addToCartBtn')?.addEventListener('click', () => {
            this.addCurrentToCart();
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });

        // Formule add button click (event delegation)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.formule-add-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                const formuleId = btn.dataset.formuleId;
                if (formuleId) {
                    this.openFormuleModal(formuleId);
                }
            }
        });
    },

    /**
     * Open product modal
     */
    openProductModal(productId) {
        console.log('Opening modal for product:', productId);
        const product = Config.getProduct(productId);
        console.log('Product found:', product);
        if (!product) return;

        // Reset state
        this.currentProduct = product;
        this.currentQuantity = 1;
        this.selectedSupplements = [];
        this.removedIngredients = [];
        this.menuType = 'solo';
        this.selectedDrink = null;

        const modal = document.getElementById('productModal');

        // Update modal content
        const imgEl = document.getElementById('modalImage');
        if (product.image) {
            imgEl.src = '../' + product.image;
            imgEl.style.display = '';
        } else {
            imgEl.src = '';
            imgEl.style.display = 'none';
        }
        document.getElementById('modalTitle').textContent = product.name;
        document.getElementById('modalDescription').textContent = product.description || '';

        // Menu/Solo toggle
        const menuToggleSection = document.getElementById('menuToggleSection');
        const hasMenuOption = product.priceMenu && product.priceMenu > 0;

        if (hasMenuOption) {
            menuToggleSection.classList.remove('hidden');
            menuToggleSection.style.display = '';
            document.getElementById('priceSolo').textContent = Config.formatPrice(product.priceSolo || 0);
            document.getElementById('priceMenu').textContent = Config.formatPrice(product.priceMenu || 0);
            // Reset toggle to solo
            document.querySelectorAll('.menu-option').forEach(opt => {
                opt.classList.toggle('active', opt.dataset.type === 'solo');
            });
        } else {
            menuToggleSection.classList.add('hidden');
            menuToggleSection.style.display = 'none';
        }

        // Ingredients to remove
        const ingredientsSection = document.getElementById('modalIngredients');
        const ingredientsList = document.getElementById('ingredientsList');
        const hasIngredients = product.baseIngredients && product.baseIngredients.length > 0;

        if (hasIngredients) {
            ingredientsSection.classList.remove('hidden');
            ingredientsSection.style.display = '';
            ingredientsList.innerHTML = product.baseIngredients.map(ing => `
                <div class="ingredient-item" data-ingredient="${ing}" onclick="Products.toggleIngredient('${ing}')">
                    <i class="fas fa-times"></i>
                    <span>${this.capitalize(ing)}</span>
                </div>
            `).join('');
        } else {
            ingredientsSection.classList.add('hidden');
            ingredientsSection.style.display = 'none';
        }

        // Render supplements
        const supplements = Config.getSupplementsForCategory(product.categoryId);
        const supplementsContainer = document.getElementById('modalSupplements');
        const supplementsList = document.getElementById('supplementsList');

        if (supplements.length > 0) {
            supplementsContainer.classList.remove('hidden');
            supplementsContainer.style.display = '';
            supplementsList.innerHTML = supplements.map(sup => `
                <div class="supplement-item" data-id="${sup.id}" onclick="Products.toggleSupplement('${sup.id}')">
                    <div class="supplement-info">
                        <div class="supplement-checkbox">
                            <i class="fas fa-check" style="font-size: 12px;"></i>
                        </div>
                        <span class="supplement-name">${sup.name}</span>
                    </div>
                    <span class="supplement-price">+${Config.formatPrice(sup.price)}</span>
                </div>
            `).join('');
        } else {
            supplementsContainer.classList.add('hidden');
            supplementsContainer.style.display = 'none';
        }

        // Render drinks selection (for menu option)
        const drinksContainer = document.getElementById('modalDrinks');
        const drinksList = document.getElementById('drinksList');
        const drinks = Config.getDrinks();

        if (hasMenuOption && drinks.length > 0) {
            drinksList.innerHTML = drinks.map(drink => `
                <div class="drink-item" data-id="${drink.id}" onclick="Products.selectDrink('${drink.id}')">
                    ${drink.name}
                </div>
            `).join('');
            // Hide by default (shown when menu is selected)
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';
        } else {
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';
        }

        this.updateModalUI();
        console.log('Modal element:', modal);
        modal.classList.add('active');
        console.log('Modal classes after active:', modal?.className);
        document.body.style.overflow = 'hidden';
    },

    /**
     * Select a drink for menu
     */
    selectDrink(drinkId) {
        const drinks = Config.getDrinks();
        const drink = drinks.find(d => d.id === drinkId);
        this.selectedDrink = drink || null;

        // Update UI
        document.querySelectorAll('.drink-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === drinkId);
        });
    },

    /**
     * Set menu type (solo or menu)
     */
    setMenuType(type) {
        this.menuType = type;

        // Show/hide drinks section
        const drinksContainer = document.getElementById('modalDrinks');
        if (type === 'menu') {
            drinksContainer.classList.remove('hidden');
            drinksContainer.style.display = '';
        } else {
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';
            this.selectedDrink = null;
            document.querySelectorAll('.drink-item').forEach(item => {
                item.classList.remove('selected');
            });
        }

        document.querySelectorAll('.menu-option').forEach(opt => {
            opt.classList.toggle('active', opt.dataset.type === type);
        });
        this.updateModalUI();
    },

    /**
     * Toggle ingredient removal
     */
    toggleIngredient(ingredient) {
        const index = this.removedIngredients.indexOf(ingredient);
        if (index >= 0) {
            this.removedIngredients.splice(index, 1);
        } else {
            this.removedIngredients.push(ingredient);
        }

        // Update UI
        document.querySelectorAll('.ingredient-item').forEach(item => {
            const ing = item.dataset.ingredient;
            item.classList.toggle('removed', this.removedIngredients.includes(ing));
        });
    },

    /**
     * Open formule modal (simplified for now)
     */
    openFormuleModal(formuleId) {
        const formule = Config.getFormule(formuleId);
        if (!formule) return;

        // For now, treat formule like a product with fixed price
        this.currentProduct = {
            ...formule,
            price: formule.price,
            priceSolo: formule.price,
            priceMenu: formule.price,
            categoryId: 'formules',
            isFormule: true
        };
        this.currentQuantity = 1;
        this.selectedSupplements = [];
        this.removedIngredients = [];
        this.menuType = 'solo';

        const modal = document.getElementById('productModal');

        // Set image with fallback
        const modalImage = document.getElementById('modalImage');
        if (formule.image) {
            modalImage.src = '../' + formule.image;
            modalImage.onerror = () => { modalImage.style.display = 'none'; };
        } else {
            modalImage.style.display = 'none';
        }

        document.getElementById('modalTitle').textContent = formule.name;
        document.getElementById('modalDescription').textContent = formule.description;

        // Hide menu toggle for formules (fixed price)
        document.getElementById('menuToggleSection').style.display = 'none';

        // Hide ingredients section for formules
        document.getElementById('modalIngredients').classList.add('hidden');

        // Hide supplements for formules
        document.getElementById('modalSupplements').classList.add('hidden');

        // Hide drinks for formules
        document.getElementById('modalDrinks').classList.add('hidden');

        // Set price
        document.getElementById('addToCartPrice').textContent = Config.formatPrice(formule.price);

        this.updateModalUI();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /**
     * Toggle supplement selection
     */
    toggleSupplement(supId) {
        const sup = Config.supplements.catalog?.[supId];
        if (!sup) return;

        const index = this.selectedSupplements.findIndex(s => s.id === supId);
        if (index >= 0) {
            this.selectedSupplements.splice(index, 1);
        } else {
            this.selectedSupplements.push(sup);
        }

        // Update UI
        document.querySelectorAll('.supplement-item').forEach(item => {
            const id = item.dataset.id;
            if (this.selectedSupplements.find(s => s.id === id)) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });

        this.updateModalUI();
    },

    /**
     * Update modal UI (quantity, total price)
     */
    updateModalUI() {
        document.getElementById('qtyValue').textContent = this.currentQuantity;

        // Calculate total based on menu type
        let basePrice = 0;
        if (this.menuType === 'menu' && this.currentProduct?.priceMenu) {
            basePrice = this.currentProduct.priceMenu;
        } else {
            basePrice = this.currentProduct?.price || this.currentProduct?.priceSolo || 0;
        }

        let total = basePrice;

        // Add supplements
        this.selectedSupplements.forEach(sup => {
            total += sup.price || 0;
        });

        total *= this.currentQuantity;

        document.getElementById('addToCartPrice').textContent = Config.formatPrice(total);
    },

    /**
     * Add current modal product to cart
     */
    addCurrentToCart() {
        if (!this.currentProduct) return;

        // Create product with correct price based on menu type
        const productToAdd = { ...this.currentProduct };

        if (this.menuType === 'menu' && this.currentProduct.priceMenu) {
            productToAdd.price = this.currentProduct.priceMenu;
            productToAdd.name = this.currentProduct.name + ' (Menu)';
        } else {
            productToAdd.price = this.currentProduct.priceSolo || this.currentProduct.price;
        }

        Cart.addItem(
            productToAdd,
            this.currentQuantity,
            [...this.selectedSupplements],
            {
                menuType: this.menuType,
                removedIngredients: [...this.removedIngredients],
                selectedDrink: this.selectedDrink ? { ...this.selectedDrink } : null
            }
        );

        this.closeModal();
    },

    /**
     * Quick add product without modal (no supplements)
     */
    quickAdd(productId) {
        const product = Config.getProduct(productId);
        if (!product) return;

        Cart.addItem(product, 1, [], {});
    },

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('productModal');
        modal?.classList.remove('active');
        document.body.style.overflow = '';
        this.currentProduct = null;
    },

    /**
     * Setup search functionality
     */
    setupSearch() {
        const input = document.getElementById('searchInput');
        if (!input) return;

        let debounceTimer;
        input.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.filterProducts(e.target.value);
            }, 300);
        });
    },

    /**
     * Filter products by search term
     */
    filterProducts(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const cards = document.querySelectorAll('.product-card');

        cards.forEach(card => {
            const name = card.querySelector('.product-name')?.textContent.toLowerCase() || '';
            const desc = card.querySelector('.product-description')?.textContent.toLowerCase() || '';

            if (!term || name.includes(term) || desc.includes(term)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Hide empty sections
        document.querySelectorAll('.product-section').forEach(section => {
            const visibleCards = section.querySelectorAll('.product-card:not([style*="display: none"])');
            section.style.display = visibleCards.length > 0 ? '' : 'none';
        });
    },

    /**
     * Helper: Capitalize first letter
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }
};

// Export for use in other modules
window.Products = Products;
