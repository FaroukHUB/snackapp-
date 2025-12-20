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

        this.save();
        this.updateUI();
        this.showAddedToast(item.name);

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
     * Calculate item total (with supplements)
     */
    getItemTotal(item) {
        let total = item.basePrice;

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

    /**
     * Update all UI elements
     */
    updateUI() {
        // Update badge
        const badge = document.getElementById('cartBadge');
        if (badge) {
            const count = this.getItemCount();
            badge.textContent = count;
            badge.dataset.count = count;
        }

        // Update total in header
        const total = document.getElementById('cartTotal');
        if (total) {
            total.textContent = Config.formatPrice(this.getSubtotal());
        }

        // Update mini cart total
        const miniTotal = document.getElementById('miniCartTotal');
        if (miniTotal) {
            miniTotal.textContent = Config.formatPrice(this.getSubtotal());
        }

        // Update mini cart items
        this.renderMiniCart();

        // Call custom callback if defined
        if (typeof this.onUpdate === 'function') {
            this.onUpdate(this.items, this.getSubtotal());
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

        container.innerHTML = this.items.map(item => `
            <div class="mini-cart-item" data-key="${item.key}">
                <img src="../${item.image}" alt="${item.name}" class="mini-cart-item-image"
                     onerror="this.src='../images/placeholder.jpg'">
                <div class="mini-cart-item-info">
                    <div class="mini-cart-item-name">${item.name}</div>
                    ${item.supplements.length > 0 ? `
                        <div class="mini-cart-item-supplements" style="font-size: 11px; color: var(--gray-500);">
                            + ${item.supplements.map(s => s.name).join(', ')}
                        </div>
                    ` : ''}
                    <div class="mini-cart-item-price">${Config.formatPrice(this.getItemTotal(item))}</div>
                    <div class="mini-cart-item-qty">Qté: ${item.quantity}</div>
                </div>
                <button class="mini-cart-item-remove" onclick="Cart.removeItem('${item.key}')"
                        style="color: var(--gray-400); padding: 8px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
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

            if (item.supplements.length > 0) {
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
            message += `${item.quantity}x ${item.name}`;
            if (item.supplements.length > 0) {
                message += ` (+${item.supplements.map(s => s.name).join(', ')})`;
            }
            message += ` — ${Config.formatPrice(this.getItemTotal(item))}\n`;
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
