<?php
session_start();
require_once 'config.php';

// Vérifier authentification
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$restaurantName = RESTAURANT_NAME;
$primaryColor = PRIMARY_COLOR;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($restaurantName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
        }

        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .primary-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, #e67e22 100%);
        }

        .btn {
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .tab-btn {
            position: relative;
            overflow: hidden;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-color);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-received { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .status-preparing { background: rgba(251, 146, 60, 0.2); color: #fb923c; }
        .status-ready { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
        .status-delivered { background: rgba(156, 163, 175, 0.2); color: #9ca3af; }

        .section { display: none; }
        .section.active { display: block; }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="glass-strong rounded-2xl p-6 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl primary-gradient flex items-center justify-center">
                    <i class="fas fa-utensils text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-white text-2xl font-bold"><?php echo htmlspecialchars($restaurantName); ?></h1>
                    <p class="text-gray-400 text-sm">Administration</p>
                </div>
            </div>
            <a href="logout.php" class="px-4 py-2 rounded-xl glass-strong text-white btn">
                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
            </a>
        </div>

        <!-- Navigation Tabs -->
        <div class="glass-strong rounded-2xl p-2 mb-6 flex gap-2 overflow-x-auto">
            <button class="tab-btn active flex-1 px-6 py-3 rounded-xl text-white font-semibold" data-section="orders">
                <i class="fas fa-receipt mr-2"></i>Commandes
            </button>
            <button class="tab-btn flex-1 px-6 py-3 rounded-xl text-gray-400 font-semibold" data-section="dashboard">
                <i class="fas fa-chart-line mr-2"></i>Stats
            </button>
            <button class="tab-btn flex-1 px-6 py-3 rounded-xl text-gray-400 font-semibold" data-section="products">
                <i class="fas fa-burger mr-2"></i>Produits
            </button>
            <button class="tab-btn flex-1 px-6 py-3 rounded-xl text-gray-400 font-semibold" data-section="customers">
                <i class="fas fa-users mr-2"></i>Clients
            </button>
        </div>

        <!-- Section Commandes -->
        <div id="section-orders" class="section active">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-white text-2xl font-bold">Gestion des commandes</h2>
                <button onclick="loadOrders()" class="px-4 py-2 rounded-xl primary-gradient text-white font-semibold btn">
                    <i class="fas fa-sync-alt mr-2"></i>Actualiser
                </button>
            </div>

            <!-- Stats rapides -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="glass-strong rounded-2xl p-4 text-center">
                    <div class="text-gray-400 text-sm mb-1">Reçues</div>
                    <div id="stat-received" class="text-3xl font-bold text-blue-400">-</div>
                </div>
                <div class="glass-strong rounded-2xl p-4 text-center">
                    <div class="text-gray-400 text-sm mb-1">En préparation</div>
                    <div id="stat-preparing" class="text-3xl font-bold text-orange-400">-</div>
                </div>
                <div class="glass-strong rounded-2xl p-4 text-center">
                    <div class="text-gray-400 text-sm mb-1">Prêtes</div>
                    <div id="stat-ready" class="text-3xl font-bold text-green-400">-</div>
                </div>
                <div class="glass-strong rounded-2xl p-4 text-center">
                    <div class="text-gray-400 text-sm mb-1">Aujourd'hui</div>
                    <div id="stat-today" class="text-3xl font-bold text-white">-</div>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div id="orders-list" class="space-y-4">
                <p class="text-gray-400 text-center py-8">Chargement...</p>
            </div>
        </div>

        <!-- Section Stats -->
        <div id="section-dashboard" class="section">
            <h2 class="text-white text-2xl font-bold mb-6">Statistiques</h2>
            <div class="glass-strong rounded-2xl p-6">
                <p class="text-gray-400 text-center">Les graphiques sont désactivés pour améliorer la performance.</p>
                <p class="text-gray-500 text-center text-sm mt-2">Consultez la section Commandes pour les stats en temps réel.</p>
            </div>
        </div>

        <!-- Section Produits -->
        <div id="section-products" class="section">
            <h2 class="text-white text-2xl font-bold mb-6">Gestion des produits</h2>
            <div class="glass-strong rounded-2xl p-6">
                <p class="text-gray-400 text-center">Section en cours de développement</p>
            </div>
        </div>

        <!-- Section Clients -->
        <div id="section-customers" class="section">
            <h2 class="text-white text-2xl font-bold mb-6">Gestion des clients</h2>
            <div class="glass-strong rounded-2xl p-6">
                <p class="text-gray-400 text-center">Section en cours de développement</p>
            </div>
        </div>
    </div>

    <script>
        // État global SIMPLIFIÉ
        const AppState = {
            orders: [],
            activeSection: 'orders',
            soundEnabled: true
        };

        // Son de notification
        const notificationSound = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBTGH0fPTgjMGHm7A7+OZRQ8PVqzn77BdFwpDm97yuGgfBDWO1PLNeCwFJHPD8NyRQQsUXrTp66VPFQ==');

        // Navigation entre sections
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const sectionName = btn.dataset.section;

                // Mettre à jour les tabs
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'text-white');
                    b.classList.add('text-gray-400');
                });
                btn.classList.add('active', 'text-white');
                btn.classList.remove('text-gray-400');

                // Mettre à jour les sections
                document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
                document.getElementById('section-' + sectionName).classList.add('active');

                AppState.activeSection = sectionName;
            });
        });

        // Charger les commandes - VERSION SIMPLIFIÉE
        async function loadOrders() {
            try {
                const response = await fetch('api/orders.php?action=list');
                const data = await response.json();

                if (data.success) {
                    AppState.orders = data.orders;
                    renderOrders(data.orders);
                    updateStats(data.orders);
                }
            } catch (error) {
                console.error('Erreur:', error);
                document.getElementById('orders-list').innerHTML =
                    '<p class="text-red-400 text-center py-8">Erreur de chargement</p>';
            }
        }

        // Afficher les commandes
        function renderOrders(orders) {
            const ordersList = document.getElementById('orders-list');

            if (orders.length === 0) {
                ordersList.innerHTML = `
                    <div class="glass-strong rounded-2xl p-12 text-center">
                        <i class="fas fa-inbox text-6xl text-gray-600 mb-4"></i>
                        <p class="text-gray-400 text-lg">Aucune commande</p>
                    </div>`;
                return;
            }

            // Trier par date décroissante
            orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

            ordersList.innerHTML = orders.map(order => `
                <div class="glass-strong rounded-2xl p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-white font-bold text-lg">${order.id}</h3>
                            <p class="text-gray-400 text-sm">${order.customer_phone}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-white font-bold text-xl">${order.total.toFixed(2)}€</div>
                            <div class="text-gray-400 text-sm">${new Date(order.created_at).toLocaleString('fr-FR')}</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        ${order.items.map(item => `
                            <div class="text-gray-300 text-sm">
                                ${item.quantity}x ${item.name}
                                ${item.supplements && item.supplements.length > 0 ?
                                    `<span class="text-gray-500">(+ ${item.supplements.map(s => s.name).join(', ')})</span>` : ''}
                            </div>
                        `).join('')}
                    </div>

                    ${order.notes ? `<div class="text-gray-400 text-sm mb-4"><i class="fas fa-comment mr-2"></i>${order.notes}</div>` : ''}

                    <div class="flex gap-2 flex-wrap">
                        <button onclick="updateOrderStatus('${order.id}', 'received')"
                                class="status-badge status-received btn ${order.status === 'received' ? 'ring-2 ring-blue-400' : ''}">
                            Reçue
                        </button>
                        <button onclick="updateOrderStatus('${order.id}', 'preparing')"
                                class="status-badge status-preparing btn ${order.status === 'preparing' ? 'ring-2 ring-orange-400' : ''}">
                            En préparation
                        </button>
                        <button onclick="updateOrderStatus('${order.id}', 'ready')"
                                class="status-badge status-ready btn ${order.status === 'ready' ? 'ring-2 ring-green-400' : ''}">
                            Prête
                        </button>
                        <button onclick="updateOrderStatus('${order.id}', 'delivered')"
                                class="status-badge status-delivered btn ${order.status === 'delivered' ? 'ring-2 ring-gray-400' : ''}">
                            Livrée
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Mettre à jour le statut d'une commande
        async function updateOrderStatus(orderId, newStatus) {
            try {
                const response = await fetch('api/orders.php?action=update_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: newStatus })
                });

                const data = await response.json();

                if (data.success) {
                    loadOrders(); // Recharger la liste

                    // Jouer le son si passage à "ready"
                    if (newStatus === 'ready' && AppState.soundEnabled) {
                        notificationSound.play().catch(e => console.log('Son bloqué'));
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }

        // Mettre à jour les stats
        function updateStats(orders) {
            const today = new Date().toISOString().split('T')[0];
            const todayOrders = orders.filter(o => o.created_at.startsWith(today));

            document.getElementById('stat-received').textContent = orders.filter(o => o.status === 'received').length;
            document.getElementById('stat-preparing').textContent = orders.filter(o => o.status === 'preparing').length;
            document.getElementById('stat-ready').textContent = orders.filter(o => o.status === 'ready').length;
            document.getElementById('stat-today').textContent = todayOrders.length;
        }

        // Charger au démarrage
        loadOrders();
    </script>
</body>
</html>
