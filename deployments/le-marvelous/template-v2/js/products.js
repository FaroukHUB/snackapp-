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
                // Platform logos - try image first, fallback to text
                const platformLogos = {
                    'uber-eats': `<img src="../images/logouber.webp" alt="Uber Eats" class="platform-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"><span class="platform-text-logo" style="display:none">Uber Eats</span>`,
                    'deliveroo': `<img src="../images/logodeliveroo.webp" alt="Deliveroo" class="platform-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"><span class="platform-text-logo" style="display:none">Deliveroo</span>`,
                    'just-eat': `<img src="../images/logojusteat.webp" alt="Just Eat" class="platform-logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"><span class="platform-text-logo" style="display:none">Just Eat</span>`
                };

                let html = platforms.map(p => {
                    const logo = platformLogos[p.id] || `<span class="platform-text-logo">${p.name}</span>`;
                    const platformClass = p.id || '';
                    return `<a href="${p.url}" target="_blank" class="platform-card platform-card--large ${platformClass}">
                        <div class="platform-logo-wrapper">${logo}</div>
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
