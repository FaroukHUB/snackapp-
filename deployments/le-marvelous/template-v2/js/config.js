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
    categoryEmojis: {},

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
        this.categoryEmojis = data.categoryEmojis || {};
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
        const category = this.menu?.categories?.find(c => c.id === categoryId);
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
     * Find product by ID
     */
    getProduct(productId) {
        for (const category of (this.menu?.categories || [])) {
            const product = category.items?.find(p => p.id === productId);
            if (product) {
                return { ...product, categoryId: category.id, categoryName: category.name };
            }
        }
        return null;
    },

    /**
     * Get supplements for a category
     */
    getSupplementsForCategory(categoryId) {
        const supplementIds = this.supplements?.defaultForCategories?.[categoryId] || [];
        return supplementIds.map(id => this.supplements.catalog?.[id]).filter(Boolean);
    },

    /**
     * Get sweet types configuration for organized supplement display
     */
    getSweetTypes() {
        return this.supplements?.sweetTypes || {
            base: { label: "Base", order: 1 },
            croquant: { label: "Croquant", order: 2 },
            fruit: { label: "Fruit", order: 3 },
            prime: { label: "Prime", order: 4 },
            coulis: { label: "Coulis", order: 5 },
            extra: { label: "Extra", order: 6 }
        };
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
     * Smart distribution: 1-2 items per category for variety
     */
    getUpsellSuggestions(cartCategories) {
        const suggestions = [];
        const categoryProducts = {};

        for (const rule of this.upsellRules) {
            const matches = rule.when.some(cat => cartCategories.includes(cat));
            if (matches) {
                // Collect products from each suggested category
                rule.suggest.forEach(suggestedCat => {
                    if (!categoryProducts[suggestedCat]) {
                        categoryProducts[suggestedCat] = this.getProductsByCategory(suggestedCat)
                            .filter(p => p.status === 'available')
                            .map(p => ({ ...p, categoryId: suggestedCat, upsellMessage: rule.message }));
                    }
                });
                break; // Use first matching rule only
            }
        }

        // Smart distribution: take items round-robin from each category
        const categories = Object.keys(categoryProducts);
        const maxTotal = 6;
        const maxPerCategory = Math.max(1, Math.ceil(maxTotal / categories.length));
        const taken = {};

        // Round 1: Take 1 from each category
        for (const cat of categories) {
            if (suggestions.length >= maxTotal) break;
            const products = categoryProducts[cat];
            if (products.length > 0) {
                suggestions.push(products[0]);
                taken[cat] = 1;
            }
        }

        // Round 2+: Fill remaining slots
        let round = 1;
        while (suggestions.length < maxTotal && round < maxPerCategory) {
            for (const cat of categories) {
                if (suggestions.length >= maxTotal) break;
                const products = categoryProducts[cat];
                const alreadyTaken = taken[cat] || 0;
                if (products.length > alreadyTaken) {
                    suggestions.push(products[alreadyTaken]);
                    taken[cat] = alreadyTaken + 1;
                }
            }
            round++;
        }

        return suggestions;
    },

    /**
     * Get formule by ID
     */
    getFormule(formuleId) {
        return this.formules.find(f => f.id === formuleId);
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
     * Format price for display (DA = Dinar Algérien)
     */
    formatPrice(price) {
        return Math.round(price) + ' DA';
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
