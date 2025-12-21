/* ============================================
   APP.JS - Application Initialization
   Template V2 - SnackApp (BK Style)
   ============================================ */

const App = {
    /**
     * Initialize the application
     */
    async init() {
        console.log('Initializing SnackApp Template V2 (BK Style)...');

        // Show loading state
        this.showLoading(true);

        try {
            // Load configuration
            const configLoaded = await Config.init();
            if (!configLoaded) {
                throw new Error('Failed to load configuration');
            }

            // Initialize cart
            Cart.init();

            // Initialize products display
            Products.init();

            // Setup UI interactions
            this.setupMobileMenu();
            this.setupMiniCart();
            this.setupSmoothScroll();

            console.log('SnackApp initialized successfully!');

        } catch (error) {
            console.error('Initialization error:', error);
            this.showError('Erreur de chargement. Veuillez rafraîchir la page.');
        } finally {
            this.showLoading(false);
        }
    },

    /**
     * Show/hide loading state
     */
    showLoading(show) {
        document.body.classList.toggle('loading', show);
    },

    /**
     * Show error message
     */
    showError(message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast error';
        toast.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => toast.remove(), 5000);
    },

    /**
     * Setup mobile menu toggle
     */
    setupMobileMenu() {
        const menuBtn = document.getElementById('mobileMenuBtn');
        const menu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('mobileMenuOverlay');
        const closeBtn = document.getElementById('mobileMenuClose');

        const openMenu = () => {
            menu?.classList.add('active');
            overlay?.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        const closeMenu = () => {
            menu?.classList.remove('active');
            overlay?.classList.remove('active');
            document.body.style.overflow = '';
        };

        menuBtn?.addEventListener('click', openMenu);
        closeBtn?.addEventListener('click', closeMenu);
        overlay?.addEventListener('click', closeMenu);

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMenu();
        });
    },

    /**
     * Setup mini cart drawer
     */
    setupMiniCart() {
        const cartBtn = document.getElementById('cartBtn');
        const miniCart = document.getElementById('miniCart');
        const closeBtn = document.getElementById('miniCartClose');

        const openMiniCart = () => {
            miniCart?.classList.add('active');
        };

        const closeMiniCart = () => {
            miniCart?.classList.remove('active');
        };

        cartBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            // On mobile, go directly to cart page
            if (window.innerWidth <= 768) {
                window.location.href = 'cart.html';
            } else {
                openMiniCart();
            }
        });

        closeBtn?.addEventListener('click', closeMiniCart);

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (miniCart?.classList.contains('active') &&
                !miniCart.contains(e.target) &&
                !cartBtn?.contains(e.target)) {
                closeMiniCart();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMiniCart();
        });
    },

    /**
     * Setup smooth scrolling for anchor links
     */
    setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').slice(1);
                const target = document.getElementById(targetId);

                if (target) {
                    const headerHeight = document.querySelector('.header-main')?.offsetHeight || 70;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Export for debugging
window.App = App;
