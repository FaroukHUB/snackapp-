/* ============================================
   CART.JS - Shopping Cart Management
   Template V2 - SnackApp
   ============================================ */

const Cart = {
    // Cart items array
    items: [],

    // Storage key
    storageKey: 'snackapp_cart_v2',

    // Callbacks for UI updates
    onUpdate: null,

    /**
     * Initialize cart from localStorage
     */
    init() {
        this.load();
        this.updateUI();
        console.log('Cart initialized with', this.items.length, 'items');
    },

    /**
     * Load cart from localStorage
     */
    load() {
        try {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                this.items = JSON.parse(saved);
            }
        } catch (e) {
            console.error('Failed to load cart:', e);
            this.items = [];
        }
    },

    /**
     * Save cart to localStorage
     */
    save() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.items));
        } catch (e) {
            console.error('Failed to save cart:', e);
        }
    },

    /**
     * Add item to cart
     * @param {Object} item - Product or formule data
     * @param {number} quantity - Quantity to add
     * @param {Array} supplements - Selected supplements
     * @param {Object} options - Additional options (size, choices for formule)
     */
    addItem(item, quantity = 1, supplements = [], options = {}) {
        // Generate unique key for this item configuration
        const itemKey = this.generateItemKey(item, supplements, options);

        // Check if same item already exists
        const existingIndex = this.items.findIndex(i => i.key === itemKey);

        if (existingIndex >= 0) {
            // Update quantity
            this.items[existingIndex].quantity += quantity;
        } else {
            // Add new item
            this.items.push({
                key: itemKey,
                id: item.id,
                name: item.name,
                image: item.image,
                basePrice: item.price || item.priceSolo || 0,
                supplements: supplements,
                options: options,
                quantity: quantity,
                isFormule: !!item.includes,
                categoryId: item.categoryId || null,
                addedAt: Date.now()
            });
        }

        // Show toast FIRST for instant feedback
        this.showAddedToast(item.name);

        // Save and update UI asynchronously for better perceived performance
        requestAnimationFrame(() => {
            this.save();
            this.updateUI();
        });

        return true;
    },

    /**
     * Generate unique key for item + supplements + options combination
     */
    generateItemKey(item, supplements, options) {
        const supIds = supplements.map(s => s.id).sort().join(',');
        const optStr = JSON.stringify(options);
        return `${item.id}_${supIds}_${optStr}`;
    },

    /**
     * Remove item from cart
     */
    removeItem(itemKey) {
        const index = this.items.findIndex(i => i.key === itemKey);
        if (index >= 0) {
            this.items.splice(index, 1);
            this.save();
            this.updateUI();
        }
    },

    /**
     * Update item quantity
     */
    updateQuantity(itemKey, newQuantity) {
        const item = this.items.find(i => i.key === itemKey);
        if (item) {
            if (newQuantity <= 0) {
                this.removeItem(itemKey);
            } else {
                item.quantity = newQuantity;
                this.save();
                this.updateUI();
            }
        }
    },

    /**
     * Increment item quantity
     */
    increment(itemKey) {
        const item = this.items.find(i => i.key === itemKey);
        if (item) {
            item.quantity++;
            this.save();
            this.updateUI();
        }
    },

    /**
     * Decrement item quantity
     */
    decrement(itemKey) {
        const item = this.items.find(i => i.key === itemKey);
        if (item) {
            if (item.quantity <= 1) {
                this.removeItem(itemKey);
            } else {
                item.quantity--;
                this.save();
                this.updateUI();
            }
        }
    },

    /**
     * Clear entire cart
     */
    clear() {
        this.items = [];
        this.save();
        this.updateUI();
    },

    /**
     * Get total item count
     */
    getItemCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    /**
     * Calculate item total (with supplements and variant)
     */
    getItemTotal(item) {
        let total = item.basePrice;

        // Add variant price if any
        if (item.options?.selectedVariant?.price) {
            // If variantPriceIsTotal is true, use variant price AS the total (not add to it)
            if (item.options?.variantPriceIsTotal) {
                total = item.options.selectedVariant.price;
            } else {
                total += item.options.selectedVariant.price;
            }
        }

        // Add supplements prices
        if (item.supplements && item.supplements.length > 0) {
            total += item.supplements.reduce((sum, sup) => sum + (sup.price || 0), 0);
        }

        return total * item.quantity;
    },

    /**
     * Get cart subtotal
     */
    getSubtotal() {
        return this.items.reduce((sum, item) => sum + this.getItemTotal(item), 0);
    },

    /**
     * Get all unique categories in cart
     */
    getCartCategories() {
        const categories = new Set();
        this.items.forEach(item => {
            if (item.categoryId) {
                categories.add(item.categoryId);
            }
        });
        return Array.from(categories);
    },

    /**
     * Check if cart is empty
     */
    isEmpty() {
        return this.items.length === 0;
    },

    // Cached DOM references for performance
    _cachedElements: null,

    /**
     * Get cached DOM elements (lazy initialization)
     */
    getCachedElements() {
        if (!this._cachedElements) {
            this._cachedElements = {
                badge: document.getElementById('cartBadge'),
                total: document.getElementById('cartTotal'),
                miniTotal: document.getElementById('miniCartTotal'),
                miniCartItems: document.getElementById('miniCartItems')
            };
        }
        return this._cachedElements;
    },

    /**
     * Update all UI elements (optimized)
     */
    updateUI() {
        const els = this.getCachedElements();
        const count = this.getItemCount();
        const subtotal = this.getSubtotal();
        const formattedTotal = Config.formatPrice(subtotal);

        // Batch DOM updates
        if (els.badge) {
            els.badge.textContent = count;
            els.badge.dataset.count = count;
        }

        if (els.total) {
            els.total.textContent = formattedTotal;
        }

        if (els.miniTotal) {
            els.miniTotal.textContent = formattedTotal;
        }

        // Update mini cart items (only if visible/exists)
        if (els.miniCartItems) {
            this.renderMiniCart();
        }

        // Call custom callback if defined
        if (typeof this.onUpdate === 'function') {
            this.onUpdate(this.items, subtotal);
        }
    },

    /**
     * Render mini cart items
     */
    renderMiniCart() {
        const container = document.getElementById('miniCartItems');
        if (!container) return;

        if (this.isEmpty()) {
            container.innerHTML = `
                <div class="empty-cart" style="text-align: center; padding: 40px 20px; color: var(--gray-500);">
                    <i class="fas fa-shopping-bag" style="font-size: 48px; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>Votre panier est vide</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.items.map((item, index) => {
            const menuType = item.options?.menuType;
            const removedIngredients = item.options?.removedIngredients || [];
            const selectedDrink = item.options?.selectedDrink;
            const selectedSauce = item.options?.selectedSauce;
            const selectedAccompagnement = item.options?.selectedAccompagnement;
            const selectedVariant = item.options?.selectedVariant;

            return `
            <div class="mini-cart-item" data-index="${index}">
                <img src="../${item.image}" alt="${item.name}" class="mini-cart-item-image"
                     onerror="this.style.display='none'">
                <div class="mini-cart-item-info">
                    <div class="mini-cart-item-name">
                        ${item.name}
                        ${menuType === 'menu' ? '<span style="background: var(--primary); color: white; font-size: 9px; padding: 1px 4px; border-radius: 3px; margin-left: 4px;">MENU</span>' : ''}
                    </div>
                    ${selectedVariant ? `
                        <div class="mini-cart-item-variant" style="font-size: 11px; color: var(--primary); font-weight: 500;">
                            → ${selectedVariant.name}
                        </div>
                    ` : ''}
                    ${selectedSauce ? `
                        <div class="mini-cart-item-sauce" style="font-size: 11px; color: var(--warning);">
                            🌶️ ${selectedSauce.name}
                        </div>
                    ` : ''}
                    ${selectedAccompagnement ? `
                        <div class="mini-cart-item-accompagnement" style="font-size: 11px; color: var(--info);">
                            🥗 Avec ${selectedAccompagnement}
                        </div>
                    ` : ''}
                    ${selectedDrink ? `
                        <div class="mini-cart-item-drink" style="font-size: 11px; color: var(--primary);">
                            🥤 ${selectedDrink.name}
                        </div>
                    ` : ''}
                    ${(item.supplements && item.supplements.length > 0) ? `
                        <div class="mini-cart-item-supplements" style="font-size: 11px; color: var(--success);">
                            + ${item.supplements.map(s => s.name).join(', ')}
                        </div>
                    ` : ''}
                    ${removedIngredients.length > 0 ? `
                        <div class="mini-cart-item-removed" style="font-size: 11px; color: var(--error);">
                            Sans: ${removedIngredients.join(', ')}
                        </div>
                    ` : ''}
                    <div class="mini-cart-item-price">${Config.formatPrice(this.getItemTotal(item))}</div>
                    <div class="mini-cart-item-qty">Qté: ${item.quantity}</div>
                </div>
                <button type="button" class="mini-cart-item-remove" data-remove-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `}).join('');

        // Add click handlers for remove buttons
        container.querySelectorAll('.mini-cart-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const index = parseInt(btn.dataset.removeIndex);
                if (!isNaN(index) && this.items[index]) {
                    this.items.splice(index, 1);
                    this.save();
                    this.updateUI();
                }
            });
        });
    },

    /**
     * Show toast notification when item added
     */
    showAddedToast(itemName) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast success';
        toast.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${itemName} ajouté au panier</span>
        `;

        container.appendChild(toast);

        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    /**
     * Generate order summary for WhatsApp
     */
    generateOrderSummary() {
        if (this.isEmpty()) return '';

        let summary = '📋 *MA COMMANDE*\n';
        summary += '━━━━━━━━━━━━━━━━━━━━\n\n';

        this.items.forEach(item => {
            summary += `${item.quantity}x ${item.name}\n`;

            if (item.supplements && item.supplements.length > 0) {
                summary += `   + ${item.supplements.map(s => s.name).join(', ')}\n`;
            }

            summary += `   ${Config.formatPrice(this.getItemTotal(item))}\n\n`;
        });

        summary += '━━━━━━━━━━━━━━━━━━━━\n';
        summary += `*TOTAL: ${Config.formatPrice(this.getSubtotal())}*\n`;

        return summary;
    },

    /**
     * Generate WhatsApp link for self-send
     */
    generateWhatsAppSelfLink(customerPhone, customerName, orderNumber, mode = 'pickup') {
        let message = `📋 *CONFIRMATION COMMANDE*\n`;
        message += `━━━━━━━━━━━━━━━━━━━━\n`;
        message += `🏪 ${Config.restaurant?.name || 'Restaurant'}\n`;
        message += `📍 ${Config.getFullAddress()}\n`;
        message += `━━━━━━━━━━━━━━━━━━━━\n\n`;

        message += `🎫 Commande #${orderNumber}\n`;
        message += `👤 ${customerName}\n`;
        message += `📱 ${customerPhone}\n`;
        message += `🚶 ${mode === 'pickup' ? 'Retrait sur place' : 'Livraison'}\n\n`;

        message += `━━━━━━━━━━━━━━━━━━━━\n`;

        this.items.forEach(item => {
            const menuType = item.options?.menuType;
            const removedIngredients = item.options?.removedIngredients || [];
            const selectedDrink = item.options?.selectedDrink;
            const selectedSauce = item.options?.selectedSauce;
            const selectedAccompagnement = item.options?.selectedAccompagnement;
            const selectedVariant = item.options?.selectedVariant;

            message += `${item.quantity}x ${item.name}`;
            if (menuType === 'menu') {
                message += ` (MENU)`;
            }
            if (selectedVariant) {
                message += `\n   → ${selectedVariant.name}`;
            }
            if (selectedSauce) {
                message += `\n   🌶️ Sauce: ${selectedSauce.name}`;
            }
            if (selectedAccompagnement) {
                message += `\n   🥗 Accompagnement: ${selectedAccompagnement}`;
            }
            if (selectedDrink) {
                message += `\n   🥤 Boisson: ${selectedDrink.name}`;
            }
            if (item.supplements && item.supplements.length > 0) {
                message += `\n   ✅ +${item.supplements.map(s => s.name).join(', ')}`;
            }
            if (removedIngredients.length > 0) {
                message += `\n   ❌ Sans: ${removedIngredients.join(', ')}`;
            }
            message += `\n   💵 ${Config.formatPrice(this.getItemTotal(item))}\n`;
        });

        message += `━━━━━━━━━━━━━━━━━━━━\n`;
        message += `💰 *TOTAL: ${Config.formatPrice(this.getSubtotal())}*\n\n`;
        message += `✅ Commande envoyée !`;

        // Clean phone number
        const cleanPhone = customerPhone.replace(/[^0-9]/g, '');

        return `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
    }
};

// Add CSS for toast animation
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    @keyframes slideOutRight {
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(toastStyle);

// Export for use in other modules
window.Cart = Cart;
