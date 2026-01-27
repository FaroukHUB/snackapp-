/* ============================================
   CONFIG.JS - Configuration & Data Loading
   Template V2 - SnackApp
   ============================================ */

const Config = {
    // Restaurant data
    restaurant: null,
    menu: null,
    formules: [],
    supplements: {},
    upsellRules: [],
    categoryIcons: {},

    // State
    isLoaded: false,

    // Cache for computed values
    _cache: {},

    // Paths - adjust based on your setup
    basePath: '../config/',

    /**
     * Initialize configuration
     */
    async init() {
        try {
            await Promise.all([
                this.loadRestaurant(),
                this.loadMenu()
            ]);
            this.isLoaded = true;
            this.applyTheme();
            this.applyCurrency(); // Remplacer devise hardcodée
            console.log('Config loaded successfully');
            return true;
        } catch (error) {
            console.error('Failed to load configuration:', error);
            return false;
        }
    },

    /**
     * Load restaurant data
     */
    async loadRestaurant() {
        const response = await fetch(this.basePath + 'restaurant.php');
        if (!response.ok) throw new Error('Failed to load restaurant.php');
        this.restaurant = await response.json();
    },

    /**
     * Load menu data
     */
    async loadMenu() {
        const response = await fetch(this.basePath + 'menu.php');
        if (!response.ok) throw new Error('Failed to load menu.php');
        const data = await response.json();

        this.menu = data.menu;
        this.formules = data.formules || [];
        this.supplements = data.supplements || {};
        this.upsellRules = data.upsellRules || [];
        this.categoryIcons = data.categoryIcons || {};
        this.featured = data.featured || {};
        this.menuOptions = data.menuOptions || {};
    },

    /**
     * Apply restaurant theme colors
     */
    applyTheme() {
        if (!this.restaurant) return;

        const theme = this.restaurant.theme || {};
        const root = document.documentElement;

        // Primary colors
        const primaryColor = theme.primary || this.restaurant.branding?.primaryColor || '#e63946';
        root.style.setProperty('--primary', primaryColor);
        root.style.setProperty('--primary-dark', theme.primaryDark || this.darkenColor(primaryColor, 15));
        root.style.setProperty('--primary-light', this.lightenColor(primaryColor, 90));

        // Secondary & accent
        if (theme.secondary) {
            root.style.setProperty('--secondary', theme.secondary);
            root.style.setProperty('--secondary-dark', this.darkenColor(theme.secondary, 15));
        }
        if (theme.accent) root.style.setProperty('--accent', theme.accent);

        // Background colors
        if (theme.background) root.style.setProperty('--bg-dark', theme.background);
        if (theme.cardBackground) root.style.setProperty('--card-bg', theme.cardBackground);

        // Text colors
        if (theme.textPrimary) root.style.setProperty('--text-primary', theme.textPrimary);
        if (theme.textSecondary) root.style.setProperty('--text-secondary', theme.textSecondary);

        // Feedback colors
        if (theme.success) root.style.setProperty('--success', theme.success);
        if (theme.error) root.style.setProperty('--error', theme.error);

        // Border radius
        if (theme.buttonRadius) root.style.setProperty('--border-radius', theme.buttonRadius);
        if (theme.cardRadius) root.style.setProperty('--border-radius-lg', theme.cardRadius);

        // Update page title
        document.title = `Commander | ${this.restaurant.name}`;

        console.log('Theme applied:', theme);
    },

    /**
     * Apply currency to hardcoded elements
     * Remplace "DA" hardcodé par la vraie devise
     */
    applyCurrency() {
        // Récupérer la devise depuis l'API
        const currency = this.restaurant?._jsConfig?.currency ||
                        this.menu?._meta?.currency ||
                        'EUR';

        // Stocker globalement
        window.CURRENCY = currency;

        // Remplacer tous les "DA" hardcodés dans le HTML
        const elementsToUpdate = [
            { id: 'cartTotal', selector: null },
            { id: 'miniCartTotal', selector: null },
            { id: 'addToCartPrice', selector: null }
        ];

        elementsToUpdate.forEach(({ id, selector }) => {
            const element = id ? document.getElementById(id) : document.querySelector(selector);
            if (element && element.textContent) {
                element.textContent = element.textContent.replace(/\bDA\b/g, currency);
            }
        });

        // Mettre à jour le bouton WhatsApp si présent
        const whatsappButton = document.querySelector('.whatsapp-float');
        if (whatsappButton && this.restaurant?.contact?.allowWhatsAppOrders) {
            const whatsappNumber = this.getWhatsAppNumber();
            whatsappButton.href = `https://wa.me/${whatsappNumber}`;
        }

        console.log('Currency applied:', currency);
    },

    /**
     * Helper: Darken a hex color
     */
    darkenColor(hex, percent) {
        const num = parseInt(hex.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.max((num >> 16) - amt, 0);
        const G = Math.max((num >> 8 & 0x00FF) - amt, 0);
        const B = Math.max((num & 0x0000FF) - amt, 0);
        return `#${(1 << 24 | R << 16 | G << 8 | B).toString(16).slice(1)}`;
    },

    /**
     * Helper: Lighten a hex color (creates a tint)
     */
    lightenColor(hex, percent) {
        const num = parseInt(hex.replace('#', ''), 16);
        const amt = Math.round(2.55 * percent);
        const R = Math.min((num >> 16) + amt, 255);
        const G = Math.min((num >> 8 & 0x00FF) + amt, 255);
        const B = Math.min((num & 0x0000FF) + amt, 255);
        return `#${(1 << 24 | R << 16 | G << 8 | B).toString(16).slice(1)}`;
    },

    /**
     * Get categories from menu
     */
    getCategories() {
        return this.menu?.categories || [];
    },

    /**
     * Get products from a specific category
     */
    getProductsByCategory(categoryId) {
        const category = this.menu?.categories?.find(c => c.id == categoryId);
        return category?.items || [];
    },

    /**
     * Get all products flat
     */
    getAllProducts() {
        const products = [];
        this.menu?.categories?.forEach(cat => {
            cat.items?.forEach(item => {
                products.push({ ...item, categoryId: cat.id, categoryName: cat.name });
            });
        });
        return products;
    },

    /**
     * Find product by ID or SLUG
     * Supports:
     * - Numeric ID lookup: getProduct(123) or getProduct("123")
     * - String ID lookup: getProduct("fromagere")
     * - Slug lookup: getProduct("margherita-26cm")
     */
    getProduct(productId) {
        // Détecter si on cherche par ID numérique ou par slug
        const isNumericLookup = typeof productId === 'number' ||
                                (typeof productId === 'string' && /^\d+$/.test(productId));

        // Convertir en nombre si c'est un string numérique
        const numericId = isNumericLookup ? Number(productId) : null;

        for (const category of (this.menu?.categories || [])) {
            let product;

            if (isNumericLookup) {
                // Lookup par ID numérique (strict)
                product = category.items?.find(p => p.id === numericId);
            } else {
                // Lookup par ID string ou par slug (strict)
                product = category.items?.find(p => p.id === productId || p.slug === productId);
            }

            if (product) {
                return { ...product, categoryId: category.id, categoryName: category.name };
            }
        }
        return null;
    },

    /**
     * Get supplements for a category
     * Filtre UNIQUEMENT par flavor (sale/sucre) - ignore defaultForCategories
     */
    getSupplementsForCategory(categoryId) {
        // ❌ Catégories SANS suppléments
        const noSupplementsCategories = [
            'sucres-sales',
            'boissons-chaudes',
            'sodas-eaux',
            'jus-cocktails',
            'menu-enfant'
        ];
        if (noSupplementsCategories.includes(categoryId)) {
            return [];
        }

        // ✅ Chercher la catégorie dans les données pour lire son flavor
        let categoryFlavor = null;

        if (this.menu && this.menu.categories) {
            const category = this.menu.categories.find(cat => cat.id === categoryId);
            if (category && category.flavor) {
                categoryFlavor = category.flavor; // 'sale' ou 'sucre' défini dans l'admin
            }
        }

        // Fallback : deviner selon l'ID si pas de flavor
        if (!categoryFlavor) {
            const sucreCategories = ['crepes-sucrees', 'gaufres', 'bubble-waffle'];
            categoryFlavor = sucreCategories.includes(categoryId) ? 'sucre' : 'sale';
        }

        // Filtrer les suppléments par flavor
        return Object.values(this.supplements.catalog || {})
            .filter(sup => sup.flavor === categoryFlavor && sup.status === 'available');
    },

    /**
     * Get drinks for menu selection
     */
    getDrinks() {
        if (!this._cache.drinks) {
            const drinksCategory = this.menu?.categories?.find(c => c.id === 'boissons');
            this._cache.drinks = (drinksCategory?.items || []).filter(d => d.status === 'available');
        }
        return this._cache.drinks;
    },

    /**
     * Get upsell suggestions based on cart contents
     */
    getUpsellSuggestions(cartCategories) {
        const suggestions = [];

        for (const rule of this.upsellRules) {
            // Check if any cart category matches the rule's "when" condition
            const matches = rule.when.some(cat => cartCategories.includes(cat));
            if (matches) {
                // Get products from suggested items (categories or specific products)
                rule.suggest.forEach(item => {
                    let products = [];

                    // Check if item specifies category with max (format: {category: "id", max: 2})
                    if (typeof item === 'object' && item.category) {
                        const maxItems = item.max || 3;
                        products = this.getProductsByCategory(item.category)
                            .filter(p => p.status === 'available')
                            .slice(0, maxItems);
                    }
                    // String: try as product ID first, then as category
                    else if (typeof item === 'string') {
                        // Try to find specific product
                        const product = this.getProduct(item);
                        if (product && product.status === 'available') {
                            products = [product];
                        } else {
                            // Fall back to category
                            products = this.getProductsByCategory(item)
                                .filter(p => p.status === 'available')
                                .slice(0, 3);
                        }
                    }

                    products.forEach(product => {
                        if (!suggestions.find(s => s.id === product.id)) {
                            // Transform product to show random option if available
                            const upsellProduct = this._getRandomOptionOrProduct(product);

                            suggestions.push({
                                ...upsellProduct,
                                categoryId: typeof item === 'string' ? item : item.category,
                                upsellMessage: rule.message,
                                _originalProductId: product.id
                            });
                        }
                    });
                });
            }
        }

        return suggestions.slice(0, 6); // Max 6 total suggestions
    },

    /**
     * Get random option from product or return product if no options
     * MODIFICATION 2024-12-26: Retourner toujours le produit PARENT, pas une option aléatoire
     * Permet d'ouvrir le modal pour choisir l'option au lieu d'ajouter directement
     * SAFETY: Always returns valid product, never breaks
     */
    _getRandomOptionOrProduct(product) {
        // Safety check: if no product, return as-is
        if (!product) return product;

        // NOUVELLE LOGIQUE: Toujours retourner le produit parent
        // Le modal cart.html se chargera d'afficher les options disponibles
        // Au lieu de retourner une option aléatoire (qui n'existe pas comme produit)
        return product;

        /* ANCIENNE LOGIQUE DÉSACTIVÉE - causait des problèmes d'upsells
        let availableOptions = [];

        // Check for pâtisserie options
        if (product.hasPâtisserieOptions && Array.isArray(product.pâtisserieOptions)) {
            availableOptions = product.pâtisserieOptions.filter(opt =>
                opt && (!opt.status || opt.status === 'available')
            );
        }
        // Check for beverage options
        else if (product.hasBeverageOptions && Array.isArray(product.beverageOptions)) {
            availableOptions = product.beverageOptions.filter(opt =>
                opt && (!opt.status || opt.status === 'available')
            );
        }
        // Check for viennoiserie options
        else if (product.hasViennoiserieOptions && Array.isArray(product.viennoiserieOptions)) {
            availableOptions = product.viennoiserieOptions.filter(opt =>
                opt && (!opt.status || opt.status === 'available')
            );
        }

        // If no available options, return original product (SAFETY FALLBACK)
        if (availableOptions.length === 0) {
            return product;
        }

        // Select random option
        const randomIndex = Math.floor(Math.random() * availableOptions.length);
        const selectedOption = availableOptions[randomIndex];

        // Return option with product structure
        return {
            id: selectedOption.id,
            name: selectedOption.name,
            price: selectedOption.price || product.price || product.priceSolo || 0,
            priceSolo: selectedOption.price || product.priceSolo || 0,
            image: selectedOption.image || product.image,
            description: product.description || '',
            status: 'available',
            _isRandomOption: true,
            _parentProduct: product.id
        };
        */
    },

    /**
     * Get formule by ID
     * Supports both numeric and string IDs (flexible like getProduct)
     */
    getFormule(formuleId) {
        // Handle both number and string IDs
        const isNumericLookup = typeof formuleId === 'number' ||
                                (typeof formuleId === 'string' && /^\d+$/.test(formuleId));

        // Convert to number if it's a numeric string
        const numericId = isNumericLookup ? Number(formuleId) : null;

        if (isNumericLookup) {
            // Lookup by numeric ID (strict)
            return this.formules.find(f => f.id === numericId);
        } else {
            // Lookup by string ID (for formule-midi, etc.)
            return this.formules.find(f => f.id === formuleId);
        }
    },

    /**
     * Get available formules
     */
    getAvailableFormules() {
        if (!this._cache.availableFormules) {
            this._cache.availableFormules = this.formules.filter(f => f.status === 'available');
        }
        return this._cache.availableFormules;
    },

    /**
     * Format price for display
     * Currency symbol: € (euro)
     */
    formatPrice(price) {
        // Récupérer la devise depuis les données du restaurant ou du menu
        const currencyCode = this.restaurant?._jsConfig?.currency ||
                        this.menu?._meta?.currency ||
                        window.SNACK_CONFIG?.currency ||
                        'EUR';
        // Convertir code devise en symbole
        const currencySymbols = { 'EUR': '€', 'USD': '$', 'GBP': '£', 'DA': 'DA', 'DZD': 'DA' };
        const symbol = currencySymbols[currencyCode] || currencyCode;
        return Math.round(price) + ' ' + symbol;
    },

    /**
     * Get opening hours formatted
     */
    getOpeningHours() {
        return this.restaurant?.openingHours || [];
    },

    /**
     * Check if restaurant is currently open
     */
    isOpen() {
        const now = new Date();
        const dayNames = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        const today = dayNames[now.getDay()];

        const todayHours = this.restaurant?.openingHours?.find(h =>
            h.day.toLowerCase() === today
        );

        if (!todayHours) return false;

        const currentTime = now.getHours() * 60 + now.getMinutes();

        // Check slots if available, otherwise use opens/closes
        const slots = todayHours.slots || [{ opens: todayHours.opens, closes: todayHours.closes }];

        for (const slot of slots) {
            const [openH, openM] = slot.opens.split(':').map(Number);
            const [closeH, closeM] = slot.closes.split(':').map(Number);

            let openTime = openH * 60 + openM;
            let closeTime = closeH * 60 + closeM;

            // Handle closing after midnight
            if (closeTime < openTime) {
                closeTime += 24 * 60;
                if (currentTime < openTime) {
                    // We're after midnight but before closing
                    if (currentTime + 24 * 60 < closeTime) return true;
                }
            }

            if (currentTime >= openTime && currentTime < closeTime) {
                return true;
            }
        }

        return false;
    },

    /**
     * Get contact info
     */
    getContact() {
        return this.restaurant?.contact || {};
    },

    /**
     * Get social links
     */
    getSocial() {
        return this.restaurant?.social || {};
    },

    /**
     * Get FAQ items
     */
    getFAQ() {
        return this.restaurant?.faq?.items || [];
    },

    /**
     * Get WhatsApp order number
     */
    getWhatsAppNumber() {
        return this.restaurant?.contact?.whatsappOrdersNumber ||
               this.restaurant?.contact?.phone?.replace(/[^0-9]/g, '');
    },

    /**
     * Get full address
     */
    getFullAddress() {
        const loc = this.restaurant?.location;
        if (!loc) return '';
        const street = loc.addressLine1 || loc.address || '';
        return `${street}, ${loc.postalCode || ''} ${loc.city || ''}`.trim();
    },

    /**
     * Get delivery platforms (Uber Eats, Deliveroo, Just Eat)
     */
    getPlatforms() {
        return this.restaurant?.platforms || [];
    }
};

// Export for use in other modules
window.Config = Config;
