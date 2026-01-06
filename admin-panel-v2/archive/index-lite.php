<?php
require_once 'config.php';
requireLogin();

$config = loadConfig();
$primaryColor = $config['branding']['primaryColor'] ?? '#f97316';
$restaurantName = $config['restaurant']['name'] ?? 'Restaurant';
$whatsappNumber = $config['contact']['whatsappOrdersNumber'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?php echo htmlspecialchars($restaurantName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
        }

        * {
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-strong {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .primary-gradient {
            background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, black));
        }

        .tab-btn {
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.6);
        }

        .tab-btn.active {
            color: white;
            background: var(--primary-color);
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .status-badge {
            display: inline-flex;
            align-items-center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .status-received {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        .status-preparing {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .status-ready {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
    </style>
</head>
<body class="pb-20">
    <!-- Header -->
    <header class="glass-strong sticky top-0 z-40 px-4 py-3 mb-6">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl primary-gradient flex items-center justify-center">
                    <i class="fas fa-utensils text-white"></i>
                </div>
                <div>
                    <h1 class="text-white font-bold text-lg"><?php echo htmlspecialchars($restaurantName); ?></h1>
                    <p class="text-gray-400 text-xs">Administration</p>
                </div>
            </div>
            <a href="logout.php" class="w-10 h-10 rounded-xl glass flex items-center justify-center text-white hover:bg-white/10 transition-colors">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4">
        <!-- Section: Commandes -->
        <div id="section-orders" class="section active">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">Commandes</h2>
                <button onclick="window.location.reload()" class="px-4 py-2 rounded-xl glass text-white text-sm">
                    <i class="fas fa-sync-alt"></i> Actualiser
                </button>
            </div>

            <div id="orders-list" class="space-y-4">
                <p class="text-gray-400 text-center py-8">Chargement...</p>
            </div>
        </div>

        <!-- Autres sections simplifiées -->
        <div id="section-products" class="section">
            <h2 class="text-2xl font-bold text-white mb-6">Produits</h2>
            <p class="text-gray-400">Section en développement</p>
        </div>
    </main>

    <!-- Navigation Bottom -->
    <nav class="glass-strong fixed bottom-0 left-0 right-0 px-4 py-3 z-50">
        <div class="max-w-7xl mx-auto flex items-center justify-around">
            <button class="tab-btn active" data-section="orders">
                <i class="fas fa-receipt text-xl"></i>
            </button>
            <button class="tab-btn" data-section="products">
                <i class="fas fa-burger text-xl"></i>
            </button>
        </div>
    </nav>

    <script>
    // Configuration
    window.APP_CONFIG = {
        primaryColor: '<?php echo $primaryColor; ?>',
        restaurantName: '<?php echo addslashes($restaurantName); ?>',
        whatsappNumber: '<?php echo $whatsappNumber; ?>'
    };

    // Navigation simple
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.getElementById('section-' + btn.dataset.section).classList.add('active');
        };
    });

    // Charger commandes UNE SEULE FOIS
    fetch('api/orders.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const orders = data.orders.filter(o => o.status !== 'delivered');
            const html = orders.map(order => `
                <div class="glass-strong rounded-2xl p-6">
                    <div class="flex justify-between mb-4">
                        <div>
                            <div class="text-white font-bold text-lg">#${order.id}</div>
                            <div class="text-gray-400 text-sm">${order.customer_phone}</div>
                        </div>
                        <div class="text-2xl font-bold text-white">${order.total}€</div>
                    </div>
                    <div class="text-gray-300 text-sm mb-4">
                        ${order.items.map(i => `${i.quantity}x ${i.name}`).join(', ')}
                    </div>
                    <span class="status-badge status-${order.status === 'received' ? 'received' : order.status === 'preparing' ? 'preparing' : 'ready'}">
                        ${order.status === 'received' ? 'Reçue' : order.status === 'preparing' ? 'En préparation' : 'Prête'}
                    </span>
                </div>
            `).join('');
            document.getElementById('orders-list').innerHTML = html || '<p class="text-gray-400 text-center py-8">Aucune commande</p>';
        });
    </script>
</body>
</html>
