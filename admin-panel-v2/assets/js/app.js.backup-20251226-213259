// État global de l'application
const AppState = {
    orders: [],
    lastOrderCount: 0,
    soundEnabled: true,
    activeSection: 'orders',
    autoRefreshInterval: null,
    charts: {
        sales: null,
        topProducts: null
    }
};

// Son de notification
const notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZRQ8PVqzn77BdFwpDm97yuGgfBDWO1PLNeCwFJHPD8NyRQQsUXrTp66VPFQ==');

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    initNavigation();
    initNotifications();
    loadOrders();
    initCharts();
    startAutoRefresh();
});

// Navigation entre sections
function initNavigation() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const sections = document.querySelectorAll('.section');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const sectionName = btn.dataset.section;

            // Mettre à jour les boutons
            tabButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Mettre à jour les sections
            sections.forEach(s => s.classList.remove('active'));
            document.getElementById(`section-${sectionName}`).classList.add('active');

            AppState.activeSection = sectionName;

            // Charger les données si nécessaire
            if (sectionName === 'orders') {
                loadOrders();
            } else if (sectionName === 'products') {
                loadProducts();
            } else if (sectionName === 'customers') {
                loadCustomers();
            } else if (sectionName === 'dashboard') {
                updateCharts();
            }
        });
    });
}

// Système de notifications sonores
function initNotifications() {
    const notifToggle = document.getElementById('notif-toggle');
    const soundStatus = document.getElementById('sound-status');

    notifToggle.addEventListener('click', () => {
        AppState.soundEnabled = !AppState.soundEnabled;
        soundStatus.innerHTML = AppState.soundEnabled
            ? '<i class="fas fa-volume-up"></i> Activé'
            : '<i class="fas fa-volume-mute"></i> Désactivé';
    });

    // Bouton refresh manuel
    document.getElementById('refresh-orders').addEventListener('click', () => {
        loadOrders();
    });
}

// Charger les commandes
async function loadOrders() {
    try {
        const response = await fetch('api/orders.php?action=list');
        const data = await response.json();

        if (data.success) {
            const newOrderCount = data.orders.length;

            // Détecter nouvelle commande
            if (newOrderCount > AppState.lastOrderCount && AppState.lastOrderCount > 0) {
                playNotification();
                showNotificationBadge();
            }

            AppState.lastOrderCount = newOrderCount;
            AppState.orders = data.orders;
            renderOrders(data.orders);
            updateStats(data.orders);
        }
    } catch (error) {
        console.error('Erreur chargement commandes:', error);
    }
}

// Jouer le son de notification
function playNotification() {
    if (AppState.soundEnabled) {
        notificationSound.play().catch(e => console.log('Son non autorisé:', e));

        // Notification navigateur
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Nouvelle commande !', {
                body: 'Une nouvelle commande vient d\'arriver',
                icon: '/images/logo.png',
                badge: '/images/logo.png'
            });
        }
    }
}

// Afficher badge nouvelle commande
function showNotificationBadge() {
    const ordersTab = document.querySelector('[data-section="orders"]');
    if (!ordersTab.classList.contains('new-order-badge')) {
        ordersTab.classList.add('new-order-badge');
        setTimeout(() => ordersTab.classList.remove('new-order-badge'), 5000);
    }
}

// Afficher les commandes
function renderOrders(orders) {
    const ordersList = document.getElementById('orders-list');
    const noOrders = document.getElementById('no-orders');

    if (orders.length === 0) {
        ordersList.innerHTML = '';
        noOrders.style.display = 'block';
        return;
    }

    noOrders.style.display = 'none';

    // Filtrer les commandes actives (pas encore traitées complètement)
    const activeOrders = orders.filter(o => o.status !== 'delivered');

    ordersList.innerHTML = activeOrders.map(order => `
        <div class="order-card glass-strong rounded-2xl p-6" data-order-id="${order.id}">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-white font-bold text-lg">#${order.id}</span>
                        <span class="status-badge status-${order.status === 'received' ? 'received' : order.status === 'preparing' ? 'preparing' : 'ready'}">
                            <i class="fas fa-${order.status === 'received' ? 'clock' : order.status === 'preparing' ? 'fire' : 'check-circle'}"></i>
                            ${getStatusLabel(order.status)}
                        </span>
                    </div>
                    <div class="text-gray-400 text-sm">
                        <i class="fas fa-user mr-2"></i>${order.customer_name || 'Client'}
                    </div>
                    <div class="text-gray-400 text-sm">
                        <i class="fas fa-phone mr-2"></i><a href="tel:${order.customer_phone}" class="text-blue-400 hover:text-blue-300 hover:underline">${order.customer_phone}</a>
                    </div>
                    <div class="text-gray-400 text-sm">
                        <i class="fas fa-clock mr-2"></i>${formatTime(order.created_at)}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-white">${order.total} DA</div>
                    ${order.estimated_time ? `
                        <div class="text-sm text-gray-400 mt-1">
                            <i class="fas fa-hourglass-half mr-1"></i>${order.estimated_time} min
                        </div>
                    ` : ''}
                </div>
            </div>

            <div class="border-t border-white/10 pt-4 mb-4">
                <div class="space-y-2">
                    ${order.items.map(item => `
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-300">${item.quantity}x ${item.name}</span>
                            <span class="text-gray-400">${item.price} DA</span>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="flex gap-2">
                ${order.status === 'received' ? `
                    <button onclick="updateOrderStatus('${order.id}', 'preparing')" class="flex-1 py-3 rounded-xl bg-yellow-500/20 text-yellow-400 font-semibold btn border border-yellow-500/30">
                        <i class="fas fa-fire mr-2"></i>Commencer préparation
                    </button>
                ` : ''}
                ${order.status === 'preparing' ? `
                    <button onclick="updateOrderStatus('${order.id}', 'ready')" class="flex-1 py-3 rounded-xl bg-green-500/20 text-green-400 font-semibold btn border border-green-500/30">
                        <i class="fas fa-check-circle mr-2"></i>Marquer prête
                    </button>
                ` : ''}
                ${order.status === 'ready' ? `
                    <button onclick="notifyCustomer('${order.id}')" class="flex-1 py-3 rounded-xl bg-green-500 text-white font-semibold btn">
                        <i class="fab fa-whatsapp mr-2"></i>Notifier client
                    </button>
                    <button onclick="updateOrderStatus('${order.id}', 'delivered')" class="px-4 py-3 rounded-xl glass text-white btn">
                        <i class="fas fa-check"></i>
                    </button>
                ` : ''}
                <button onclick="showOrderDetails('${order.id}')" class="px-4 py-3 rounded-xl glass text-white btn">
                    <i class="fas fa-info-circle"></i>
                </button>
            </div>

            ${order.status === 'preparing' && !order.estimated_time ? `
                <div class="mt-3 flex gap-2">
                    <input type="number" id="time-${order.id}" placeholder="Temps (min)" class="flex-1 px-4 py-2 rounded-xl glass text-white" min="5" max="120">
                    <button onclick="setEstimatedTime('${order.id}')" class="px-4 py-2 rounded-xl glass text-white btn">
                        <i class="fas fa-clock"></i> Définir
                    </button>
                </div>
            ` : ''}
        </div>
    `).join('');
}

// Obtenir le label du statut
function getStatusLabel(status) {
    const labels = {
        'received': 'Reçue',
        'preparing': 'En préparation',
        'ready': 'Prête',
        'delivered': 'Livrée'
    };
    return labels[status] || status;
}

// Formater l'heure
function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000 / 60); // différence en minutes

    if (diff < 1) return 'À l\'instant';
    if (diff < 60) return `Il y a ${diff} min`;

    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

// Mettre à jour le statut d'une commande
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_status',
                order_id: orderId,
                status: newStatus
            })
        });

        const data = await response.json();
        if (data.success) {
            loadOrders();

            // Si commande prête, proposer notification automatique
            if (newStatus === 'ready') {
                if (confirm('Voulez-vous notifier le client maintenant ?')) {
                    notifyCustomer(orderId);
                }
            }
        }
    } catch (error) {
        console.error('Erreur mise à jour statut:', error);
        alert('Erreur lors de la mise à jour');
    }
}

// Définir temps estimé
async function setEstimatedTime(orderId) {
    const timeInput = document.getElementById(`time-${orderId}`);
    const time = parseInt(timeInput.value);

    if (!time || time < 1) {
        alert('Veuillez entrer un temps valide');
        return;
    }

    try {
        const response = await fetch('api/orders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_estimated_time',
                order_id: orderId,
                estimated_time: time
            })
        });

        const data = await response.json();
        if (data.success) {
            loadOrders();
        }
    } catch (error) {
        console.error('Erreur définition temps:', error);
    }
}

// Notifier le client via WhatsApp
async function notifyCustomer(orderId) {
    const order = AppState.orders.find(o => o.id === orderId);
    if (!order) return;

    const message = `Bonjour ! Votre commande #${orderId} est prête. Vous pouvez venir la récupérer. Merci ! - ${window.APP_CONFIG.restaurantName}`;
    const phone = order.customer_phone.replace(/[^0-9]/g, '');
    const url = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;

    window.open(url, '_blank');

    // Marquer comme notifié
    await updateOrderStatus(orderId, 'delivered');
}

// Afficher détails commande
function showOrderDetails(orderId) {
    const order = AppState.orders.find(o => o.id === orderId);
    if (!order) return;

    const modal = document.getElementById('order-modal');
    const content = document.getElementById('modal-content');

    content.innerHTML = `
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <span class="text-gray-400">Numéro de commande</span>
                <span class="text-white font-bold">#${order.id}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">Client</span>
                <span class="text-white">${order.customer_name || 'N/A'}</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">Téléphone</span>
                <span class="text-white">
                    <a href="tel:${order.customer_phone}" class="text-blue-400 hover:text-blue-300 hover:underline inline-flex items-center gap-2">
                        <i class="fas fa-phone"></i>${order.customer_phone}
                    </a>
                </span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-gray-400">Date</span>
                <span class="text-white">${new Date(order.created_at).toLocaleString('fr-FR')}</span>
            </div>
            <div class="border-t border-white/10 pt-4">
                <h4 class="text-white font-semibold mb-3">Articles</h4>
                <div class="space-y-3">
                    ${order.items.map(item => `
                        <div class="border-b border-gray-700/50 pb-3 last:border-0">
                            <div class="flex justify-between mb-2">
                                <span class="text-white font-semibold">${item.quantity}x ${item.name}</span>
                                <span class="text-white font-bold">${item.price} DA</span>
                            </div>
                            ${item.supplements && item.supplements.length > 0 ? `
                                <div class="text-yellow-400 text-sm ml-4">
                                    <i class="fas fa-plus-circle text-xs"></i> ${item.supplements.map(s => s.name).join(', ')}
                                </div>
                            ` : ''}
                            ${item.removed_ingredients && item.removed_ingredients.length > 0 ? `
                                <div class="text-red-400 text-sm ml-4">
                                    <i class="fas fa-minus-circle text-xs"></i> Sans: ${item.removed_ingredients.join(', ')}
                                </div>
                            ` : ''}
                            ${item.selected_options ? `
                                <div class="text-gray-400 text-sm ml-4">
                                    <i class="fas fa-info-circle text-xs"></i> ${Object.entries(item.selected_options).map(([k, v]) => v).join(', ')}
                                </div>
                            ` : ''}
                            ${item.selected_drink ? `
                                <div class="text-blue-400 text-sm ml-4">
                                    <i class="fas fa-glass text-xs"></i> ${item.selected_drink}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
            <div class="border-t border-white/10 pt-4 flex items-center justify-between">
                <span class="text-gray-400 font-semibold">Total</span>
                <span class="text-2xl font-bold text-white">${order.total} DA</span>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
}

// Fermer modal
document.addEventListener('click', (e) => {
    if (e.target.id === 'close-modal' || e.target.id === 'order-modal') {
        document.getElementById('order-modal').classList.add('hidden');
    }
});

// Mettre à jour les statistiques
function updateStats(orders) {
    const today = new Date().toDateString();
    const todayOrders = orders.filter(o => new Date(o.created_at).toDateString() === today);

    document.getElementById('stat-pending').textContent = orders.filter(o => o.status === 'received').length;
    document.getElementById('stat-preparing').textContent = orders.filter(o => o.status === 'preparing').length;
    document.getElementById('stat-ready').textContent = orders.filter(o => o.status === 'ready').length;
    document.getElementById('stat-today').textContent = todayOrders.length;
}

// Auto-refresh toutes les 60 secondes (réduit pour éviter surcharge)
function startAutoRefresh() {
    // Nettoyer l'ancien interval s'il existe
    if (AppState.autoRefreshInterval) {
        clearInterval(AppState.autoRefreshInterval);
    }

    AppState.autoRefreshInterval = setInterval(() => {
        if (AppState.activeSection === 'orders') {
            loadOrders();
        }
    }, 60000); // 60 secondes au lieu de 10
}

// Initialiser les graphiques
function initCharts() {
    // Détruire les anciens graphiques pour éviter memory leak
    if (AppState.charts.sales) {
        AppState.charts.sales.destroy();
    }
    if (AppState.charts.topProducts) {
        AppState.charts.topProducts.destroy();
    }

    // Graphique ventes
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        AppState.charts.sales = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                datasets: [{
                    label: 'Ventes (DA)',
                    data: [0, 0, 0, 0, 0, 0, 0],
                    borderColor: window.APP_CONFIG.primaryColor,
                    backgroundColor: `${window.APP_CONFIG.primaryColor}20`,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Graphique top produits
    const topProductsCtx = document.getElementById('topProductsChart');
    if (topProductsCtx) {
        AppState.charts.topProducts = new Chart(topProductsCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Ventes',
                    data: [],
                    backgroundColor: window.APP_CONFIG.primaryColor
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
}

async function updateCharts() {
    try {
        // Charger les stats de ventes
        const salesResponse = await fetch('api/stats.php?action=sales&period=week');
        const salesData = await salesResponse.json();

        if (salesData.success && salesData.data) {
            const salesChart = Chart.getChart('salesChart');
            if (salesChart) {
                salesChart.data.labels = salesData.data.map(d => d.label);
                salesChart.data.datasets[0].data = salesData.data.map(d => d.revenue);
                salesChart.update();
            }
        }

        // Charger les top produits
        const productsResponse = await fetch('api/stats.php?action=products');
        const productsData = await productsResponse.json();

        if (productsData.success && productsData.top_products) {
            const topProductsChart = Chart.getChart('topProductsChart');
            if (topProductsChart) {
                topProductsChart.data.labels = productsData.top_products.map(p => p.name);
                topProductsChart.data.datasets[0].data = productsData.top_products.map(p => p.count);
                topProductsChart.update();
            }
        }

        // Charger les stats du dashboard
        const dashboardResponse = await fetch('api/stats.php?action=dashboard');
        const dashboardData = await dashboardResponse.json();

        if (dashboardData.success) {
            updateDashboardStats(dashboardData.stats);
        }
    } catch (error) {
        console.error('Erreur chargement statistiques:', error);
    }
}

function updateDashboardStats(stats) {
    // Créer ou mettre à jour l'affichage des stats dashboard
    const dashboardSection = document.getElementById('section-dashboard');
    if (!dashboardSection) return;

    // Chercher ou créer le conteneur de stats
    let statsContainer = dashboardSection.querySelector('.dashboard-stats');
    if (!statsContainer) {
        // Insérer avant les graphiques
        statsContainer = document.createElement('div');
        statsContainer.className = 'dashboard-stats grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6';
        dashboardSection.querySelector('h2').after(statsContainer);
    }

    statsContainer.innerHTML = `
        <div class="glass-strong rounded-2xl p-6">
            <div class="text-gray-400 text-sm mb-1">Aujourd'hui</div>
            <div class="text-3xl font-bold text-white">${stats.today.orders}</div>
            <div class="text-sm text-gray-400 mt-1">${stats.today.revenue|0} DA</div>
        </div>
        <div class="glass-strong rounded-2xl p-6">
            <div class="text-gray-400 text-sm mb-1">Cette semaine</div>
            <div class="text-3xl font-bold text-white">${stats.week.orders}</div>
            <div class="text-sm text-gray-400 mt-1">${stats.week.revenue|0} DA</div>
        </div>
        <div class="glass-strong rounded-2xl p-6">
            <div class="text-gray-400 text-sm mb-1">Ce mois</div>
            <div class="text-3xl font-bold text-white">${stats.month.orders}</div>
            <div class="text-sm text-gray-400 mt-1">${stats.month.revenue|0} DA</div>
        </div>
        <div class="glass-strong rounded-2xl p-6">
            <div class="text-gray-400 text-sm mb-1">Clients total</div>
            <div class="text-3xl font-bold text-white">${stats.customers.total}</div>
            <div class="text-sm text-gray-400 mt-1">${stats.customers.vip} VIP</div>
        </div>
    `;
}

// NOTE: loadProducts() et loadCustomers() sont maintenant dans leurs fichiers respectifs
// (products.js et customers.js)

// Demander permission notifications
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}
