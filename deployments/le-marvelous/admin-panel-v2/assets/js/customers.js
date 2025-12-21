// ========== GESTION CLIENTS ==========

let customersData = [];
let selectedCustomers = [];

// Charger les clients
async function loadCustomers() {
    try {
        const response = await fetch('api/customers.php?action=list');
        const data = await response.json();

        if (data.success) {
            customersData = data.customers;
            renderCustomers(data.customers);
            updateCustomerStats(data.stats);
        }
    } catch (error) {
        console.error('Erreur chargement clients:', error);
        document.getElementById('customers-list').innerHTML =
            '<p class="text-red-400 text-center py-8">Erreur de chargement</p>';
    }
}

// Afficher les clients
function renderCustomers(customers) {
    const customersList = document.getElementById('customers-list');

    if (customers.length === 0) {
        customersList.innerHTML = `
            <div class="col-span-full glass-strong rounded-2xl p-12 text-center">
                <i class="fas fa-users text-6xl text-gray-600 mb-4"></i>
                <p class="text-gray-400 text-lg">Aucun client pour le moment</p>
            </div>
        `;
        return;
    }

    customersList.innerHTML = customers.map(customer => `
        <div class="glass-strong rounded-2xl p-6 hover:bg-white/10 transition-all cursor-pointer" onclick="showCustomerDetails('${customer.id}')">
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="text-white font-semibold text-lg">${customer.name}</h3>
                        ${customer.orders_count >= 10 ? '<span class="px-2 py-1 rounded-full text-xs font-bold bg-yellow-500/20 text-yellow-400">VIP</span>' : ''}
                    </div>
                    <p class="text-gray-400 text-sm">
                        <i class="fas fa-phone mr-2"></i>${customer.phone}
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold" style="color: var(--primary-color);">
                        ${customer.loyalty_points}
                    </div>
                    <div class="text-xs text-gray-400">points</div>
                </div>
            </div>

            <div class="border-t border-white/10 pt-3 mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                    <span class="text-gray-400">Commandes:</span>
                    <span class="text-white font-semibold ml-2">${customer.orders_count}</span>
                </div>
                <div>
                    <span class="text-gray-400">Dépensé:</span>
                    <span class="text-white font-semibold ml-2">${customer.total_spent.toFixed(2)}€</span>
                </div>
            </div>

            <div class="mt-3 text-xs text-gray-500">
                <i class="fas fa-clock mr-1"></i>
                Dernière commande: ${formatDate(customer.last_order)}
            </div>
        </div>
    `).join('');
}

// Afficher détails client
function showCustomerDetails(customerId) {
    const customer = customersData.find(c => c.id === customerId);
    if (!customer) return;

    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <div class="space-y-6">
            <!-- Infos principales -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-1">${customer.name}</h3>
                    <p class="text-gray-400">${customer.phone}</p>
                </div>
                ${customer.orders_count >= 10 ? `
                    <div class="px-4 py-2 rounded-xl bg-yellow-500/20 text-yellow-400 font-semibold">
                        <i class="fas fa-crown mr-2"></i>Client VIP
                    </div>
                ` : ''}
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="glass rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">${customer.orders_count}</div>
                    <div class="text-sm text-gray-400 mt-1">Commandes</div>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">${customer.total_spent.toFixed(2)}€</div>
                    <div class="text-sm text-gray-400 mt-1">Dépensé</div>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold" style="color: var(--primary-color);">${customer.loyalty_points}</div>
                    <div class="text-sm text-gray-400 mt-1">Points</div>
                </div>
            </div>

            <!-- Carte fidélité virtuelle -->
            <div class="primary-gradient rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="text-sm opacity-80">Carte de fidélité</div>
                        <div class="text-2xl font-bold mt-1">${customer.loyalty_points} pts</div>
                    </div>
                    <i class="fas fa-gift text-4xl opacity-50"></i>
                </div>
                <div class="bg-white/20 rounded-full h-2 overflow-hidden">
                    <div class="bg-white h-full transition-all" style="width: ${Math.min((customer.loyalty_points / 100) * 100, 100)}%"></div>
                </div>
                <div class="text-xs mt-2 opacity-80">
                    ${customer.loyalty_points >= 100 ? 'Récompense disponible !' : `${100 - customer.loyalty_points} points pour une récompense`}
                </div>
            </div>

            <!-- Notes -->
            ${customer.notes ? `
                <div class="glass rounded-xl p-4">
                    <div class="text-sm text-gray-400 mb-2">Notes:</div>
                    <div class="text-white">${customer.notes}</div>
                </div>
            ` : ''}

            <!-- Actions -->
            <div class="flex gap-2">
                <button onclick="sendMessageToCustomer('${customer.id}')" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold btn">
                    <i class="fab fa-whatsapp mr-2"></i>Message WhatsApp
                </button>
                <button onclick="addBonusPoints('${customer.id}')" class="px-4 py-3 rounded-xl glass text-white btn">
                    <i class="fas fa-plus-circle"></i>
                </button>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

// Envoyer message à un client
function sendMessageToCustomer(customerId) {
    const customer = customersData.find(c => c.id === customerId);
    if (!customer) return;

    const message = `Bonjour ${customer.name} ! Vous avez ${customer.loyalty_points} points de fidélité. ${customer.loyalty_points >= 100 ? 'Vous pouvez profiter d\'une récompense !' : ''} - ${window.APP_CONFIG.restaurantName}`;
    const phone = customer.phone.replace(/[^0-9]/g, '');
    window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank');
}

// Ajouter des points bonus
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
            alert(`${points} points ajoutés !`);
            loadCustomers();
            document.getElementById('order-modal').classList.add('hidden');
        }
    } catch (error) {
        console.error('Erreur ajout points:', error);
    }
}

// Envoyer promotion groupée
async function sendGroupPromo() {
    const message = prompt('Message promotionnel à envoyer :');
    if (!message) return;

    const segment = prompt('À qui ? (all/vip/inactive):', 'all');

    let targetCustomers = customersData;
    if (segment === 'vip') {
        targetCustomers = customersData.filter(c => c.orders_count >= 10);
    } else if (segment === 'inactive') {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        targetCustomers = customersData.filter(c => new Date(c.last_order) < thirtyDaysAgo);
    }

    if (targetCustomers.length === 0) {
        alert('Aucun client dans cette catégorie');
        return;
    }

    if (!confirm(`Envoyer à ${targetCustomers.length} clients ?`)) return;

    // Ouvrir un onglet WhatsApp pour chaque client (limité à 5 max)
    const limit = Math.min(targetCustomers.length, 5);
    for (let i = 0; i < limit; i++) {
        const customer = targetCustomers[i];
        const phone = customer.phone.replace(/[^0-9]/g, '');
        const personalizedMessage = message.replace('{NAME}', customer.name).replace('{POINTS}', customer.loyalty_points);

        setTimeout(() => {
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(personalizedMessage)}`, '_blank');
        }, i * 2000); // 2 secondes entre chaque
    }

    if (targetCustomers.length > 5) {
        alert(`Seulement les 5 premiers clients ont été traités (limite navigateur). Total: ${targetCustomers.length}`);
    }
}

// Mettre à jour stats clients
function updateCustomerStats(stats) {
    // Afficher dans le header de la section si nécessaire
    console.log('Stats clients:', stats);
}

// Formater date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Aujourd\'hui';
    if (diffDays === 1) return 'Hier';
    if (diffDays < 7) return `Il y a ${diffDays} jours`;

    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Initialiser le bouton promo
document.addEventListener('DOMContentLoaded', () => {
    const promoBtn = document.getElementById('send-promo-btn');
    if (promoBtn) {
        promoBtn.addEventListener('click', sendGroupPromo);
    }
});
