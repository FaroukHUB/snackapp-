/* ============================================
   PRODUCTS.JS - Products & Formules Display
   Template V2 - SnackApp
   ============================================ */

// products.js loaded

// 🔒 SÉCURITÉ: Fonction pour échapper le HTML et prévenir les attaques XSS
function escapeHtml(unsafe) {
    if (unsafe === null || unsafe === undefined) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

const Products = {
    // Current modal state
    currentProduct: null,
    currentQuantity: 1,
    selectedSupplements: [],
    removedIngredients: [],
    menuType: 'solo', // 'solo' or 'menu'
    selectedDrink: null, // For menu drink selection
    selectedSauce: null, // For special sauce selection (Crousti)
    selectedAccompagnement: null, // For accompaniment selection (salade/riz)
    selectedViennoiserie: null, // For viennoiserie selection (Croissant/Pain au Chocolat)
    selectedPatisserie: null, // For pâtisserie selection (with photo and price)
    selectedBeverage: null, // For beverage selection (Soda/Jus/Jus Frais/Smoothie)
    selectedVariant: null, // For variant selection (Court/Long)
    selectedCapsule: null, // For capsule number selection

    // Protection flags pour éviter la multiplication des event listeners
    modalSetup: false,
    searchSetup: false,

    // Cache suppléments: ne reconstruire que si catégorie change
    _supplementsCategoryId: null,

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
        this.renderCategoryIcons();
        this.renderFeatured();
        this.renderSidebar();
        // renderFormules() supprimé - maintenant intégré dans renderAllCategories()
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
     * Render category icons filter
     */
    renderCategoryIcons() {
        const container = document.getElementById('categoryIconsContainer');
        if (!container) return;

        const categories = Config.getCategories();

        // Filter non-empty categories
        const nonEmptyCategories = categories.filter(cat => cat.items && cat.items.length > 0);

        if (nonEmptyCategories.length === 0) return;

        // Add "All" button first
        let html = `
            <div class="category-icon-item active" data-category="all" onclick="Products.filterByCategory('all')">
                <div class="category-icon-circle color-primary">
                    <i class="fas fa-th"></i>
                </div>
                <span class="category-icon-name">Tout</span>
            </div>
        `;

        // Add category icons with alternating colors
        nonEmptyCategories.forEach((cat, index) => {
            const icon = cat.icon || 'fa-utensils';
            const iconImage = cat.icon_image;
            const colorClass = index % 2 === 0 ? 'color-primary' : 'color-black';

            // Retirer "Pizza " ou "Pizzas " du nom pour affichage compact
            let displayName = cat.name
                .replace(/^Pizzas?\s+/i, '')  // Retire "Pizza " ou "Pizzas " au début
                .trim();

            // Priority: icon_image > emoji/text > FontAwesome
            let iconHtml;
            if (iconImage) {
                // Image badge (style hashtagbangers.fr)
                // Ajouter ../../ si le chemin ne commence pas par http ou /
                const imgSrc = iconImage.startsWith('http') || iconImage.startsWith('/') ? iconImage : `../../${iconImage}`;
                iconHtml = `<img src="${imgSrc}" alt="" class="category-icon-img">`;
            } else if (icon.startsWith("fa-")) {
                iconHtml = `<i class="fas ${icon}"></i>`;
            } else {
                // Emoji or text
                iconHtml = icon;
            }

            html += `
                <div class="category-icon-item" data-category="${cat.id}" onclick="Products.filterByCategory('${cat.id}')">
                    <div class="category-icon-circle ${colorClass}${iconImage ? ' has-image' : ''}">
                        ${iconHtml}
                    </div>
                    <span class="category-icon-name">${displayName}</span>
                </div>
            `;
        });

        // Ajouter "Nos Formules" si des formules sont disponibles
        const formules = Config.getAvailableFormules();
        if (formules && formules.length > 0) {
            const formulesColorClass = nonEmptyCategories.length % 2 === 0 ? 'color-primary' : 'color-black';
            html += `
                <div class="category-icon-item" data-category="formules" onclick="Products.scrollToFormules()">
                    <div class="category-icon-circle ${formulesColorClass}">
                        <i class="fas fa-fire"></i>
                    </div>
                    <span class="category-icon-name">Formules</span>
                </div>
            `;
        }

        container.innerHTML = html;
    },

    /**
     * Filter products by category
     */
    filterByCategory(categoryId) {
        console.log('Filtering by category:', categoryId);

        // Update active state on icons
        document.querySelectorAll('.category-icon-item').forEach(item => {
            item.classList.toggle('active', item.dataset.category === categoryId);
        });

        // Show/hide sections
        if (categoryId === 'all') {
            // Show all sections
            document.querySelectorAll('.product-section, .featured-section').forEach(section => {
                section.style.display = '';
            });
        } else {
            // Hide all sections
            document.querySelectorAll('.product-section, .featured-section').forEach(section => {
                section.style.display = 'none';
            });

            // Show only selected category
            const selectedSection = document.getElementById(categoryId);
            if (selectedSection) {
                selectedSection.style.display = '';
            }
        }

        // Scroll to products
        const firstVisibleSection = categoryId === 'all'
            ? document.getElementById('featuredSection')
            : document.getElementById(categoryId);

        if (firstVisibleSection) {
            setTimeout(() => {
                firstVisibleSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    },

    /**
     * Scroll to formules section
     */
    scrollToFormules() {
        // Update active state on icons
        document.querySelectorAll('.category-icon-item').forEach(item => {
            item.classList.toggle('active', item.dataset.category === 'formules');
        });

        // Show all sections (formules is part of the main flow)
        document.querySelectorAll('.product-section, .featured-section').forEach(section => {
            section.style.display = '';
        });

        // Scroll to formules section
        const formulesSection = document.getElementById('formulesSection');
        if (formulesSection) {
            setTimeout(() => {
                formulesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
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

        grid.innerHTML = featuredProducts.map((product, index) => {
            // Si pricePrefix existe (ex: "À partir de 500 Da"), afficher SEULEMENT ça
            if (product.pricePrefix) {
                const priceText = product.pricePrefix;
                const desc = product.description ? product.description.substring(0, 100) + (product.description.length > 100 ? '...' : '') : '';
                return `
                    <article class="product-card-new" data-product-id="${escapeHtml(product.id)}" onclick="Products.openProductModal('${escapeHtml(product.id)}')">
                        ${product.badge ? `<span class="product-badge-new">${escapeHtml(product.badge)}</span>` : ''}
                        <img src="../../${escapeHtml(product.image)}"
                             alt="${escapeHtml(product.name)}"
                             class="product-image-new"
                             width="300"
                             height="300"
                             ${index >= 6 ? 'loading="lazy"' : ''}
                             onerror="this.style.display='none'">
                        <div class="product-content-new">
                            <h3 class="product-name-new">${escapeHtml(product.name)}</h3>
                            <p class="product-desc-new">${escapeHtml(desc)}</p>
                            <div class="product-footer-new">
                                <span class="product-price-new">${priceText}</span>
                                <button class="product-btn-new" onclick="event.stopPropagation(); Products.openProductModal('${escapeHtml(product.id)}')">
                                    <span>Ajouter</span>
                                </button>
                            </div>
                        </div>
                    </article>
                `;
            }

            // Sinon, calculer le prix normalement
            let price = product.priceSolo || product.price || 0;

            // Si price = 0 et produit a des options, calculer le prix minimum
            if (price === 0 && product.pâtisserieOptions && product.pâtisserieOptions.length > 0) {
                price = Math.min(...product.pâtisserieOptions.map(opt => opt.price || 0));
            } else if (price === 0 && product.beverageOptions && product.beverageOptions.length > 0) {
                price = Math.min(...product.beverageOptions.map(opt => opt.price || 0));
            }

            const priceText = Config.formatPrice(price);
            const desc = product.description ? product.description.substring(0, 100) + (product.description.length > 100 ? '...' : '') : '';
            return `
                <article class="product-card-new" data-product-id="${escapeHtml(product.id)}" onclick="Products.openProductModal('${escapeHtml(product.id)}')">
                    ${product.badge ? `<span class="product-badge-new">${escapeHtml(product.badge)}</span>` : ''}
                    <img src="../../${escapeHtml(product.image)}"
                         alt="${escapeHtml(product.name)}"
                         class="product-image-new"
                         width="300"
                         height="300"
                         loading="lazy"
                         onerror="this.style.display='none'">
                    <div class="product-content-new">
                        <h3 class="product-name-new">${escapeHtml(product.name)}</h3>
                        <p class="product-desc-new">${escapeHtml(desc)}</p>
                        <div class="product-footer-new">
                            <span class="product-price-new">${priceText}</span>
                            <button class="product-btn-new" onclick="event.stopPropagation(); Products.openProductModal('${escapeHtml(product.id)}')">
                                <span>Ajouter</span>
                            </button>
                        </div>
                    </div>
                </article>
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

        let html = '';

        // Add Featured link first (if enabled)
        const featured = Config.featured;
        if (featured?.enabled && featured?.items?.length > 0) {
            html += `
                <li>
                    <a href="#featuredSection" data-section="featuredSection" onclick="Products.scrollToSection('featuredSection', event)" class="active">
                        <i class="fas fa-star"></i>
                        ${featured.title || 'Sélection pour vous'}
                    </a>
                </li>
            `;
        }

        // Add category links
        categories.forEach(cat => {
            const icon = cat.icon || 'fa-utensils';
            const iconImage = cat.icon_image;
            // Only add if category has items
            if (cat.items && cat.items.length > 0) {
                // Priority: icon_image > emoji/text > FontAwesome
                let iconHtml;
                if (iconImage) {
                    // Image badge (style hashtagbangers.fr)
                    const imgSrc = iconImage.startsWith('http') || iconImage.startsWith('/') ? iconImage : `../../${iconImage}`;
                    iconHtml = `<img src="${imgSrc}" alt="" class="sidebar-icon-img">`;
                } else if (icon.startsWith("fa-")) {
                    iconHtml = `<i class="fas ${icon}"></i>`;
                } else {
                    iconHtml = `<span class="sidebar-icon-emoji">${icon}</span>`;
                }
                html += `
                    <li>
                        <a href="#${cat.id}" data-section="${cat.id}" onclick="Products.scrollToSection('${cat.id}', event)">
                            ${iconHtml}
                            <span class="sidebar-cat-name">${cat.name}</span>
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
                    <a href="#formulesSection" data-section="formulesSection" onclick="Products.scrollToSection('formulesSection', event)">
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
                        if (link.getAttribute('data-section') === id) {
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
     * Scroll to section without adding hash to URL
     */
    scrollToSection(sectionId, event) {
        // Prevent hash from being added to URL
        if (event) {
            event.preventDefault();
        }

        const section = document.getElementById(sectionId);
        if (!section) return;

        // Smooth scroll to section
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Update active state on navigation
        const navLinks = document.querySelectorAll('#categoryNav a');
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-section') === sectionId) {
                link.classList.add('active');
            }
        });
    },

    /**
     * Render formules section - NOUVEAU: retourne HTML complet de la section
     */
    renderFormulesSection() {
        const formules = Config.getAvailableFormules();

        if (formules.length === 0) {
            return '';
        }

        const cardsHtml = formules.map(formule => {
            const hasImage = formule.image && formule.image.trim() !== '';

            // Badge dynamique depuis la DB
            const badgeHtml = formule.badge
                ? `<span class="formule-savings">${escapeHtml(formule.badge)}</span>`
                : '';

            // Image wrapper avec centrage
            const imageHtml = hasImage
                ? `<div class="formule-image-centered">
                       <img src="../../${escapeHtml(formule.image)}" alt="${escapeHtml(formule.name)}" class="formule-image"
                            loading="lazy"
                            onerror="this.parentElement.innerHTML='<div class=\\'formule-image-placeholder\\'><i class=\\'fas fa-fire\\'></i></div>'">
                       ${badgeHtml}
                   </div>`
                : `<div class="formule-image-centered">
                       <div class="formule-image-placeholder"><i class="fas fa-fire"></i></div>
                       ${badgeHtml}
                   </div>`;

            // Description ou availability depuis DB (pas de texte hardcodé)
            const descriptionHtml = formule.description
                ? `<p class="formule-description">${escapeHtml(formule.description)}</p>`
                : '';

            return `
                <div class="formule-card" onclick="Products.openFormuleModal('${escapeHtml(formule.id)}')">
                    ${imageHtml}
                    <div class="formule-card-content">
                        <h3 class="formule-name">${escapeHtml(formule.name)}</h3>
                        ${descriptionHtml}
                        <div class="formule-price">
                            <span class="current">${Config.formatPrice(formule.price)}</span>
                            ${formule.originalPrice ? `<span class="original">${Config.formatPrice(formule.originalPrice)}</span>` : ''}
                        </div>
                        <button type="button" class="formule-cta-btn" data-add-formule="${escapeHtml(formule.id)}">
                            <i class="fas fa-check-circle"></i>
                            Choisir cette formule
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        // Retourner la section complète avec styles alignés sur product-section
        return `
            <section class="product-section formules-section" id="formulesSection">
                <h2>
                    <i class="fas fa-fire"></i>
                    Nos Formules
                </h2>
                <div class="formules-grid">
                    ${cardsHtml}
                </div>
            </section>
        `;
    },

    /**
     * OBSOLÈTE - Garder pour compatibilité mais ne plus utiliser
     */
    renderFormules() {
        // Cette fonction n'est plus utilisée - remplacée par renderFormulesSection()
        // Gardée pour éviter les erreurs si appelée ailleurs
        return;
    },

    /**
     * Render formules inline (après sucres-sales) - CARRÉ comme Click & Collect
     */
    renderFormulesInline() {
        const formules = Config.getAvailableFormules();

        if (formules.length === 0) {
            return '';
        }

        // Couleurs alternées : vert Marvelous et rose
        const colors = [
            { bg: 'linear-gradient(135deg, #2ec4b6, #25a89c)' },
            { bg: 'linear-gradient(135deg, #ec4899, #db2777)' }
        ];

        const formulesHtml = formules.map((formule, index) => {
            const color = colors[index % 2];
            const hasImage = formule.image && formule.image.trim() !== '';

            return `
                <div class="formule-card-v2" style="display: flex; flex-direction: column; align-items: center; text-align: center; background: ${color.bg}; border-radius: 16px; padding: 28px 20px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.1); position: relative; overflow: hidden;" onclick="Products.openFormuleModal('${formule.id}')" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)';">
                    <div style="width: 70px; height: 70px; margin: 0 auto 14px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.15); flex-shrink: 0; overflow: hidden;">
                        ${hasImage
                            ? `<img src="../../${formule.image}" alt="${formule.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" onerror="this.outerHTML='<i class=\\'fas fa-fire\\' style=\\'font-size: 32px; color: white;\\'></i>'">`
                            : `<i class="fas fa-fire" style="font-size: 32px; color: white;"></i>`
                        }
                    </div>
                    <div style="flex: 1; width: 100%;">
                        <h3 style="color: white; font-size: 18px; font-weight: 700; margin: 0 0 8px 0;">${formule.name}</h3>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 13px; line-height: 1.4; margin: 0 0 10px 0;">${formule.description}</p>
                        ${formule.badge ? `<span style="display: inline-block; background: rgba(255, 255, 255, 0.2); color: white; padding: 3px 8px; border-radius: 8px; font-size: 11px; font-weight: 600; margin-bottom: 10px;">${formule.badge}</span>` : ''}
                        <div style="font-size: 24px; font-weight: 700; color: white; margin: 10px 0;">
                            ${Config.formatPrice(formule.price)}
                        </div>
                        ${formule.originalPrice ? `<div style="font-size: 13px; color: rgba(255, 255, 255, 0.7); text-decoration: line-through; margin-top: -6px; margin-bottom: 10px;">${Config.formatPrice(formule.originalPrice)}</div>` : ''}
                        <button onclick="event.stopPropagation(); Products.addFormuleDirectly('${formule.id}')" style="background: white; color: ${index % 2 === 0 ? '#2ec4b6' : '#ec4899'}; padding: 8px 20px; border: none; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 3px 10px rgba(0,0,0,0.12); margin-top: 4px;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                            AJOUTER <i class="fas fa-plus" style="margin-left: 5px; font-size: 11px;"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <section class="formules-section-v2" style="padding: 50px 20px; background: #f8f9fa;">
                <div style="text-align: center; margin-bottom: 32px;">
                    <h2 style="font-size: 28px; font-weight: 700; color: #1a1a2e; margin: 0 0 6px 0;"><i class="fas fa-fire" style="color: #ec4899; margin-right: 10px;"></i>Nos Formules</h2>
                    <p style="font-size: 15px; color: #64748b; margin: 0;">Économisez avec nos menus combinés</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; max-width: 750px; margin: 0 auto;">
                    ${formulesHtml}
                </div>
            </section>
        `;
    },

    /**
     * Render all product categories
     */
    renderAllCategories() {
        const container = document.getElementById('productsContainer');
        if (!container) {
            console.error('productsContainer not found!');
            return;
        }

        const categories = Config.getCategories();
        const icons = Config.categoryIcons;

        console.log('📦 Total categories loaded:', categories.length);
        categories.forEach(cat => {
            console.log(`  - ${cat.name} (${cat.id}): ${cat.items?.length || 0} items`);
        });

        // Filter out empty categories
        const nonEmptyCategories = categories.filter(cat => cat.items && cat.items.length > 0);

        console.log('✅ Non-empty categories:', nonEmptyCategories.length);

        // Préparer le HTML des formules
        const formulesHtml = this.renderFormulesSection();

        // Trouver l'index de "Pizza originale" (recherche insensible à la casse)
        const pizzaOriginaleIndex = nonEmptyCategories.findIndex(cat =>
            cat.name && cat.name.toLowerCase().includes('originale')
        );

        console.log('🍕 Pizza originale index:', pizzaOriginaleIndex,
            pizzaOriginaleIndex >= 0 ? `(${nonEmptyCategories[pizzaOriginaleIndex].name})` : '(non trouvée - fallback fin)');

        // Render categories avec insertion formules après Pizza originale
        let html = '';
        nonEmptyCategories.forEach((cat, index) => {
            html += `
                <section class="product-section" id="${cat.id}">
                    <h2>${cat.name}</h2>
                    <div class="products-grid">
                        ${this.renderProducts(cat.items || [], cat.id, cat.name)}
                    </div>
                </section>
            `;

            // Insérer formules APRÈS Pizza originale
            if (index === pizzaOriginaleIndex && formulesHtml) {
                console.log('✅ FORMULES: Insérées après', cat.name);
                html += formulesHtml;
            }
        });

        // Fallback: si Pizza originale non trouvée, ajouter formules à la fin
        if (pizzaOriginaleIndex < 0 && formulesHtml) {
            console.log('⚠️ FORMULES: Fallback - ajoutées à la fin (Pizza originale non trouvée)');
            html += formulesHtml;
        }

        container.innerHTML = html;

        console.log('🎨 Rendered HTML length:', container.innerHTML.length);
    },

    /**
     * Render products for a category
     */
    renderProducts(products, categoryId, categoryName = '') {
        if (!products || !Array.isArray(products)) {
            console.warn(`⚠️ No products array for category: ${categoryId}`);
            return '';
        }
        console.log(`🍽️ Rendering ${products.length} products for category: ${categoryId} (${categoryName})`);
        return products.map(product => {
            const isUnavailable = product.status === 'unavailable';

            // Si pricePrefix existe (ex: "À partir de 500 Da"), afficher SEULEMENT ça
            let priceText;
            if (product.pricePrefix) {
                priceText = product.pricePrefix;
            } else {
                // Sinon, calculer le prix normalement
                let price = product.price || product.priceSolo || 0;

                // Si price = 0 et produit a des options, calculer le prix minimum
                if (price === 0 && product.pâtisserieOptions && product.pâtisserieOptions.length > 0) {
                    price = Math.min(...product.pâtisserieOptions.map(opt => opt.price || 0));
                } else if (price === 0 && product.beverageOptions && product.beverageOptions.length > 0) {
                    price = Math.min(...product.beverageOptions.map(opt => opt.price || 0));
                }

                priceText = Config.formatPrice(price);
            }
            const desc = product.description ? product.description.substring(0, 100) + (product.description.length > 100 ? '...' : '') : '';

            // Badge bundle pour pizzas
            const catNameLower = String(categoryName || '').toLowerCase();
            const isPizza = catNameLower.includes('pizza');
            const bundleBadge = isPizza ? `
                <div class="bundle-offer-badge">
                    <span class="bundle-icon">🔥</span>
                    <span class="bundle-text">2 = 13€ Solo | 15€ Duo</span>
                </div>` : '';

            return `
                <article class="product-card-new ${isUnavailable ? 'unavailable' : ''}" data-product-id="${escapeHtml(product.id)}" onclick="Products.openProductModal('${escapeHtml(product.id)}')">
                    ${product.badge ? `<span class="product-badge-new">${escapeHtml(product.badge)}</span>` : ''}
                    <img src="../../${escapeHtml(product.image)}"
                         alt="${escapeHtml(product.name)}"
                         class="product-image-new"
                         width="300"
                         height="300"
                         loading="lazy"
                         onerror="this.style.display='none'">
                    <div class="product-content-new">
                        <h3 class="product-name-new">${escapeHtml(product.name)}</h3>
                        <p class="product-desc-new">${escapeHtml(desc)}</p>
                        ${bundleBadge}
                        <div class="product-footer-new">
                            <span class="product-price-new">${priceText}</span>
                            <button class="product-btn-new" onclick="event.stopPropagation(); Products.openProductModal('${escapeHtml(product.id)}')">
                                <span>Ajouter</span>
                            </button>
                        </div>
                    </div>
                </article>
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

            if (social.instagram || social.facebook || social.tiktok || social.snapchat) {
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
                if (social.snapchat) {
                    const snapchatUrl = social.snapchat.startsWith('@')
                        ? `https://www.snapchat.com/add/${social.snapchat.substring(1)}`
                        : social.snapchat;
                    html += `<a href="${snapchatUrl}" target="_blank" class="social-link snapchat"><i class="fab fa-snapchat"></i></a>`;
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
            const location = Config.restaurant?.location;
            // Use custom embed URL if available, otherwise fallback to dynamic generation
            const embedUrl = location?.googleMapsEmbed;
            const mapsUrl = location?.googleMapsUrl;

            if (embedUrl) {
                mapContainer.innerHTML = `
                    <iframe
                        src="${embedUrl}"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                `;
            } else {
                // Fallback: generate from address
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
                }
            }

            // Set directions button link (opens in Google Maps / GPS)
            if (directionsBtn) {
                if (mapsUrl) {
                    directionsBtn.href = mapsUrl;
                } else {
                    const fullAddress = Config.getFullAddress();
                    if (fullAddress) {
                        const encodedAddress = encodeURIComponent(fullAddress);
                        directionsBtn.href = `https://www.google.com/maps/dir/?api=1&destination=${encodedAddress}`;
                    }
                }
            }

            if (!embedUrl && !Config.getFullAddress()) {
                document.getElementById('localisation')?.classList.add('hidden');
                document.getElementById('localisation').style.display = 'none';
            }
        }
    },

    /**
     * Setup product modal
     */
    setupModal() {
        // Protection: éviter la multiplication des event listeners
        if (this.modalSetup) return;
        this.modalSetup = true;

        const modal = document.getElementById('productModal');
        const backdrop = modal?.querySelector('.modal-backdrop');
        const closeBtn = document.getElementById('modalClose');

        backdrop?.addEventListener('click', () => this.closeModal(), { passive: true });
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

        // ⚡ FIX: Stocker les handlers pour éviter la multiplication
        this._escapeHandler = this._escapeHandler || ((e) => {
            if (e.key === 'Escape') this.closeModal();
        });
        this._formuleClickHandler = this._formuleClickHandler || ((e) => {
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

        // Remove avant d'add pour éviter les doublons (au cas où)
        document.removeEventListener('keydown', this._escapeHandler);
        document.removeEventListener('click', this._formuleClickHandler);

        // Ajouter les listeners globaux
        document.addEventListener('keydown', this._escapeHandler, { passive: true });
        document.addEventListener('click', this._formuleClickHandler, { passive: true });

        // ⚡ EVENT DELEGATION pour les boutons "Choisir cette formule"
        this._formuleAddHandler = this._formuleAddHandler || ((e) => {
            const btn = e.target.closest('[data-add-formule]');
            if (!btn) return;

            e.preventDefault();
            e.stopPropagation();

            const formuleId = btn.dataset.addFormule;
            console.log('CLICK FORMULE', formuleId);

            // Open modal instead of adding directly
            this.openFormuleModal(formuleId);
        });

        // Remove avant d'add pour éviter les doublons
        document.removeEventListener('click', this._formuleAddHandler);
        document.addEventListener('click', this._formuleAddHandler);
    },

    /**
     * Open product modal
     */
    openProductModal(productId) {
        const product = Config.getProduct(productId);
        if (!product) return;

        // Ouvrir le modal immédiatement (visuel d'abord)
        const modal = document.getElementById('productModal');
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Reset state
        this.currentProduct = product;
        this.currentQuantity = 1;
        this.selectedSupplements = [];
        this.removedIngredients = [];
        this.menuType = 'solo';
        this.selectedDrink = null;
        this.selectedSauce = null;
        this.selectedAccompagnement = null;
        this.selectedViennoiserie = null;
        this.selectedPatisserie = null;
        this.selectedBeverage = null;
        this.selectedKidsCrepe = null;
        this.selectedKidsSauce = null;
        this.selectedVariant = null;
        this.selectedCapsule = null;

        // Charger le contenu essentiel immédiatement
        document.getElementById('modalTitle').textContent = product.name;
        const imgEl = document.getElementById('modalImage');
        if (product.image) {
            imgEl.src = '../../' + product.image;
            imgEl.style.display = '';
        } else {
            imgEl.src = '';
            imgEl.style.display = 'none';
        }

        // Différer le reste du chargement pour afficher le modal plus vite
        requestAnimationFrame(() => {
            // Masquer l'encart "Cette formule comprend" (produits non-formule)
            const formuleIncludesSection = document.getElementById('modalFormuleIncludes');
            if (formuleIncludesSection) {
                formuleIncludesSection.classList.add('hidden');
                formuleIncludesSection.style.display = 'none';
            }

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

            // Categories where "Retirer des ingrédients" should be hidden
            const categoriesWithoutIngredientRemoval = [
                'sucres-sales',      // Nos Sucrés et Salés (Viennoiseries, Pâtisserie)
                'crepes-sucrees',    // Crêpes Sucrées
                'gaufres',           // Gaufres
                'bubble-waffle'      // Bubble Waffle
            ];

            const shouldShowIngredients = hasIngredients &&
                !categoriesWithoutIngredientRemoval.includes(product.categoryId);

            if (shouldShowIngredients) {
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

            // Render supplements (uniquement pour les pizzas)
            const supplementsContainer = document.getElementById('modalSupplements');
            const supplementsList = document.getElementById('supplementsList');

            // Vérifier si c'est une catégorie pizza
            const productCategory = Config.getCategories().find(c => c.id === product.categoryId);
            const categoryNameLower = (productCategory?.name || '').toLowerCase();
            const isPizzaCategory = categoryNameLower.includes('pizza');

            if (!isPizzaCategory) {
                // Masquer les suppléments pour les catégories non-pizza
                supplementsContainer.classList.add('hidden');
                supplementsContainer.style.display = 'none';
                supplementsList.innerHTML = '';
                this._supplementsCategoryId = null;
            } else if (this._supplementsCategoryId !== product.categoryId) {
                this._supplementsCategoryId = product.categoryId;
                const supplements = Config.getSupplementsForCategory(product.categoryId);

                if (supplements.length > 0) {
                    supplementsContainer.classList.remove('hidden');
                    supplementsContainer.style.display = '';

                    // Ordre préféré pour les groupes (les autres seront ajoutés à la fin)
                    const groupOrder = ['viande', 'viandes', 'fromages', 'fromage', 'legumes', 'légumes', 'sauces', 'sauce', 'autres'];
                    const groupLabels = {
                        'viande': 'Viandes',
                        'viandes': 'Viandes',
                        'fromages': 'Fromages',
                        'fromage': 'Fromages',
                        'legumes': 'Légumes',
                        'légumes': 'Légumes',
                        'sauces': 'Sauces',
                        'sauce': 'Sauces',
                        'autres': 'Autres'
                    };

                    const grouped = {};
                    supplements.forEach(sup => {
                        const group = (sup.group_name || 'autres').toLowerCase();
                        if (!grouped[group]) grouped[group] = [];
                        grouped[group].push(sup);
                    });

                    // Collecter tous les groupes présents
                    const allGroups = Object.keys(grouped);
                    // Trier: d'abord ceux dans groupOrder, puis les autres
                    const sortedGroups = [
                        ...groupOrder.filter(g => allGroups.includes(g)),
                        ...allGroups.filter(g => !groupOrder.includes(g))
                    ];
                    // Enlever les doublons
                    const uniqueGroups = [...new Set(sortedGroups)];

                    let html = '';
                    uniqueGroups.forEach(group => {
                        if (grouped[group] && grouped[group].length > 0) {
                            const label = groupLabels[group] || this.capitalize(group);
                            html += `
                                <div class="supplement-category">
                                    <h4 class="supplement-category-title">${label}</h4>
                                    <div class="supplement-category-items">
                                        ${grouped[group].map(sup => `
                                            <div class="supplement-item" data-id="${sup.id}" onclick="Products.toggleSupplement('${sup.id}')">
                                                <div class="supplement-info">
                                                    <div class="supplement-checkbox">
                                                        <i class="fas fa-check" style="font-size: 12px;"></i>
                                                    </div>
                                                    <span class="supplement-name">${sup.name}</span>
                                                </div>
                                                <span class="supplement-price">+${Config.formatPrice(sup.price)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                    });

                    supplementsList.innerHTML = html;
                    console.log('[Products] Supplements rendered:', uniqueGroups, 'total items:', supplements.length);
                } else {
                    supplementsContainer.classList.add('hidden');
                    supplementsContainer.style.display = 'none';
                }
            }

            // Reset sélection visuelle (nouvelle ouverture)
            document.querySelectorAll('.supplement-item').forEach(item => item.classList.remove('selected'));

            // Drinks selection désactivée (plus de boisson avec menu/duo)
            const drinksContainer = document.getElementById('modalDrinks');
            drinksContainer.classList.add('hidden');
            drinksContainer.style.display = 'none';

            // Render sauce options (for Crousti)
            const sauceContainer = document.getElementById('modalSauce');
            const sauceOptions = document.getElementById('sauceOptions');

            if (product.hasSpecialSauce && product.sauceOptions && product.sauceOptions.length > 0) {
                sauceContainer.classList.remove('hidden');
                sauceContainer.style.display = '';
                sauceOptions.innerHTML = product.sauceOptions.map((sauce, index) => `
                    <div class="sauce-item ${index === 0 ? 'selected' : ''}" data-id="${sauce.id}" onclick="Products.selectSauce('${sauce.id}')">
                        <div class="sauce-radio">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="sauce-name">${sauce.name}</span>
                    </div>
                `).join('');
                // Select first sauce by default
                this.selectedSauce = product.sauceOptions[0];
            } else {
                sauceContainer.classList.add('hidden');
                sauceContainer.style.display = 'none';
            }

            // Render kids options (for Crêpe Kids Salée)
            const kidsContainer = document.getElementById('modalKidsOptions');
            const kidsCrepeOptions = document.getElementById('kidsCrepeOptions');
            const kidsSauceOptions = document.getElementById('kidsSauceOptions');

            if (product.hasKidsOptions && product.kidsOptions) {
                kidsContainer.classList.remove('hidden');
                kidsContainer.style.display = '';

                // Render crepe type options
                if (product.kidsOptions.crepeTypes && product.kidsOptions.crepeTypes.length > 0) {
                    kidsCrepeOptions.innerHTML = product.kidsOptions.crepeTypes.map((crepe, index) => `
                        <div class="sauce-item ${index === 0 ? 'selected' : ''}" data-id="${crepe.id}" onclick="Products.selectKidsCrepe('${crepe.id}')">
                            <div class="sauce-radio">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="sauce-name">${crepe.name}</span>
                        </div>
                    `).join('');
                    this.selectedKidsCrepe = product.kidsOptions.crepeTypes[0];
                }

                // Render sauce options
                if (product.kidsOptions.sauces && product.kidsOptions.sauces.length > 0) {
                    kidsSauceOptions.innerHTML = product.kidsOptions.sauces.map((sauce, index) => `
                        <div class="sauce-item ${index === 0 ? 'selected' : ''}" data-id="${sauce.id}" onclick="Products.selectKidsSauce('${sauce.id}')">
                            <div class="sauce-radio">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="sauce-name">${sauce.name}</span>
                        </div>
                    `).join('');
                    this.selectedKidsSauce = product.kidsOptions.sauces[0];
                }
            } else {
                kidsContainer.classList.add('hidden');
                kidsContainer.style.display = 'none';
            }

            // Render variants (Court/Long for cafe)
            const variantsContainer = document.getElementById('modalVariants');
            const variantOptions = document.getElementById('variantOptions');

            if (product.variants && product.variants.length > 0) {
                variantsContainer.classList.remove('hidden');
                variantsContainer.style.display = '';
                variantOptions.innerHTML = product.variants.map((variant, index) => `
                    <div class="variant-item ${index === 0 ? 'selected' : ''}" data-id="${variant.id}" onclick="Products.selectVariant('${escapeHtml(variant.id)}')">
                        <div class="variant-radio">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="variant-name">${escapeHtml(variant.name)}</span>
                        <span class="variant-price">${Config.formatPrice(variant.price)}</span>
                    </div>
                `).join('');
                // Select first variant by default
                this.selectedVariant = product.variants[0];
            } else {
                variantsContainer.classList.add('hidden');
                variantsContainer.style.display = 'none';
            }

            // Render capsule colors or numbers
            const capsulesContainer = document.getElementById('modalCapsules');
            const capsuleOptions = document.getElementById('capsuleOptions');

            // Café Caps → Couleurs | Café L'Or → Numéros
            const hasCapsuleColors = product.capsuleColors && product.capsuleColors.length > 0;
            const hasCapsuleNumbers = product.capsuleNumbers && product.capsuleNumbers.length > 0;

            if (hasCapsuleColors || hasCapsuleNumbers) {
                capsulesContainer.classList.remove('hidden');
                capsulesContainer.style.display = '';

                if (hasCapsuleColors) {
                    // Café Caps : afficher les couleurs capsules
                    const uniqueColors = [...new Set(product.capsuleColors)];
                    console.log('Café Caps - Couleurs capsules:', uniqueColors);

                    // Titre pour Café Caps
                    capsulesContainer.querySelector('h4').innerHTML = '<i class="fas fa-palette"></i> Couleur capsule';

                    capsuleOptions.innerHTML = uniqueColors.map((colorName, index) => {
                        const colorCode = this.getCapsuleColorCode(colorName);
                        return `
                            <div class="capsule-item capsule-color ${index === 0 ? 'selected' : ''}" data-color="${colorName}" onclick="Products.selectCapsule('${colorName}')">
                                <div class="capsule-radio">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="capsule-color-preview" style="background-color: ${colorCode};"></div>
                                <span class="capsule-number">${colorName}</span>
                            </div>
                        `;
                    }).join('');
                    this.selectedCapsule = uniqueColors[0];
                } else if (hasCapsuleNumbers) {
                    // Café L'Or : afficher les numéros (intensité)
                    const uniqueCapsules = [...new Set(product.capsuleNumbers)].sort((a, b) => a - b);
                    console.log('Café L\'Or - Intensité:', uniqueCapsules);

                    // Titre pour Café L'Or
                    capsulesContainer.querySelector('h4').innerHTML = '<i class="fas fa-hashtag"></i> Intensité de capsule L\'Or';

                    capsuleOptions.innerHTML = uniqueCapsules.map((num, index) => `
                        <div class="capsule-item ${index === 0 ? 'selected' : ''}" data-number="${num}" onclick="Products.selectCapsule(${num})">
                            <div class="capsule-radio">
                                <i class="fas fa-check"></i>
                            </div>
                            <span class="capsule-number">${num}</span>
                        </div>
                    `).join('');
                    this.selectedCapsule = uniqueCapsules[0];
                }
            } else {
                capsulesContainer.classList.add('hidden');
                capsulesContainer.style.display = 'none';
            }

            // Render viennoiserie options
            const viennoiserieContainer = document.getElementById('modalViennoiserie');
            const viennoiserieOptions = document.getElementById('viennoiserieOptions');

            if (product.hasViennoiserieOptions && product.viennoiserieOptions && product.viennoiserieOptions.length > 0) {
                viennoiserieContainer.classList.remove('hidden');
                viennoiserieContainer.style.display = '';
                viennoiserieOptions.innerHTML = product.viennoiserieOptions.map((viennoiserie, index) => `
                    <div class="viennoiserie-item ${index === 0 ? 'selected' : ''}" data-id="${viennoiserie.id}" onclick="Products.selectViennoiserie('${viennoiserie.id}')">
                        <div class="viennoiserie-radio">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="viennoiserie-name">${viennoiserie.name}</span>
                    </div>
                `).join('');
                // Select first viennoiserie by default
                this.selectedViennoiserie = product.viennoiserieOptions[0];
            } else {
                viennoiserieContainer.classList.add('hidden');
                viennoiserieContainer.style.display = 'none';
            }

            // Render pâtisserie options
            const patisserieContainer = document.getElementById('modalPatisserie');
            const patisserieOptions = document.getElementById('patisserieOptions');

            if (product.hasPâtisserieOptions && product.pâtisserieOptions && product.pâtisserieOptions.length > 0) {
                // Filtrer les options disponibles seulement
                const availablePatisseries = product.pâtisserieOptions.filter(p => !p.status || p.status === 'available');

                if (availablePatisseries.length > 0) {
                    patisserieContainer.classList.remove('hidden');
                    patisserieContainer.style.display = '';
                    patisserieOptions.innerHTML = availablePatisseries.map((patisserie, index) => `
                        <div class="patisserie-item ${index === 0 ? 'selected' : ''}" data-id="${patisserie.id}" onclick="Products.selectPatisserie('${patisserie.id}')">
                            <div class="patisserie-image-container">
                                <img src="../../${patisserie.image}" alt="${patisserie.name}" class="patisserie-image" loading="lazy" onerror="this.style.display='none'">
                            </div>
                            <div class="patisserie-divider"></div>
                            <div class="patisserie-name">${patisserie.name}</div>
                            <div class="patisserie-divider"></div>
                            <div class="patisserie-price">${Config.formatPrice(patisserie.price)}</div>
                        </div>
                    `).join('');
                    // Select first pâtisserie by default
                    this.selectedPatisserie = availablePatisseries[0];
                } else {
                    patisserieContainer.classList.add('hidden');
                    patisserieContainer.style.display = 'none';
                }
            } else {
                patisserieContainer.classList.add('hidden');
                patisserieContainer.style.display = 'none';
            }

            // Render beverage options (Soda, Jus, Jus Frais, Smoothie)
            const beverageContainer = document.getElementById('modalBeverage');
            const beverageOptions = document.getElementById('beverageOptions');
            const beverageTitle = document.getElementById('beverageTitle');

            if (product.hasBeverageOptions && product.beverageOptions && product.beverageOptions.length > 0) {
                // Filtrer les options disponibles seulement
                const availableBeverages = product.beverageOptions.filter(b => !b.status || b.status === 'available');

                if (availableBeverages.length > 0) {
                    beverageContainer.classList.remove('hidden');
                    beverageContainer.style.display = '';
                    beverageTitle.textContent = `Choisissez votre ${product.name.toLowerCase()}`;
                    beverageOptions.innerHTML = availableBeverages.map((beverage, index) => `
                        <div class="beverage-item ${index === 0 ? 'selected' : ''}" data-id="${beverage.id}" onclick="Products.selectBeverage('${beverage.id}')">
                            ${beverage.image ? `
                                <div class="beverage-image-container">
                                    <img src="../../${beverage.image}" alt="${beverage.name}" class="beverage-image" loading="lazy" onerror="this.style.display='none'">
                                </div>
                            ` : ''}
                            <div class="beverage-divider"></div>
                            <div class="beverage-name">${beverage.name}</div>
                            ${beverage.price ? `
                                <div class="beverage-divider"></div>
                                <div class="beverage-price">${Config.formatPrice(beverage.price)}</div>
                            ` : ''}
                        </div>
                    `).join('');
                    // Select first beverage by default
                    this.selectedBeverage = availableBeverages[0];
                } else {
                    beverageContainer.classList.add('hidden');
                    beverageContainer.style.display = 'none';
                }
            } else {
                beverageContainer.classList.add('hidden');
                beverageContainer.style.display = 'none';
            }

            // Render accompaniment options
            const accompagnementContainer = document.getElementById('modalAccompagnement');
            const accompagnementOptions = document.getElementById('accompagnementOptions');

            // Get category info for accompaniment
            const category = Config.getCategories().find(c => c.id === product.categoryId);
            const hasAccompagnement = category?.hasAccompagnement || product.hasSpecialAccompagnement;

            // Determine which accompaniment options to show
            let accompOptions = [];
            if (product.hasSpecialAccompagnement && product.accompagnementOptions) {
                // Product-specific options (like Crousti with riz OR salade)
                accompOptions = product.accompagnementOptions;
            } else if (category?.hasAccompagnement && category?.accompagnementOptions) {
                // Category-level options (salade for all savory crêpes)
                accompOptions = category.accompagnementOptions;
            }

            if (hasAccompagnement && accompOptions.length > 0) {
                accompagnementContainer.classList.remove('hidden');
                accompagnementContainer.style.display = '';
                accompagnementOptions.innerHTML = accompOptions.map(acc => `
                    <div class="accompagnement-item" data-id="${acc}" onclick="Products.toggleAccompagnement('${acc}')">
                        <div class="accompagnement-checkbox">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="accompagnement-name">${this.capitalize(acc)}</span>
                        <span class="accompagnement-price">Gratuit</span>
                    </div>
                `).join('');
            } else {
                accompagnementContainer.classList.add('hidden');
                accompagnementContainer.style.display = 'none';
            }

            this.updateModalUI();
        }); // Fin du requestAnimationFrame
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

        // Drinks section désactivée (plus de boisson avec menu/duo)
        const drinksContainer = document.getElementById('modalDrinks');
        // Toujours masquer les boissons
        drinksContainer.classList.add('hidden');
        drinksContainer.style.display = 'none';
        this.selectedDrink = null;

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
     * Select sauce option (for Crousti)
     */
    selectSauce(sauceId) {
        if (!this.currentProduct?.sauceOptions) return;

        const sauce = this.currentProduct.sauceOptions.find(s => s.id === sauceId);
        this.selectedSauce = sauce || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('.sauce-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === sauceId);
        });
    },

    /**
     * Select kids crepe type (Fumée/Poulet)
     */
    selectKidsCrepe(crepeId) {
        if (!this.currentProduct?.kidsOptions?.crepeTypes) return;

        const crepe = this.currentProduct.kidsOptions.crepeTypes.find(c => c.id === crepeId);
        this.selectedKidsCrepe = crepe || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('#kidsCrepeOptions .sauce-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === crepeId);
        });
    },

    /**
     * Select kids sauce (Mayonnaise/Ketchup)
     */
    selectKidsSauce(sauceId) {
        if (!this.currentProduct?.kidsOptions?.sauces) return;

        const sauce = this.currentProduct.kidsOptions.sauces.find(s => s.id === sauceId);
        this.selectedKidsSauce = sauce || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('#kidsSauceOptions .sauce-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === sauceId);
        });
    },

    /**
     * Select viennoiserie option (Croissant/Pain au Chocolat)
     */
    selectViennoiserie(viennoiserieId) {
        if (!this.currentProduct?.viennoiserieOptions) return;

        const viennoiserie = this.currentProduct.viennoiserieOptions.find(v => v.id === viennoiserieId);
        this.selectedViennoiserie = viennoiserie || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('.viennoiserie-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === viennoiserieId);
        });
    },

    /**
     * Select pâtisserie option (with photo and individual pricing)
     */
    selectPatisserie(patisserieId) {
        if (!this.currentProduct?.pâtisserieOptions) return;

        const patisserie = this.currentProduct.pâtisserieOptions.find(p => p.id === patisserieId);
        this.selectedPatisserie = patisserie || null;

        // Update UI - only one selected
        document.querySelectorAll('.patisserie-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === patisserieId);
        });

        // Update total price since pâtisserie has individual pricing
        this.updateModalUI();
    },

    /**
     * Select beverage option (Soda/Jus/Jus Frais/Smoothie)
     */
    selectBeverage(beverageId) {
        if (!this.currentProduct?.beverageOptions) return;

        const beverage = this.currentProduct.beverageOptions.find(b => b.id === beverageId);
        this.selectedBeverage = beverage || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('.beverage-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === beverageId);
        });

        // Update total price since beverage has individual pricing
        this.updateModalUI();
    },

    /**
     * Select variant (Court/Long for cafe)
     */
    selectVariant(variantId) {
        if (!this.currentProduct?.variants) return;

        const variant = this.currentProduct.variants.find(v => v.id === variantId);
        this.selectedVariant = variant || null;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('.variant-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === variantId);
        });

        // Update price since variant changes the base price
        this.updateModalUI();
    },

    /**
     * Get color code for capsule color name
     */
    getCapsuleColorCode(colorName) {
        const colorMap = {
            'mauve': '#9b87f5',
            'marron': '#8b4513',
            'noir': '#1a1a1a',
            'bleu': '#2563eb',
            'rouge': '#dc2626',
            'orange': '#ea580c',
            'vert': '#16a34a',
            'jaune': '#eab308',
            'rose': '#ec4899',
            'violet': '#7c3aed'
        };
        const normalized = colorName.toLowerCase().trim();
        return colorMap[normalized] || '#6b7280'; // gray fallback
    },

    /**
     * Select capsule number or color (for cafe-caps and cafe-lor)
     */
    selectCapsule(capsuleValue) {
        const hasCapsuleColors = this.currentProduct?.capsuleColors && this.currentProduct.capsuleColors.length > 0;
        const hasCapsuleNumbers = this.currentProduct?.capsuleNumbers && this.currentProduct.capsuleNumbers.length > 0;

        if (!hasCapsuleColors && !hasCapsuleNumbers) return;

        this.selectedCapsule = capsuleValue;

        // Update UI - radio button style (only one selected)
        document.querySelectorAll('.capsule-item').forEach(item => {
            if (hasCapsuleColors) {
                // Compare color names
                item.classList.toggle('selected', item.dataset.color === capsuleValue);
            } else {
                // Compare numbers
                item.classList.toggle('selected', parseInt(item.dataset.number) === capsuleValue);
            }
        });
    },

    /**
     * Toggle accompaniment selection
     */
    toggleAccompagnement(accId) {
        // Check if this product has special accompaniment (mutually exclusive like Crousti)
        const hasSpecialAcc = this.currentProduct?.hasSpecialAccompagnement;

        if (hasSpecialAcc) {
            // Radio button behavior - only one can be selected
            if (this.selectedAccompagnement === accId) {
                this.selectedAccompagnement = null;
            } else {
                this.selectedAccompagnement = accId;
            }
        } else {
            // Checkbox behavior for regular accompaniment
            if (this.selectedAccompagnement === accId) {
                this.selectedAccompagnement = null;
            } else {
                this.selectedAccompagnement = accId;
            }
        }

        // Update UI
        document.querySelectorAll('.accompagnement-item').forEach(item => {
            item.classList.toggle('selected', item.dataset.id === this.selectedAccompagnement);
        });
    },

    /**
     * Add formule directly to cart (without modal)
     */
    addFormuleDirectly(formuleId) {
        const formule = Config.getFormule(formuleId);
        if (!formule) {
            console.error('Formule not found:', formuleId);
            return;
        }

        console.log('Adding formule to cart:', formule.name);

        // Add formule to cart with basic info
        Cart.addItem({
            id: formule.id,
            name: formule.name,
            image: formule.image,
            price: formule.price,
            categoryId: 'formules',
            isFormule: true,
            includes: formule.includes
        }, 1, [], {});

        console.log('Formule added to cart, opening mini cart...');

        // Ouvrir le mini-cart automatiquement (desktop uniquement)
        if (window.innerWidth > 768) {
            const miniCart = document.getElementById('miniCart');
            if (miniCart) {
                miniCart.classList.add('active');
                console.log('Mini cart opened');
            }
        }
    },

    /**
     * Open formule modal with interactive component selection
     */
    openFormuleModal(formuleId) {
        const formule = Config.getFormule(formuleId);
        if (!formule) return;

        // Initialize formule as current product with selections storage
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

        // Initialize formule selections
        this.formuleSelections = {};
        this.isFormuleValid = false;
        if (formule.includes && formule.includes.length > 0) {
            formule.includes.forEach((include, index) => {
                this.formuleSelections[index] = null;
            });
        }

        const modal = document.getElementById('productModal');

        // Set image with fallback
        const modalImage = document.getElementById('modalImage');
        if (formule.image) {
            modalImage.src = '../../' + formule.image;
            modalImage.onerror = () => { modalImage.style.display = 'none'; };
            modalImage.style.display = 'block';
        } else {
            modalImage.style.display = 'none';
        }

        document.getElementById('modalTitle').textContent = formule.name;
        document.getElementById('modalDescription').textContent = formule.description || '';

        // Hide menu toggle for formules (fixed price)
        document.getElementById('menuToggleSection').style.display = 'none';

        // Hide ingredients section for formules
        document.getElementById('modalIngredients').classList.add('hidden');

        // Hide supplements for formules
        document.getElementById('modalSupplements').classList.add('hidden');

        // Hide drinks for formules
        document.getElementById('modalDrinks').classList.add('hidden');

        // Hide all variant/options sections
        const sectionsToHide = ['modalVariants', 'modalCapsules', 'modalSauce', 'modalKidsOptions',
                                'modalViennoiserie', 'modalPatisserie', 'modalBeverage', 'modalAccompagnement'];
        sectionsToHide.forEach(id => {
            const section = document.getElementById(id);
            if (section) section.classList.add('hidden');
        });

        // Show and populate formule includes with interactive selectors
        this.renderFormuleSelectorsInteractive(formule);

        // Set price
        document.getElementById('addToCartPrice').textContent = Config.formatPrice(formule.price);

        this.updateModalUI();
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /**
     * Render formule includes in modal
     */
    renderFormuleIncludes(formule) {
        const includesSection = document.getElementById('modalFormuleIncludes');
        const includesList = document.getElementById('formuleIncludesList');

        if (!includesSection || !includesList) return;

        // Check if formule has includes
        if (!formule.includes || formule.includes.length === 0) {
            includesSection.classList.add('hidden');
            return;
        }

        // Show the section
        includesSection.classList.remove('hidden');

        // Render includes (simple display without selectors)
        includesList.innerHTML = formule.includes.map(include => {
            const includeLabel = include.label || `${include.quantity || 1}x ${include.type}`;

            return `
                <div class="formule-include-item">
                    <i class="fas fa-check-circle"></i>
                    <div class="formule-include-content">
                        <div class="formule-include-title">${includeLabel}</div>
                    </div>
                </div>
            `;
        }).join('');
    },

    /**
     * Render formule includes with interactive selectors
     */
    renderFormuleSelectorsInteractive(formule) {
        console.log('[renderFormuleSelectorsInteractive] Starting with formule:', formule);

        const includesSection = document.getElementById('modalFormuleIncludes');
        const includesList = document.getElementById('formuleIncludesList');

        if (!includesSection || !includesList) {
            console.error('[renderFormuleSelectorsInteractive] Missing DOM elements!', {
                includesSection: !!includesSection,
                includesList: !!includesList
            });
            return;
        }

        // Check if formule has includes
        if (!formule.includes || formule.includes.length === 0) {
            console.warn('[renderFormuleSelectorsInteractive] No includes found');
            includesSection.classList.add('hidden');
            return;
        }

        console.log('[renderFormuleSelectorsInteractive] Found', formule.includes.length, 'includes');

        // Show the section (DOIT reset display car openProductModal() le cache)
        includesSection.classList.remove('hidden');
        includesSection.style.display = '';

        // Render interactive selectors for each include
        includesList.innerHTML = formule.includes.map((include, index) => {
            const selectId = `formule-select-${index}`;
            const includeLabel = include.label || `${include.quantity || 1}x ${include.type}`;

            // Get available products based on include rules
            const availableProducts = this.getAvailableProductsForInclude(include);

            if (availableProducts.length === 0) {
                return `
                    <div class="formule-include-item">
                        <i class="fas fa-check-circle"></i>
                        <div class="formule-include-content">
                            <div class="formule-include-title">${includeLabel}</div>
                            <div class="formule-include-note">Aucun produit disponible</div>
                        </div>
                    </div>
                `;
            }

            // Create selector with all available products from the category
            const optionsHtml = availableProducts.map(product => {
                return `<option value="${product.id}">${product.name}</option>`;
            }).join('');

            // Determine icon based on categoryId or productId
            const iconName = this.getIconForInclude(include);

            // Add choiceGroup data attribute if defined
            const choiceGroupAttr = include.choiceGroup ? `data-choice-group="${include.choiceGroup}"` : '';

            return `
                <div class="formule-include-item">
                    <i class="fas fa-${iconName}"></i>
                    <div class="formule-include-content">
                        <label for="${selectId}" class="formule-include-title">
                            ${includeLabel}
                        </label>
                        <select id="${selectId}" class="formule-selector" data-include-index="${index}" data-include-type="${include.type}" ${choiceGroupAttr}>
                            <option value="">-- Choisissez --</option>
                            ${optionsHtml}
                        </select>
                    </div>
                </div>
            `;
        }).join('');

        // Attach change event listeners to all selectors
        includesList.querySelectorAll('.formule-selector').forEach(selector => {
            selector.addEventListener('change', (e) => {
                const includeIndex = parseInt(e.target.dataset.includeIndex);
                const selectedProductId = e.target.value;
                const choiceGroup = e.target.dataset.choiceGroup;

                // If this selector has a choiceGroup and a value is selected,
                // clear all other selections in the same group (replacement logic)
                if (choiceGroup && selectedProductId) {
                    includesList.querySelectorAll(`.formule-selector[data-choice-group="${choiceGroup}"]`).forEach(otherSelector => {
                        const otherIndex = parseInt(otherSelector.dataset.includeIndex);
                        if (otherIndex !== includeIndex) {
                            // Clear other selections in the same group
                            if (this.formuleSelections[otherIndex]) {
                                console.log(`[REMPLACEMENT] choiceGroup="${choiceGroup}" : index ${otherIndex} EFFACÉ (remplacé par index ${includeIndex})`);
                            }
                            this.formuleSelections[otherIndex] = null;
                            otherSelector.value = '';
                        }
                    });
                }

                this.formuleSelections[includeIndex] = selectedProductId || null;
                this.updateFormuleSelectorStates(formule);
                this.validateFormuleSelections();
            });
        });

        // Initialize selector states
        this.updateFormuleSelectorStates(formule);
    },

    /**
     * Update formule selector states based on selections and limits
     * Uses choiceGroup for grouping if available, otherwise falls back to categoryId
     */
    updateFormuleSelectorStates(formule) {
        if (!formule.includes || formule.includes.length === 0) return;

        // Calculate selection counts and limits for each group
        const groupLimits = {};
        const groupSelectionCounts = {};

        // Helper function to get group key for an include
        // Priority: choiceGroup > categoryId > productId > type
        const getGroupKey = (include) => {
            // If choiceGroup is defined, use it (primary grouping mechanism)
            if (include.choiceGroup) {
                return `group-${include.choiceGroup}`;
            }
            // Fallback to legacy behavior for backwards compatibility
            if (include.type === 'category' && include.categoryId) {
                return `category-${include.categoryId}`;
            } else if (include.type === 'product' && include.productId) {
                return `product-${include.productId}`;
            }
            return `type-${include.type}`;
        };

        // Calculate limits for each group
        // If multiple includes share the same choiceGroup, they share ONE global limit
        formule.includes.forEach((include, index) => {
            const key = getGroupKey(include);
            const quantity = include.quantity || 1;

            if (!groupLimits[key]) {
                // Use the first quantity found for this group
                groupLimits[key] = quantity;
                groupSelectionCounts[key] = 0;
            }

            // Count current selections
            if (this.formuleSelections[index]) {
                groupSelectionCounts[key]++;
            }
        });

        console.log('[updateFormuleSelectorStates] Limits par groupe:', groupLimits);
        console.log('[updateFormuleSelectorStates] Sélections par groupe:', groupSelectionCounts);

        // Update selector states for each include
        formule.includes.forEach((include, index) => {
            const key = getGroupKey(include);
            const selector = document.querySelector(`[data-include-index="${index}"]`);

            if (!selector) return;

            const hasSelection = !!this.formuleSelections[index];
            const limitReached = groupSelectionCounts[key] >= groupLimits[key];

            // Disable if: no selection AND limit reached for this group
            if (!hasSelection && limitReached) {
                selector.disabled = true;
                selector.style.opacity = '0.5';
                selector.style.cursor = 'not-allowed';
            } else {
                selector.disabled = false;
                selector.style.opacity = '1';
                selector.style.cursor = 'pointer';
            }
        });
    },

    /**
     * Get available products for a formule include based on type and categoryId/productId
     */
    getAvailableProductsForInclude(include) {
        let products = [];

        console.log('[getAvailableProductsForInclude]', include);

        // If type is 'product', get specific product by ID
        if (include.type === 'product' && include.productId) {
            const product = Config.getProduct(include.productId);
            console.log('[getAvailableProductsForInclude] Found product:', product);
            if (product && product.status === 'available') {
                products = [product];
            }
        }
        // If type is 'category', get all products from that category
        else if (include.type === 'category' && include.categoryId) {
            // Try to get products by category ID (could be string or number)
            let categoryProducts = Config.getProductsByCategory(include.categoryId);

            // If nothing found and categoryId is a string, try to find category by matching name or slug
            if (categoryProducts.length === 0 && typeof include.categoryId === 'string') {
                const allCategories = Config.menu?.categories || [];
                const category = allCategories.find(cat =>
                    cat.id === include.categoryId ||
                    cat.name?.toLowerCase() === include.categoryId.toLowerCase() ||
                    cat.slug === include.categoryId
                );
                if (category) {
                    categoryProducts = category.items || [];
                }
            }

            console.log('[getAvailableProductsForInclude] Found products in category:', categoryProducts.length);
            products = categoryProducts.filter(p => p && p.status === 'available');
        }

        console.log('[getAvailableProductsForInclude] Returning products:', products.length);
        return products;
    },

    /**
     * Get icon for formule include based on categoryId or productId
     */
    getIconForInclude(include) {
        // Map category IDs to icons
        const categoryIconMap = {
            'pizzas': 'pizza-slice',
            'burgers': 'hamburger',
            'tacos': 'taco',
            'paninis': 'bread-slice',
            'salades': 'salad',
            'pates': 'bowl-rice',
            'gratins': 'bowl-rice',
            'crepes': 'cookie',
            'gaufres': 'waffle',
            'desserts': 'ice-cream',
            'boissons': 'glass-whiskey',
            'sodas-eaux': 'bottle-water',
            'boissons-chaudes': 'mug-hot',
            'jus-cocktails': 'cocktail',
            'accompagnements': 'utensils',
            'sides': 'french-fries'
        };

        // If type is category, use categoryId to find icon
        if (include.type === 'category' && include.categoryId) {
            return categoryIconMap[include.categoryId] || 'utensils';
        }

        // If type is product, try to find the product and use its category
        if (include.type === 'product' && include.productId) {
            const product = Config.getProduct(include.productId);
            if (product && product.categoryId) {
                return categoryIconMap[product.categoryId] || 'utensils';
            }
        }

        return 'check-circle';
    },

    /**
     * Get icon for formule include type (legacy method, kept for compatibility)
     */
    getIconForType(type) {
        const iconMap = {
            'pizza': 'pizza-slice',
            'boisson': 'glass-whiskey',
            'pate': 'bowl-rice',
            'gratin': 'bowl-rice',
            'dessert': 'ice-cream',
            'accompagnement': 'utensils'
        };
        return iconMap[type?.toLowerCase()] || 'check-circle';
    },

    /**
     * Validate that all required formule selections are made
     * Validates by choiceGroup: each group needs at least 1 selection
     */
    validateFormuleSelections() {
        console.log("🔥🔥🔥 TRACE: validateFormuleSelections APPELÉ", {file: "products.js"});
        if (!this.currentProduct?.isFormule) return true;

        const formule = this.currentProduct;
        if (!formule.includes || formule.includes.length === 0) return true;

        // Build list of required groups and check selections per group
        const requiredGroups = new Set();
        const selectedByGroup = {};

        formule.includes.forEach((include, index) => {
            // 🔥 TRACE: Voir include AVANT fallback
            console.log(`🔥 INCLUDE[${index}]:`, {
                label: include.label,
                choiceGroup: include.choiceGroup,
                categoryId: include.categoryId,
                type: include.type
            });
            // Determine group key (choiceGroup if defined, otherwise unique per include)
            const groupKey = include.choiceGroup || `include-${index}`;
            console.log(`🔥 GROUPKEY[${index}]:`, groupKey, include.choiceGroup ? "(choiceGroup)" : "(FALLBACK)");
            requiredGroups.add(groupKey);

            if (!selectedByGroup[groupKey]) {
                selectedByGroup[groupKey] = [];
            }

            // If this include has a selection, add it to the group
            if (this.formuleSelections[index]) {
                selectedByGroup[groupKey].push({
                    index,
                    productId: this.formuleSelections[index]
                });
            }
        });

        // Validate: each required group must have at least 1 selection
        let allGroupsValid = true;
        requiredGroups.forEach(group => {
            if (!selectedByGroup[group] || selectedByGroup[group].length === 0) {
                allGroupsValid = false;
            }
        });

        console.log('[validateFormuleSelections] Groupes requis:', Array.from(requiredGroups));
        console.log('[validateFormuleSelections] Sélections par groupe:', selectedByGroup);
        console.log('[validateFormuleSelections] Formule valide:', allGroupsValid);

        // STOCKER LE RÉSULTAT DANS UNE VARIABLE CENTRALE
        this.isFormuleValid = allGroupsValid;

        // Enable/disable add to cart button - SYNCHRONISÉ AVEC this.isFormuleValid
        const addButton = document.getElementById('addToCartBtn');
        if (addButton) {
            addButton.disabled = !this.isFormuleValid;
            if (this.isFormuleValid) {
                addButton.classList.remove('disabled');
                console.log('🔥 BOUTON ACTIVÉ (isFormuleValid=true)');
            } else {
                addButton.classList.add('disabled');
                console.log('🔥 BOUTON DÉSACTIVÉ (isFormuleValid=false)');
            }
        }

        return this.isFormuleValid;
    },

    /**
     * Resolve formule selections to actual product objects
     * Only includes ONE product per choiceGroup (no duplicates)
     */
    resolveFormuleSelections() {
        console.log("🔥🔥🔥 TRACE: resolveFormuleSelections APPELÉ", {file: "products.js"});
        const resolved = [];
        const formule = this.currentProduct;

        if (!formule?.includes || !this.formuleSelections) return resolved;

        // Track which groups have already been added to avoid duplicates
        const addedGroups = new Set();

        formule.includes.forEach((include, index) => {
            const selectedProductId = this.formuleSelections[index];
            if (!selectedProductId) return;

            // Determine group key
            const groupKey = include.choiceGroup || `include-${index}`;

            // Skip if this group already has a product added
            if (addedGroups.has(groupKey)) {
                console.log(`[resolveFormuleSelections] Groupe "${groupKey}" déjà ajouté, skip index ${index}`);
                return;
            }

            const product = Config.getProduct(selectedProductId);
            if (product) {
                resolved.push({
                    type: include.type,
                    label: include.choiceGroup || include.label || include.type,
                    choiceGroup: include.choiceGroup,
                    product: {
                        id: product.id,
                        name: product.name,
                        price: product.priceSolo || product.price
                    }
                });
                addedGroups.add(groupKey);
                console.log(`[resolveFormuleSelections] Ajouté: groupe="${groupKey}", produit="${product.name}"`);
            }
        });

        console.log('[resolveFormuleSelections] Total produits résolus:', resolved.length);
        return resolved;
    },

    /**
     * Toggle supplement selection
     */
    toggleSupplement(supId) {
        const supIdStr = String(supId);

        // Lookup UNIQUE via byId (pas de fallback)
        const sup = Config.supplements.byId?.[supIdStr];
        if (!sup) {
            console.error('[toggleSupplement] ERREUR: supplément absent de byId:', supIdStr);
            return;
        }

        const price = parseFloat(sup.price);
        if (isNaN(price) || price < 0) {
            console.error('[toggleSupplement] ERREUR: prix invalide:', sup.name, sup.price);
            return;
        }

        // Toggle dans selectedSupplements
        const index = this.selectedSupplements.findIndex(s => String(s.id) === supIdStr);
        if (index >= 0) {
            this.selectedSupplements.splice(index, 1);
        } else {
            this.selectedSupplements.push(sup);
        }

        // Sync UI
        document.querySelectorAll('.supplement-item').forEach(item => {
            item.classList.toggle('selected', this.selectedSupplements.some(s => String(s.id) === item.dataset.id));
        });

        this.updateModalUI();
    },

    /**
     * Update modal UI (quantity, total price)
     */
    updateModalUI() {
        document.getElementById('qtyValue').textContent = this.currentQuantity;

        // Calculate total based on menu type or special product type
        let basePrice = 0;

        // If variant is selected (for cafe-caps, cafe-lor), use variant price
        if (this.selectedVariant) {
            basePrice = this.selectedVariant.price || 0;
        }
        // If pâtisserie is selected, use its price (pâtisserie products have individual pricing)
        else if (this.selectedPatisserie) {
            basePrice = this.selectedPatisserie.price || 0;
        }
        // If beverage is selected (jus, smoothie, salade), use its price
        else if (this.selectedBeverage) {
            basePrice = this.selectedBeverage.price || 0;
        } else if (this.menuType === 'menu' && this.currentProduct?.priceMenu) {
            basePrice = this.currentProduct.priceMenu;
        } else {
            basePrice = this.currentProduct?.price || this.currentProduct?.priceSolo || 0;
        }

        let total = parseFloat(basePrice) || 0;

        // Add supplements (normaliser chaque prix en Number)
        this.selectedSupplements.forEach(sup => {
            const supPrice = parseFloat(sup.price) || 0;
            total += supPrice;
        });

        total *= this.currentQuantity;

        // Log simplifié (évite overhead DevTools)
        // console.log("🧪 TOTAL:", total);

        document.getElementById('addToCartPrice').textContent = Config.formatPrice(total);
    },

    /**
     * Add current modal product to cart
     */
    addCurrentToCart() {
        if (!this.currentProduct) return;

        // VÉRIFICATION CENTRALE: Si c'est une formule, utiliser this.isFormuleValid (pas de re-validation)
        if (this.currentProduct.isFormule && this.isFormuleValid !== true) {
            return;
        }

        // Create product with correct price based on menu type
        const productToAdd = { ...this.currentProduct };

        // Utiliser le prix menu si sélectionné, sinon prix solo
        if (this.menuType === 'menu' && this.currentProduct.priceMenu) {
            productToAdd.price = this.currentProduct.priceMenu;
        } else {
            productToAdd.price = this.currentProduct.priceSolo || this.currentProduct.price;
        }

        // For formules, resolve the selected products
        let formuleProducts = null;
        if (this.currentProduct.isFormule && this.formuleSelections) {
            formuleProducts = this.resolveFormuleSelections();
            // NE PAS modifier le nom - les produits seront affichés séparément dans le panier
        }

        Cart.addItem(
            productToAdd,
            this.currentQuantity,
            [...this.selectedSupplements],
            {
                menuType: this.menuType,
                removedIngredients: [...this.removedIngredients],
                selectedDrink: this.selectedDrink ? { ...this.selectedDrink } : null,
                selectedSauce: this.selectedSauce ? { ...this.selectedSauce } : null,
                selectedAccompagnement: this.selectedAccompagnement,
                selectedViennoiserie: this.selectedViennoiserie ? { ...this.selectedViennoiserie } : null,
                selectedPatisserie: this.selectedPatisserie ? { ...this.selectedPatisserie } : null,
                selectedBeverage: this.selectedBeverage ? { ...this.selectedBeverage } : null,
                selectedKidsCrepe: this.selectedKidsCrepe ? { ...this.selectedKidsCrepe } : null,
                selectedKidsSauce: this.selectedKidsSauce ? { ...this.selectedKidsSauce } : null,
                selectedVariant: this.selectedVariant ? { ...this.selectedVariant } : null,
                selectedCapsule: this.selectedCapsule,
                formuleSelections: formuleProducts
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
        // Protection: éviter la multiplication des event listeners
        if (this.searchSetup) return;
        this.searchSetup = true;

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
        const cards = document.querySelectorAll('.product-card-new');

        cards.forEach(card => {
            const name = card.querySelector('.product-name-new')?.textContent.toLowerCase() || '';
            const desc = card.querySelector('.product-desc-new')?.textContent.toLowerCase() || '';

            if (!term || name.includes(term) || desc.includes(term)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Hide empty sections
        document.querySelectorAll('.product-section').forEach(section => {
            const visibleCards = section.querySelectorAll('.product-card-new:not([style*="display: none"])');
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
