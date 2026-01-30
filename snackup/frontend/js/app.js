/* ============================================
   APP.JS - Application Initialization
   Template V2 - SnackApp
   ============================================ */

const App = {
    // Protection flags pour éviter la multiplication des event listeners
    _setupComplete: false,

    /**
     * Initialize the application
     */
    async init() {
        console.log('Initializing SnackApp Template V2...');

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

            // Setup UI interactions (une seule fois)
            if (!this._setupComplete) {
                this.setupSidebar();
                this.setupMiniCart();
                this.setupSmoothScroll();
                this.setupStickyNav();
                this._setupComplete = true;
            }

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
        // Could add a loading overlay here
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
     * Setup sidebar toggle (mobile)
     */
    setupSidebar() {
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        const closeSidebar = () => {
            sidebar?.classList.remove('active');
            overlay?.classList.remove('active');
            document.body.style.overflow = '';
        };

        toggle?.addEventListener('click', () => {
            const isActive = sidebar?.classList.toggle('active');
            overlay?.classList.toggle('active', isActive);
            document.body.style.overflow = isActive ? 'hidden' : '';
        });

        overlay?.addEventListener('click', closeSidebar);

        // Close sidebar when clicking a nav link (mobile)
        document.querySelectorAll('#categoryNav a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 1024) {
                    closeSidebar();
                }
            });
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

        // ⚡ FIX: Stocker les handlers pour éviter la multiplication
        this._miniCartClickOutsideHandler = this._miniCartClickOutsideHandler || ((e) => {
            if (miniCart?.classList.contains('active') &&
                !miniCart.contains(e.target) &&
                !cartBtn?.contains(e.target)) {
                closeMiniCart();
            }
        });

        this._miniCartEscapeHandler = this._miniCartEscapeHandler || ((e) => {
            if (e.key === 'Escape') closeMiniCart();
        });

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

        // Remove avant d'add pour éviter les doublons
        document.removeEventListener('click', this._miniCartClickOutsideHandler);
        document.removeEventListener('keydown', this._miniCartEscapeHandler);

        // Close when clicking outside
        document.addEventListener('click', this._miniCartClickOutsideHandler, { passive: true });

        // Close on Escape
        document.addEventListener('keydown', this._miniCartEscapeHandler, { passive: true });
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
                    const headerHeight = document.querySelector('.header')?.offsetHeight || 70;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    },

    /**
     * Setup sticky category navigation - CSS sticky only (plus fiable)
     * Ajoute juste la classe 'scrolled' pour le shadow quand on scroll
     */
    setupStickyNav() {
        const categorySection = document.getElementById('categoryIconsSection');
        if (!categorySection) return;

        // Seulement sur mobile (< 1025px)
        if (window.innerWidth >= 1025) return;

        let ticking = false;

        const updateScrolledState = () => {
            const scrollY = window.scrollY;
            // Ajouter shadow quand on a scrollé un peu
            if (scrollY > 100) {
                categorySection.classList.add('scrolled');
            } else {
                categorySection.classList.remove('scrolled');
            }
            ticking = false;
        };

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateScrolledState);
                ticking = true;
            }
        }, { passive: true });

        // Initial check
        updateScrolledState();
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Export for debugging
window.App = App;
