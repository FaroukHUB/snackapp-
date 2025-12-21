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
    menuType: 'solo',
    selectedDrink: null,
    activeCategory: 'all',

    /**
     * Capitalize first letter
     */
    capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    },

    /**
     * Initialize products display
     */
    init() {
        this.renderHeaderNav();
        this.renderMobileMenuNav();
        this.renderFeatured();
        this.renderCategoryTabs();
        this.renderProductsGrid();
        this.renderRestaurantInfo();
        this.setupModal();
        this.setupSearch();
        this.setupCategoryFilter();
        this.setupImageErrorHandling();
    },

    /**
     * Render header navigation (desktop)
     */
    renderHeaderNav() {
        const nav = document.getElementById('headerNav');
        if (!nav) return;

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        let html = categories.slice(0, 5).map(cat => {
            const icon = icons[cat.id] || 'fa-utensils';
            return `
                <a href="#menuSection" data-category="${cat.id}">
                    <i class="fas ${icon}"></i>
                    <span>${cat.name}</span>
                </a>
            `;
        }).join('');

        nav.innerHTML = html;

        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const catId = link.dataset.category;
                this.filterByCategory(catId);
                document.getElementById('menuSection')?.scrollIntoView({ behavior: 'smooth' });
            });
        });
    },

    /**
     * Render mobile menu navigation
     */
    renderMobileMenuNav() {
        const nav = document.getElementById('mobileMenuNav');
        if (!nav) return;

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        let html = categories.map(cat => {
            const icon = icons[cat.id] || 'fa-utensils';
            return `
                <a href="#menuSection" data-category="${cat.id}">
                    <i class="fas ${icon}"></i>
                    ${cat.name}
                </a>
            `;
        }).join('');

        nav.innerHTML = html;

        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const catId = link.dataset.category;
                this.filterByCategory(catId);
                document.getElementById('mobileMenu')?.classList.remove('active');
                document.getElementById('mobileMenuOverlay')?.classList.remove('active');
                document.body.style.overflow = '';
                setTimeout(() => {
                    document.getElementById('menuSection')?.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            });
        });
    },

    /**
     * Render featured products section
     */
    renderFeatured() {
        const grid = document.getElementById('featuredGrid');
        const section = document.getElementById('featuredSection');
        const titleEl = document.getElementById('featuredTitle');

        if (!grid || !section) return;

        const featured = Config.getFeatured();

        if (!featured || !featured.enabled || !featured.items || featured.items.length === 0) {
            section.style.display = 'none';
            return;
        }

        if (titleEl && featured.title) {
            titleEl.textContent = featured.title;
        }

        const featuredProducts = featured.items
            .map(id => Config.getProduct(id))
            .filter(p => p && p.status !== 'unavailable');

        if (featuredProducts.length === 0) {
            section.style.display = 'none';
            return;
        }

        grid.innerHTML = featuredProducts.map(product => {
            const hasImage = product.image && product.image.trim() !== '';
            const price = product.priceSolo || product.price || 0;
            const shortDesc = product.description ? product.description.substring(0, 60) + '...' : '';

            return `
                <div class="product-card" onclick="Products.openProductModal('${product.id}')">
                    <div class="product-image">
                        ${hasImage
                            ? `<img src="../${product.image}" alt="${product.name}" onerror="this.parentElement.innerHTML='<div class=\\'image-placeholder\\'><i class=\\'fas fa-utensils\\'></i></div>'">`
                            : '<div class="image-placeholder"><i class="fas fa-utensils"></i></div>'}
                    </div>
                    <div class="product-info">
                        <div class="product-name">${product.name}</div>
                        <div class="product-desc">${shortDesc}</div>
                        <div class="product-footer">
                            <div class="product-price">
                                ${Config.formatPrice(price)}
                                ${product.priceMenu ? `<small>Menu ${Config.formatPrice(product.priceMenu)}</small>` : ''}
                            </div>
                            <button class="add-btn" onclick="event.stopPropagation(); Products.openProductModal('${product.id}')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    ${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
                </div>
            `;
        }).join('');
    },

    /**
     * Render category tabs
     */
    renderCategoryTabs() {
        const container = document.getElementById('categoryTabs');
        if (!container) return;

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        let html = `<button class="category-tab active" data-category="all">
            <i class="fas fa-th-large"></i> Tous
        </button>`;

        html += categories.map(cat => {
            const icon = icons[cat.id] || 'fa-utensils';
            return `<button class="category-tab" data-category="${cat.id}">
                <i class="fas ${icon}"></i> ${cat.name}
            </button>`;
        }).join('');

        container.innerHTML = html;
    },

    /**
     * Setup category filter clicks
     */
    setupCategoryFilter() {
        const container = document.getElementById('categoryTabs');
        if (!container) return;

        container.addEventListener('click', (e) => {
            const tab = e.target.closest('.category-tab');
            if (!tab) return;
            const category = tab.dataset.category;
            this.filterByCategory(category);
        });
    },

    /**
     * Filter products by category
     */
    filterByCategory(categoryId) {
        this.activeCategory = categoryId;

        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.category === categoryId);
        });

        const cards = document.querySelectorAll('#productsGrid .product-card');
        cards.forEach(card => {
            const cardCategory = card.dataset.category;
            if (categoryId === 'all' || cardCategory === categoryId) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    },

    /**
     * Render all products in grid
     */
    renderProductsGrid() {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        const categories = Config.getCategories();
        let allProducts = [];

        categories.forEach(cat => {
            if (cat.items && Array.isArray(cat.items)) {
                cat.items.forEach(product => {
                    allProducts.push({ ...product, categoryId: cat.id });
                });
            }
        });

        grid.innerHTML = allProducts.map(product => {
            const isUnavailable = product.status === 'unavailable';
            const hasImage = product.image && product.image.trim() !== '';
            const cardClick = isUnavailable ? '' : `Products.openProductModal('${product.id}')`;
            const price = product.priceSolo || product.price || 0;
            const shortDesc = product.description ? product.description.substring(0, 50) + '...' : '';

            return `
                <div class="product-card ${isUnavailable ? 'unavailable' : ''}"
                     data-product-id="${product.id}"
                     data-category="${product.categoryId}"
                     onclick="${cardClick}"
                     style="position: relative;">
                    <div class="product-image">
                        ${hasImage
                            ? `<img src="../${product.image}" alt="${product.name}" onerror="this.parentElement.innerHTML='<div class=\\'image-placeholder\\'><i class=\\'fas fa-utensils\\'></i></div>'">`
                            : '<div class="image-placeholder"><i class="fas fa-utensils"></i></div>'}
                    </div>
                    <div class="product-info">
                        <div class="product-name">${product.name}</div>
                        <div class="product-desc">${shortDesc}</div>
                        <div class="product-footer">
                            <div class="product-price">
                                ${Config.formatPrice(price)}
                                ${product.priceMenu ? `<small>Menu ${Config.formatPrice(product.priceMenu)}</small>` : ''}
                            </div>
                            ${!isUnavailable ? `<button class="add-btn" onclick="event.stopPropagation(); Products.openProductModal('${product.id}')"><i class="fas fa-plus"></i></button>` : ''}
                        </div>
                    </div>
                    ${product.badge ? `<span class="product-badge">${product.badge}</span>` : ''}
                </div>
            `;
        }).join('');
    },

    /**
     * Setup global image error handling
     */
    setupImageErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                e.target.style.display = 'none';
                const wrapper = e.target.closest('.product-image, .modal-image');
                if (wrapper && !wrapper.querySelector('.image-placeholder')) {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'image-placeholder';
                    placeholder.innerHTML = '<i class="fas fa-utensils"></i>';
                    wrapper.appendChild(placeholder);
                }
            }
        }, true);
    },

    /**
     * Render restaurant info sections
     */
    renderRestaurantInfo() {
        // Logo and name
        const logoImg = document.getElementById('logoImg');
        const logoText = document.getElementById('logoText');
        const footerLogo = document.getElementById('footerLogo');

        if (logoImg && Config.restaurant?.logo) {
            logoImg.src = '../' + Config.restaurant.logo;
            logoImg.alt = Config.restaurant.name;
        }
        if (logoText) {
            logoText.textContent = Config.restaurant?.name || 'Restaurant';
        }
        if (footerLogo) {
            footerLogo.textContent = Config.restaurant?.name || 'Restaurant';
        }

        // Hero content
        const heroTitle = document.getElementById('heroTitle');
        const heroSubtitle = document.getElementById('heroSubtitle');
        if (heroTitle && Config.restaurant?.heroTitle) {
            heroTitle.innerHTML = Config.restaurant.heroTitle;
        }
        if (heroSubtitle && Config.restaurant?.heroSubtitle) {
            heroSubtitle.textContent = Config.restaurant.heroSubtitle;
        }

        // Hours
        const hours = document.getElementById('horairesContent');
        if (hours) {
            const openingHours = Config.getOpeningHours();
            const days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            const todayIndex = new Date().getDay();
            const todayName = days[todayIndex];

            hours.innerHTML = openingHours.map(h => {
                let timeStr = '';
                if (h.slots && Array.isArray(h.slots)) {
                    timeStr = h.slots.map(s => `${s.opens} - ${s.closes}`).join(' / ');
                } else {
                    timeStr = `${h.opens} - ${h.closes}`;
                }
                const isToday = h.day.toLowerCase() === todayName;
                return `<div style="display: flex; justify-content: space-between; padding: 5px 0; ${isToday ? 'font-weight: 700; color: var(--primary);' : ''}">
                    <span>${this.capitalize(h.day)}</span>
                    <span>${timeStr}</span>
                </div>`;
            }).join('');
        }

        // Contact
        const contact = document.getElementById('contactContent');
        if (contact) {
            const c = Config.getContact();
            contact.innerHTML = `
                <p><a href="tel:${c.phone}" style="color: inherit; text-decoration: none;">${c.phoneDisplay || c.phone}</a></p>
            `;
        }

        // Location
        const location = document.getElementById('locationContent');
        if (location) {
            const fullAddress = Config.getFullAddress();
            location.innerHTML = `<p>${fullAddress}</p>`;
        }

        // Delivery Platforms
        const platformsGrid = document.getElementById('platformsGrid');
        const platformsSection = document.getElementById('platformsSection');
        if (platformsGrid) {
            const platforms = Config.getPlatforms();
            if (Array.isArray(platforms) && platforms.length > 0) {
                const platformIcons = {
                    'uber-eats': { icon: 'fa-shopping-bag', class: 'uber-eats' },
                    'deliveroo': { icon: 'fa-bicycle', class: 'deliveroo' },
                    'just-eat': { icon: 'fa-utensils', class: 'just-eat' }
                };

                platformsGrid.innerHTML = platforms.map(p => {
                    const config = platformIcons[p.id] || { icon: 'fa-external-link-alt', class: '' };
                    return `<a href="${p.url}" target="_blank" class="platform-btn ${config.class}">
                        <i class="fas ${config.icon}"></i> ${p.name}
                    </a>`;
                }).join('');
            } else if (platformsSection) {
                platformsSection.style.display = 'none';
            }
        }

        // Google Maps
        const mapContainer = document.getElementById('mapContainer');
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
            } else {
                mapContainer.style.display = 'none';
            }
        }

        // Footer Social
        const footerSocial = document.getElementById('footerSocial');
        if (footerSocial) {
            const social = Config.getSocial();
            let html = '';
            if (social.instagram) {
                html += `<a href="${social.instagram}" target="_blank"><i class="fab fa-instagram"></i></a>`;
            }
            if (social.facebook) {
                html += `<a href="${social.facebook}" target="_blank"><i class="fab fa-facebook-f"></i></a>`;
            }
            if (social.tiktok) {
                html += `<a href="${social.tiktok}" target="_blank"><i class="fab fa-tiktok"></i></a>`;
            }
            footerSocial.innerHTML = html;
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

        document.getElementById('addToCartBtn')?.addEventListener('click', () => {
            this.addCurrentToCart();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });
    },

    /**
     * Open product modal
     */
    openProductModal(productId) {
        const product = Config.getProduct(productId);
        if (!product) return;

        this.currentProduct = product;
        this.currentQuantity = 1;
        this.selectedSupplements = [];
        this.removedIngredients = [];
        this.menuType = 'solo';
        this.selectedDrink = null;

        const modal = document.getElementById('productModal');

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

        // Supplements
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

        // Drinks
        const drinksContainer = document.getElementById('modalDrinks');
        const drinksList = document.getElementById('drinksList');
        const drinks = Config.getDrinks();

        if (hasMenuOption && drinks.length > 0) {
            drinksList.innerHTML = drinks.map(drink => `
                <div class="drink-item" data-id="${drink.id}" onclick="Products.selectDrink('${drink.id}')">
                    ${drink.name}
                </div>
            `).join('');
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';
        } else {
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';
        }

        this.updateModalUI();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /**
     * Select a drink for menu
     */
    selectDrink(drinkId) {
        const drinks = Config.getDrinks();
        const drink = drinks.find(d => d.id === drinkId);
        this.selectedDrink = drink || null;

        document.querySelectorAll('.drink-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === drinkId);
        });
    },

    /**
     * Set menu type (solo or menu)
     */
    setMenuType(type) {
        this.menuType = type;

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

        document.querySelectorAll('.ingredient-item').forEach(item => {
            const ing = item.dataset.ingredient;
            item.classList.toggle('removed', this.removedIngredients.includes(ing));
        });
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
     * Update modal UI
     */
    updateModalUI() {
        document.getElementById('qtyValue').textContent = this.currentQuantity;

        let basePrice = 0;
        if (this.menuType === 'menu' && this.currentProduct?.priceMenu) {
            basePrice = this.currentProduct.priceMenu;
        } else {
            basePrice = this.currentProduct?.price || this.currentProduct?.priceSolo || 0;
        }

        let total = basePrice;
        this.selectedSupplements.forEach(sup => {
            total += sup.price || 0;
        });
        total *= this.currentQuantity;

        document.getElementById('addToCartPrice').textContent = Config.formatPrice(total);
    },

    /**
     * Add current product to cart
     */
    addCurrentToCart() {
        if (!this.currentProduct) return;

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
        const toggle = document.getElementById('searchToggle');
        const container = document.getElementById('searchBarContainer');
        const closeBtn = document.getElementById('searchClose');
        const input = document.getElementById('searchInput');

        toggle?.addEventListener('click', () => {
            container?.classList.toggle('active');
            if (container?.classList.contains('active')) {
                input?.focus();
            }
        });

        closeBtn?.addEventListener('click', () => {
            container?.classList.remove('active');
            if (input) input.value = '';
            this.filterProducts('');
        });

        if (input) {
            let debounceTimer;
            input.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.filterProducts(e.target.value);
                }, 300);
            });
        }
    },

    /**
     * Filter products by search term
     */
    filterProducts(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        const cards = document.querySelectorAll('#productsGrid .product-card, #featuredGrid .product-card');

        cards.forEach(card => {
            const name = card.querySelector('.product-name')?.textContent.toLowerCase() || '';

            if (!term || name.includes(term)) {
                const cardCategory = card.dataset.category;
                if (this.activeCategory === 'all' || cardCategory === this.activeCategory || !card.dataset.category) {
                    card.style.display = '';
                }
            } else {
                card.style.display = 'none';
            }
        });
    }
};

window.Products = Products;
