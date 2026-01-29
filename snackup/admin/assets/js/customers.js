// ========== GESTION CLIENTS ENRICHIE ==========

let customersData = [];
let selectedCustomers = [];
let availableTags = [];
let currentFilter = null;
let currentCustomer = null;

// Templates WhatsApp
const whatsappTemplates = {
    promo: {
        title: '🎁 Promotion spéciale',
        message: 'Bonjour {NOM},\n\nVoici un code promo spécial rien que pour vous!\n\nCode: PROMO10\nValable jusqu\'au [DATE À REMPLACER]\n\nMerci de votre fidélité! 🎉\n- {RESTO}'
    },
    inactive: {
        title: '😊 On vous a pas vu depuis longtemps',
        message: 'Bonjour {NOM},\n\nCela fait un moment qu\'on ne vous a pas vu!\n\nVotre snack préféré vous attend. Profitez de 10% de réduction sur votre prochaine commande.\n\nÀ très bientôt!\n- {RESTO}'
    },
    loyalty: {
        title: '⭐ Points fidélité',
        message: 'Bonjour {NOM},\n\nVous avez {POINTS} points de fidélité!\n\n{LOYALTY_MSG}\n\nMerci pour votre fidélité! 🎉\n- {RESTO}'
    }
};

// Charger les tags disponibles
async function loadAvailableTags() {
    try {
        const response = await fetch('api/customers.php?action=get_available_tags');
        const data = await response.json();
        if (data.success) {
            availableTags = data.tags;
        }
    } catch (error) {
        console.error('Erreur chargement tags:', error);
    }
}

// Protection contre appels multiples
let isLoadingCustomers = false;
// ⚡ PAGINATION: Variable pour suivre la page actuelle
let currentPage = 1;
let paginationData = null;

// Charger les clients
async function loadCustomers(filterTag = null, page = 1) {
    // ⚡ Protection contre surcharge
    if (isLoadingCustomers) {
        console.log('Chargement déjà en cours...');
        return;
    }

    isLoadingCustomers = true;

    try {
        let url = `api/customers.php?action=list&page=${page}`;
        if (filterTag) {
            url += '&filter_tag=' + encodeURIComponent(filterTag);
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            customersData = data.customers;
            currentPage = page;
            paginationData = data.pagination;
            renderCustomers(data.customers);
            updateCustomerStats(data.stats);
            // ⚡ PAGINATION: Afficher les boutons
            renderPagination(data.pagination, filterTag);
            currentFilter = filterTag;
        }
    } catch (error) {
        console.error('Erreur chargement clients:', error);
        document.getElementById('customers-list').innerHTML =
            '<p class="text-red-400 text-center py-8">Erreur de chargement</p>';
    } finally {
        isLoadingCustomers = false;
    }
}

// Afficher les clients (cartes enrichies)
function renderCustomers(customers) {
    const customersList = document.getElementById('customers-list');

    if (customers.length === 0) {
        customersList.innerHTML = `
            <div class="col-span-full glass-strong rounded-2xl p-12 text-center">
                <i class="fas fa-users text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">Aucun client ${currentFilter ? 'avec ce tag' : 'pour le moment'}</p>
            </div>
        `;
        return;
    }

    customersList.innerHTML = customers.map(customer => {
        const defaultAddress = customer.addresses?.find(a => a.is_default);
        const hasAllergies = customer.preferences?.allergies?.length > 0;

        return `
            <div class="glass-strong rounded-2xl p-6 hover:bg-white/10 transition-all cursor-pointer" onclick="showCustomerDetails('${customer.id}')">
                <!-- Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2 flex-wrap">
                            <h3 class="text-white font-semibold text-lg">${escapeHtml(customer.name)}</h3>
                            ${customer.orders_count >= 10 ? '<span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-500/20 text-yellow-400">VIP</span>' : ''}
                            ${hasAllergies ? '<span class="px-2 py-1 rounded-full text-xs bg-red-500/20 text-red-400">⚠️ Allergies</span>' : ''}
                        </div>

                        <!-- Tags -->
                        ${customer.tags && customer.tags.length > 0 ? `
                            <div class="flex flex-wrap gap-1 mb-2">
                                ${customer.tags.slice(0, 3).map(tag => {
                                    const tagInfo = availableTags.find(t => t.value === tag);
                                    const color = tagInfo?.color || '#gray';
                                    return `<span class="px-2 py-0.5 rounded-full text-xs" style="background-color: ${color}20; color: ${color};">${escapeHtml(tag)}</span>`;
                                }).join('')}
                                ${customer.tags.length > 3 ? `<span class="px-2 py-0.5 rounded-full text-xs bg-gray-500/20 text-gray-400">+${customer.tags.length - 3}</span>` : ''}
                            </div>
                        ` : ''}

                        <p class="text-gray-400 text-sm">
                            <i class="fas fa-phone mr-2"></i>${escapeHtml(customer.phone)}
                        </p>

                        <!-- Adresse par défaut -->
                        ${defaultAddress ? `
                            <p class="text-gray-500 text-xs mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i>${escapeHtml(defaultAddress.address).substring(0, 40)}${defaultAddress.address.length > 40 ? '...' : ''}
                            </p>
                        ` : ''}
                    </div>

                    <div class="text-right">
                        <div class="text-2xl font-bold" style="color: var(--primary-color);">
                            ${customer.loyalty_points}
                        </div>
                        <div class="text-xs text-gray-400">points</div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="border-t border-white/10 pt-3 mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-gray-400">Commandes:</span>
                        <span class="text-white font-semibold ml-2">${customer.orders_count}</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Dépensé:</span>
                        <span class="text-white font-semibold ml-2">${Math.round(customer.total_spent)} ${window.CURRENCY || 'EUR'}</span>
                    </div>
                </div>

                <!-- Dernière commande -->
                <div class="mt-3 text-xs text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Dernière commande: ${formatDate(customer.last_order_at)}
                </div>
            </div>
        `;
    }).join('');
}

// Protection contre double-clic
let isShowingDetails = false;

// Afficher détails client (modal enrichi)
async function showCustomerDetails(customerId) {
    // ⚡ Protection contre surcharge
    if (isShowingDetails) {
        console.log('Chargement détails déjà en cours...');
        return;
    }

    isShowingDetails = true;

    try {
        // Charger les détails complets
        const response = await fetch(`api/customers.php?action=get&customer_id=${customerId}`);
        const data = await response.json();

        if (!data.success) {
            showToast('Erreur lors du chargement des détails', 'error');
            return;
        }

        currentCustomer = data.customer;
        const customer = data.customer;

        const modal = document.getElementById('customer-modal');
        if (!modal) {
            console.error('Modal element not found');
            showToast('Erreur: Modal introuvable', 'error');
            return;
        }

        modal.innerHTML = `
            <div class="modal-content" style="background: white; border-radius: 16px; padding: 32px; max-width: 800px; width: 100%;">
                <!-- Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px;">
                    <div>
                        <h2 style="font-size: 1.75rem; font-weight: 700; color: #1f2937; margin-bottom: 8px;">
                            ${escapeHtml(customer.name)}
                        </h2>
                        <div style="color: #6b7280; font-size: 0.95rem;">
                            <i class="fas fa-phone" style="margin-right: 8px;"></i>${escapeHtml(customer.phone)}
                            ${customer.email ? `<span style="margin: 0 8px;">•</span><i class="fas fa-envelope" style="margin-right: 8px;"></i>${escapeHtml(customer.email)}` : ''}
                        </div>
                    </div>
                    <button onclick="closeCustomerModal()" style="background: #ef4444; color: white; border: none; border-radius: 8px; padding: 10px 20px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                </div>

                <!-- Stats principales -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;">
                    <div style="background: #eff6ff; border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #1e40af;">${customer.orders_count}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 4px;">Commandes</div>
                    </div>
                    <div style="background: #f0fdf4; border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #15803d;">${Math.round(customer.total_spent)} ${window.CURRENCY || 'EUR'}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 4px;">Dépensé</div>
                    </div>
                    <div style="background: #fef3c7; border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;">${customer.loyalty_points}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 4px;">Points fidélité</div>
                    </div>
                    <div style="background: #f3f4f6; border-radius: 12px; padding: 20px; text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: #1f2937;">${customer.orders_count > 0 ? Math.round(customer.total_spent / customer.orders_count) : 0} ${window.CURRENCY || 'EUR'}</div>
                        <div style="font-size: 0.875rem; color: #6b7280; margin-top: 4px;">Panier moyen</div>
                    </div>
                </div>

                <!-- Tags -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-weight: 600; color: #1f2937; display: flex; align-items: center;">
                            <i class="fas fa-tags" style="color: #f59e0b; margin-right: 8px;"></i>Tags
                        </h4>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        ${(customer.tags && customer.tags.length > 0) ? customer.tags.map(tag => {
                            const tagInfo = availableTags.find(t => t.value === tag);
                            const color = tagInfo?.color || '#6b7280';
                            return `<span style="background: ${color}20; color: ${color}; padding: 6px 12px; border-radius: 6px; font-size: 0.875rem; font-weight: 600;">${escapeHtml(tag)}</span>`;
                        }).join('') : '<span style="color: #9ca3af; font-size: 0.875rem;">Aucun tag</span>'}
                    </div>
                </div>

                <!-- Adresses -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-weight: 600; color: #1f2937; display: flex; align-items: center;">
                            <i class="fas fa-map-marker-alt" style="color: #f59e0b; margin-right: 8px;"></i>Adresses de livraison
                        </h4>
                    </div>
                    ${(customer.addresses && customer.addresses.length > 0) ? customer.addresses.map(addr => `
                        <div style="background: white; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div style="font-weight: 600; color: #1f2937; margin-bottom: 4px;">
                                        ${escapeHtml(addr.label)}
                                        ${addr.is_default ? '<span style="background: #dcfce7; color: #16a34a; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; margin-left: 8px;">Par défaut</span>' : ''}
                                    </div>
                                    <div style="color: #6b7280; font-size: 0.875rem;">${escapeHtml(addr.address)}</div>
                                    ${addr.notes ? `<div style="color: #9ca3af; font-size: 0.75rem; margin-top: 4px;"><i class="fas fa-comment" style="margin-right: 4px;"></i>${escapeHtml(addr.notes)}</div>` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('') : '<span style="color: #9ca3af; font-size: 0.875rem;">Aucune adresse enregistrée</span>'}
                </div>

                <!-- Préférences & Favoris -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="font-weight: 600; color: #1f2937; display: flex; align-items: center; margin-bottom: 12px;">
                        <i class="fas fa-heart" style="color: #f59e0b; margin-right: 8px;"></i>Préférences & Favoris
                    </h4>

                    ${(customer.preferences?.allergies && customer.preferences.allergies.length > 0) ? `
                        <div style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                            <div style="color: #dc2626; font-weight: 600; font-size: 0.875rem; margin-bottom: 4px;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i>Allergies
                            </div>
                            <div style="color: #991b1b; font-size: 0.875rem;">${customer.preferences.allergies.map(escapeHtml).join(', ')}</div>
                        </div>
                    ` : ''}

                    ${(customer.favorite_products && customer.favorite_products.length > 0) ? `
                        <div>
                            <div style="color: #6b7280; font-size: 0.875rem; margin-bottom: 8px;"><i class="fas fa-star" style="color: #f59e0b; margin-right: 6px;"></i>Produits favoris:</div>
                            ${customer.favorite_products.map((fav, idx) => `
                                <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.875rem;">
                                    <span style="color: #1f2937;">${idx + 1}. ${escapeHtml(fav.name)}</span>
                                    <span style="color: #6b7280;">${fav.count}x commandé</span>
                                </div>
                            `).join('')}
                        </div>
                    ` : '<span style="color: #9ca3af; font-size: 0.875rem;">Aucune préférence enregistrée</span>'}
                </div>

                <!-- Notes Admin (privées) -->
                <div style="background: #fef3c7; border: 2px dashed #f59e0b; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="font-weight: 600; color: #92400e; display: flex; align-items: center; margin-bottom: 12px;">
                        <i class="fas fa-sticky-note" style="color: #f59e0b; margin-right: 8px;"></i>Notes Admin (privées)
                        <span style="background: #92400e; color: #fef3c7; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; margin-left: 8px;">Confidentielles</span>
                    </h4>
                    <textarea
                        id="admin-notes-textarea"
                        placeholder="Ajoutez vos notes privées sur ce client (allergies, préférences, historique d'incidents, etc.)..."
                        style="width: 100%; min-height: 100px; padding: 12px; background: white; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; color: #1f2937; resize: vertical;"
                    >${customer.admin_notes || ''}</textarea>
                    <button
                        onclick="saveAdminNotes('${customer.id}')"
                        style="background: #10b981; color: white; border: none; border-radius: 8px; padding: 8px 16px; cursor: pointer; font-weight: 600; margin-top: 8px; font-size: 0.875rem;">
                        <i class="fas fa-save" style="margin-right: 6px;"></i>Enregistrer les notes
                    </button>
                    <p style="color: #92400e; font-size: 0.7rem; margin-top: 6px; font-style: italic;">
                        <i class="fas fa-lock" style="margin-right: 4px;"></i>Ces notes sont privées et visibles uniquement par les administrateurs.
                    </p>
                </div>

                <!-- Historique commandes -->
                <div style="background: #f9fafb; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="font-weight: 600; color: #1f2937; display: flex; align-items: center; margin-bottom: 12px;">
                        <i class="fas fa-history" style="color: #f59e0b; margin-right: 8px;"></i>Dernières commandes (${customer.order_history?.length || 0})
                    </h4>
                    <div style="max-height: 200px; overflow-y: auto;">
                        ${(customer.order_history && customer.order_history.length > 0) ? customer.order_history.slice(0, 5).map(order => `
                            <div style="background: white; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <div>
                                        <span style="font-weight: 600; color: #1f2937;">${escapeHtml(order.order_number)}</span>
                                        <span style="color: #9ca3af; font-size: 0.75rem; margin-left: 8px;">${formatDate(order.created_at)}</span>
                                    </div>
                                    <span style="font-weight: 700; color: #f59e0b;">${Math.round(order.total)} ${window.CURRENCY || 'EUR'}</span>
                                </div>
                                ${order.items ? `<div style="color: #6b7280; font-size: 0.75rem;">${order.items.split(',').slice(0, 2).join(', ')}${order.items.split(',').length > 2 ? '...' : ''}</div>` : ''}
                            </div>
                        `).join('') : '<span style="color: #9ca3af; font-size: 0.875rem;">Aucune commande</span>'}
                    </div>
                </div>

                <!-- Actions -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 24px;">
                    <button onclick="window.open('https://wa.me/${customer.phone.replace(/[^0-9]/g, '')}', '_blank')" style="background: #25D366; color: white; border: none; border-radius: 8px; padding: 12px; cursor: pointer; font-weight: 600;">
                        <i class="fab fa-whatsapp" style="margin-right: 8px;"></i>WhatsApp
                    </button>
                    <button onclick="addBonusPoints('${customer.id}')" style="background: #f59e0b; color: white; border: none; border-radius: 8px; padding: 12px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-gift" style="margin-right: 8px;"></i>Ajouter points
                    </button>
                </div>
            </div>
        `;

        modal.classList.add('active');
    } catch (error) {
        console.error('Erreur détails client:', error);
        alert('Erreur lors du chargement des détails');
    } finally {
        isShowingDetails = false;
    }
}

// Rendu des tags client
function renderCustomerTags(tags) {
    if (!tags || tags.length === 0) {
        return '<p class="text-gray-500 text-sm">Aucun tag</p>';
    }

    return tags.map(tag => {
        const tagInfo = availableTags.find(t => t.value === tag);
        const color = tagInfo?.color || '#gray';
        return `
            <span class="px-3 py-1.5 rounded-lg flex items-center gap-2" style="background-color: ${color}20; color: ${color};">
                ${escapeHtml(tag)}
                <button onclick="event.stopPropagation(); removeCustomerTag('${currentCustomer.id}', '${escapeHtml(tag)}')" class="hover:bg-white/20 rounded px-1">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </span>
        `;
    }).join('');
}

// Rendu des adresses
function renderCustomerAddresses(addresses) {
    if (!addresses || addresses.length === 0) {
        return '<p class="text-gray-500 text-sm">Aucune adresse enregistrée</p>';
    }

    return addresses.map(addr => `
        <div class="bg-gray-800/50 rounded-lg p-3 flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-white">${escapeHtml(addr.label)}</span>
                    ${addr.is_default ? '<span class="px-2 py-0.5 rounded-full text-xs bg-green-500/20 text-green-400">Par défaut</span>' : ''}
                </div>
                <p class="text-gray-400 text-sm">${escapeHtml(addr.address)}</p>
                ${addr.notes ? `<p class="text-gray-500 text-xs mt-1"><i class="fas fa-comment mr-1"></i>${escapeHtml(addr.notes)}</p>` : ''}
            </div>
            <div class="flex gap-1">
                ${!addr.is_default ? `<button onclick="setDefaultAddress('${currentCustomer.id}', '${addr.id}')" class="px-2 py-1 rounded glass text-white text-xs hover:bg-white/10" title="Définir par défaut"><i class="fas fa-star"></i></button>` : ''}
                <button onclick="deleteAddress('${currentCustomer.id}', '${addr.id}')" class="px-2 py-1 rounded glass text-red-400 text-xs hover:bg-red-500/20" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `).join('');
}

// Rendu préférences
function renderCustomerPreferences(preferences, favorites) {
    const allergies = preferences.allergies || [];
    const notes = preferences.notes || '';
    const deliveryInstructions = preferences.delivery_instructions || '';

    let html = '<div class="space-y-3">';

    if (allergies.length > 0) {
        html += `
            <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                <p class="text-red-400 font-semibold text-sm mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Allergies</p>
                <p class="text-red-300 text-sm">${allergies.map(escapeHtml).join(', ')}</p>
            </div>
        `;
    }

    if (favorites && favorites.length > 0) {
        html += `
            <div>
                <p class="text-gray-400 text-sm mb-2"><i class="fas fa-star mr-1"></i>Produits favoris:</p>
                <div class="space-y-1">
                    ${favorites.map((fav, idx) => `
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-white">${idx + 1}. ${escapeHtml(fav.name)}</span>
                            <span class="text-gray-400">(${fav.count}x commandé)</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    if (notes) {
        html += `
            <div>
                <p class="text-gray-400 text-sm mb-1"><i class="fas fa-comment mr-1"></i>Notes:</p>
                <p class="text-white text-sm">${escapeHtml(notes)}</p>
            </div>
        `;
    }

    if (deliveryInstructions) {
        html += `
            <div>
                <p class="text-gray-400 text-sm mb-1"><i class="fas fa-truck mr-1"></i>Instructions livraison:</p>
                <p class="text-white text-sm">${escapeHtml(deliveryInstructions)}</p>
            </div>
        `;
    }

    if (!allergies.length && !notes && !deliveryInstructions && (!favorites || !favorites.length)) {
        html += '<p class="text-gray-500 text-sm">Aucune préférence enregistrée</p>';
    }

    html += '</div>';
    return html;
}

// Rendu historique
function renderOrderHistory(orders) {
    if (!orders || orders.length === 0) {
        return '<p class="text-gray-500 text-sm">Aucune commande</p>';
    }

    return orders.map(order => `
        <div class="bg-gray-800/50 rounded-lg p-3">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <span class="text-white font-semibold">${escapeHtml(order.order_number)}</span>
                    <span class="text-gray-400 text-sm ml-2">${formatDate(order.created_at)}</span>
                </div>
                <span class="font-bold" style="color: var(--primary-color);">${Math.round(order.total)} ${window.CURRENCY || 'EUR'}</span>
            </div>
            ${order.items ? `
                <p class="text-gray-400 text-sm">${order.items.split(',').slice(0, 2).join(', ')}${order.items.split(',').length > 2 ? '...' : ''}</p>
            ` : ''}
            <button onclick="reorderSameOrder('${order.order_number}')" class="mt-2 text-sm px-3 py-1 rounded glass text-white hover:bg-white/10">
                <i class="fas fa-redo mr-1"></i>Recommander
            </button>
        </div>
    `).join('');
}

// ========== FONCTIONS ACTIONS ==========

// Sauvegarder notes admin
async function saveAdminNotes(customerId) {
    const notes = document.getElementById('admin-notes-textarea').value;

    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_admin_notes',
                customer_id: customerId,
                notes: notes
            })
        });

        const data = await response.json();
        if (data.success) {
            showToast('Notes enregistrées', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Ajouter tag
async function addCustomerTag(customerId, tag) {
    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_tag',
                customer_id: customerId,
                tag: tag
            })
        });

        const data = await response.json();
        if (data.success) {
            // Rafraîchir les détails
            showCustomerDetails(customerId);
            showToast('Tag ajouté', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Supprimer tag
async function removeCustomerTag(customerId, tag) {
    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove_tag',
                customer_id: customerId,
                tag: tag
            })
        });

        const data = await response.json();
        if (data.success) {
            showCustomerDetails(customerId);
            showToast('Tag supprimé', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Ajouter adresse
async function addAddress(customerId, addressData) {
    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_address',
                customer_id: customerId,
                ...addressData
            })
        });

        const data = await response.json();
        if (data.success) {
            showCustomerDetails(customerId);
            showToast('Adresse ajoutée', 'success');
        } else {
            alert(data.message || 'Erreur');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Supprimer adresse
async function deleteAddress(customerId, addressId) {
    if (!confirm('Supprimer cette adresse?')) return;

    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_address',
                customer_id: customerId,
                address_id: addressId
            })
        });

        const data = await response.json();
        if (data.success) {
            showCustomerDetails(customerId);
            showToast('Adresse supprimée', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Définir adresse par défaut
async function setDefaultAddress(customerId, addressId) {
    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_default_address',
                customer_id: customerId,
                address_id: addressId
            })
        });

        const data = await response.json();
        if (data.success) {
            showCustomerDetails(customerId);
            showToast('Adresse par défaut mise à jour', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Mettre à jour préférences
async function updatePreferences(customerId, preferences) {
    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_preferences',
                customer_id: customerId,
                ...preferences
            })
        });

        const data = await response.json();
        if (data.success) {
            showCustomerDetails(customerId);
            showToast('Préférences mises à jour', 'success');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Templates WhatsApp
function showWhatsAppTemplates(customerId) {
    const customer = customersData.find(c => c.id === customerId) || currentCustomer;
    if (!customer) return;

    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    const loyaltyMsg = customer.loyalty_points >= 100
        ? 'Vous pouvez profiter d\'une récompense!'
        : `Plus que ${100 - customer.loyalty_points} points pour une récompense!`;

    content.innerHTML = `
        <div class="space-y-4">
            <h3 class="text-2xl font-bold text-white mb-4">
                <i class="fab fa-whatsapp mr-2" style="color: #25D366;"></i>
                Envoyer WhatsApp à ${escapeHtml(customer.name)}
            </h3>

            ${Object.entries(whatsappTemplates).map(([key, template]) => `
                <div class="glass rounded-xl p-4">
                    <h4 class="text-white font-semibold mb-2">${template.title}</h4>
                    <textarea
                        id="whatsapp-template-${key}"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white text-sm resize-none"
                        rows="6"
                    >${template.message
                        .replace('{NOM}', customer.name)
                        .replace('{POINTS}', customer.loyalty_points)
                        .replace('{LOYALTY_MSG}', loyaltyMsg)
                        .replace('{RESTO}', window.APP_CONFIG?.restaurantName || 'Votre Restaurant')
                    }</textarea>
                    <button onclick="sendWhatsApp('${customer.phone}', document.getElementById('whatsapp-template-${key}').value)"
                        class="mt-2 px-4 py-2 rounded-lg bg-green-500 text-white btn">
                        <i class="fab fa-whatsapp mr-2"></i>Envoyer ce message
                    </button>
                </div>
            `).join('')}

            <button onclick="showCustomerDetails('${customer.id}')" class="w-full py-3 rounded-xl glass text-white font-semibold">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </button>
        </div>
    `;
}

// Envoyer WhatsApp
function sendWhatsApp(phone, message) {
    const cleanPhone = phone.replace(/[^0-9]/g, '');
    window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`, '_blank');
}

// Ajouter points bonus
async function addBonusPoints(customerId) {
    const points = prompt('Combien de points bonus voulez-vous ajouter ?');
    if (!points || isNaN(points)) return;

    try {
        const response = await fetch('api/customers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'add_points',
                customer_id: customerId,
                points: parseInt(points)
            })
        });

        const data = await response.json();
        if (data.success) {
            showToast(`${points} points ajoutés !`, 'success');
            // ⚡ FIX: Une seule requête au lieu de 2
            showCustomerDetails(customerId);
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Recommander même commande
function reorderSameOrder(orderNumber) {
    alert(`Fonctionnalité "Recommander" pour ${orderNumber} à implémenter (créer nouvelle commande avec mêmes produits)`);
}

// ========== MODALS ==========

function showAddTagModal(customerId) {
    const customer = currentCustomer;
    const existingTags = customer.tags || [];
    const availableToAdd = availableTags.filter(t => !existingTags.includes(t.value));

    if (availableToAdd.length === 0) {
        alert('Tous les tags sont déjà assignés');
        return;
    }

    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-white mb-4">Ajouter un tag</h3>
            <div class="grid grid-cols-2 gap-2">
                ${availableToAdd.map(tag => `
                    <button onclick="addCustomerTag('${customerId}', '${tag.value}')"
                        class="px-4 py-3 rounded-lg text-white font-semibold"
                        style="background-color: ${tag.color}40; border: 2px solid ${tag.color};">
                        ${escapeHtml(tag.label)}
                    </button>
                `).join('')}
            </div>
            <button onclick="showCustomerDetails('${customerId}')" class="w-full py-3 rounded-xl glass text-white">Annuler</button>
        </div>
    `;
}

function showAddAddressModal(customerId) {
    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-white mb-4">Ajouter une adresse</h3>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Type</label>
                <select id="address-type" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white">
                    <option value="home">Maison</option>
                    <option value="work">Bureau</option>
                </select>
            </div>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Adresse complète *</label>
                <textarea id="address-text" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white" rows="3" placeholder="12 Rue de la Paix, Alger"></textarea>
            </div>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Notes (code porte, instructions...)</label>
                <textarea id="address-notes" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white" rows="2" placeholder="Sonner 2 fois, code: 1234"></textarea>
            </div>

            <div class="flex gap-2">
                <button onclick="saveNewAddress('${customerId}')" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <button onclick="showCustomerDetails('${customerId}')" class="flex-1 py-3 rounded-xl glass text-white">Annuler</button>
            </div>
        </div>
    `;
}

function saveNewAddress(customerId) {
    const type = document.getElementById('address-type').value;
    const address = document.getElementById('address-text').value.trim();
    const notes = document.getElementById('address-notes').value.trim();

    if (!address) {
        alert('Adresse requise');
        return;
    }

    addAddress(customerId, {
        type: type,
        label: type === 'home' ? 'Maison' : 'Bureau',
        address: address,
        notes: notes
    });
}

function showEditPreferencesModal(customerId) {
    const customer = currentCustomer;
    const prefs = customer.preferences || {};

    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <div class="space-y-4">
            <h3 class="text-xl font-bold text-white mb-4">Modifier les préférences</h3>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Allergies (séparées par des virgules)</label>
                <input type="text" id="prefs-allergies" value="${(prefs.allergies || []).join(', ')}" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white" placeholder="gluten, lactose">
            </div>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Notes / Préférences</label>
                <textarea id="prefs-notes" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white" rows="3" placeholder="Toujours sans oignons...">${prefs.notes || ''}</textarea>
            </div>

            <div>
                <label class="text-gray-400 text-sm mb-1 block">Instructions de livraison</label>
                <textarea id="prefs-delivery" class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white" rows="2" placeholder="Appeler 5 minutes avant...">${prefs.delivery_instructions || ''}</textarea>
            </div>

            <div class="flex gap-2">
                <button onclick="savePreferences('${customerId}')" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold">
                    <i class="fas fa-save mr-2"></i>Enregistrer
                </button>
                <button onclick="showCustomerDetails('${customerId}')" class="flex-1 py-3 rounded-xl glass text-white">Annuler</button>
            </div>
        </div>
    `;
}

function savePreferences(customerId) {
    const allergies = document.getElementById('prefs-allergies').value.split(',').map(a => a.trim()).filter(a => a);
    const notes = document.getElementById('prefs-notes').value.trim();
    const delivery = document.getElementById('prefs-delivery').value.trim();

    updatePreferences(customerId, {
        allergies: allergies,
        notes: notes,
        delivery_instructions: delivery
    });
}

// ========== FILTRES ==========

function filterByTag(tag) {
    loadCustomers(tag);

    // Mettre à jour UI filtres
    document.querySelectorAll('.filter-tag-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (tag) {
        event.target.classList.add('active');
    }
}

// ========== UTILITAIRES ==========

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'Jamais';
    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Aujourd\'hui';
    if (diffDays === 1) return 'Hier';
    if (diffDays < 7) return `Il y a ${diffDays} jours`;
    if (diffDays < 30) return `Il y a ${Math.floor(diffDays / 7)} semaines`;

    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function showToast(message, type = 'success') {
    // Utiliser le système de toast existant ou créer un simple alert
    const color = type === 'success' ? 'green' : 'red';
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg bg-${color}-500 text-white font-semibold shadow-lg z-50 animate-fade-in`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

// ⚡ PAGINATION: Afficher les boutons de pagination
function renderPagination(pagination, filterTag = null) {
    const customersList = document.getElementById('customers-list');

    if (!pagination || pagination.total_pages <= 1) {
        // Pas besoin de pagination si 1 seule page
        return;
    }

    const paginationHtml = `
        <div class="col-span-full flex justify-center items-center gap-2 mt-6">
            ${pagination.page > 1 ? `
                <button onclick="loadCustomers(${filterTag ? `'${filterTag}'` : 'null'}, ${pagination.page - 1})"
                        class="btn bg-gray-200 text-gray-800 px-4 py-2">
                    <i class="fas fa-chevron-left"></i> Précédent
                </button>
            ` : ''}

            <span class="text-white font-semibold px-4">
                Page ${pagination.page} / ${pagination.total_pages}
                <span class="text-gray-400 text-sm">(${pagination.total} clients)</span>
            </span>

            ${pagination.page < pagination.total_pages ? `
                <button onclick="loadCustomers(${filterTag ? `'${filterTag}'` : 'null'}, ${pagination.page + 1})"
                        class="btn bg-gray-200 text-gray-800 px-4 py-2">
                    Suivant <i class="fas fa-chevron-right"></i>
                </button>
            ` : ''}
        </div>
    `;

    customersList.insertAdjacentHTML('beforeend', paginationHtml);
}

function updateCustomerStats(stats) {
    console.log('Stats clients:', stats);
    // Optionnel: Afficher stats dans le header
}

// ========== INITIALISATION ==========

document.addEventListener('DOMContentLoaded', () => {
    // Charger tags disponibles
    loadAvailableTags().then(() => {
        // Puis charger clients
        loadCustomers();
    });

    // Bouton promo groupée
    const promoBtn = document.getElementById('send-promo-btn');
    if (promoBtn) {
        promoBtn.addEventListener('click', () => {
            alert('Promo groupée à implémenter');
        });
    }
});
