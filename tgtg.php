<?php
/**
 * Page publique Too Good To Go
 * Affiche les offres invendus à prix réduit
 */

// Charger les offres Too Good To Go depuis l'admin panel
$tgtgFile = __DIR__ . '/admin-panel-v2/data/tgtg.json';
$offers = [];

if (file_exists($tgtgFile)) {
    $content = file_get_contents($tgtgFile);
    $allOffers = json_decode($content, true);

    // Filtrer les offres encore valides et disponibles
    $now = time();
    foreach ($allOffers as $offer) {
        $expiresAt = strtotime($offer['expires_at']);
        if ($expiresAt > $now && $offer['quantity_available'] > 0 && $offer['active']) {
            $offers[] = $offer;
        }
    }
}

// Charger config restaurant
$configFile = __DIR__ . '/config/fabrik-burger.config.js';
$restaurantName = 'Restaurant';
$restaurantPhone = '';
$restaurantAddress = '';
$primaryColor = '#10b981'; // Vert par défaut pour Too Good To Go

if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    if (preg_match('/const SNACK_CONFIG\s*=\s*({.*?});/s', $configContent, $matches)) {
        $config = json_decode($matches[1], true);
        if ($config) {
            $restaurantName = $config['restaurant']['name'] ?? 'Restaurant';
            $restaurantPhone = $config['contact']['phone'] ?? '';
            $restaurantAddress = $config['restaurant']['address'] ?? '';
            $primaryColor = $config['branding']['primaryColor'] ?? '#10b981';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Too Good To Go - <?php echo htmlspecialchars($restaurantName); ?></title>
    <meta name="description" content="Sauvez des invendus et économisez ! Offres à prix réduits disponibles chez <?php echo htmlspecialchars($restaurantName); ?>">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
        }

        body {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .discount-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .offer-card {
            transition: all 0.3s ease;
        }

        .offer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <!-- Header -->
    <header class="max-w-4xl mx-auto mb-8">
        <div class="glass rounded-3xl p-6 md:p-8">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-green-500 flex items-center justify-center">
                    <i class="fas fa-leaf text-3xl md:text-4xl text-white"></i>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl md:text-4xl font-bold text-gray-900">Too Good To Go</h1>
                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($restaurantName); ?></p>
                </div>
            </div>

            <div class="bg-green-50 rounded-xl p-4 border-l-4 border-green-500">
                <p class="text-green-800 text-sm md:text-base">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Sauvez des invendus !</strong> Récupérez des paniers surprise à prix réduits et aidez à réduire le gaspillage alimentaire.
                </p>
            </div>
        </div>
    </header>

    <!-- Offres -->
    <main class="max-w-4xl mx-auto">
        <?php if (empty($offers)): ?>
            <!-- Aucune offre disponible -->
            <div class="glass rounded-3xl p-12 text-center">
                <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Aucune offre pour le moment</h2>
                <p class="text-gray-600 mb-6">Revenez plus tard pour découvrir nos paniers surprise !</p>
                <a href="/" class="inline-block px-6 py-3 bg-green-500 text-white rounded-xl font-semibold hover:bg-green-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au menu
                </a>
            </div>
        <?php else: ?>
            <!-- Liste des offres -->
            <div class="grid gap-6 md:grid-cols-2">
                <?php foreach ($offers as $offer):
                    $discount = round((($offer['original_price'] - $offer['discount_price']) / $offer['original_price']) * 100);
                    $savings = $offer['original_price'] - $offer['discount_price'];
                ?>
                    <div class="offer-card glass rounded-3xl overflow-hidden">
                        <!-- Badge réduction -->
                        <div class="discount-badge px-6 py-3 text-center">
                            <div class="text-3xl font-bold text-white">-<?php echo $discount; ?>%</div>
                            <div class="text-sm text-white/90">Économisez <?php echo number_format($savings, 2); ?>€</div>
                        </div>

                        <!-- Contenu -->
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                                    <i class="fas fa-shopping-bag text-2xl text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($offer['product_name']); ?></h3>
                                </div>
                            </div>

                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($offer['description']); ?></p>

                            <!-- Prix -->
                            <div class="flex items-baseline gap-3 mb-4">
                                <div class="text-3xl font-bold text-green-600"><?php echo number_format($offer['discount_price'], 2); ?>€</div>
                                <div class="text-lg text-gray-400 line-through"><?php echo number_format($offer['original_price'], 2); ?>€</div>
                            </div>

                            <!-- Infos -->
                            <div class="space-y-2 mb-6">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-box text-green-600"></i>
                                    <span><strong><?php echo $offer['quantity_available']; ?></strong> panier(s) disponible(s)</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-clock text-green-600"></i>
                                    <span>À récupérer : <strong><?php echo htmlspecialchars($offer['pickup_time']); ?></strong></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="fas fa-calendar text-green-600"></i>
                                    <span>Valable jusqu'à : <strong><?php echo date('d/m/Y à H:i', strtotime($offer['expires_at'])); ?></strong></span>
                                </div>
                            </div>

                            <!-- Bouton réserver -->
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $restaurantPhone); ?>?text=<?php echo urlencode("Bonjour ! Je souhaite réserver un panier \"" . $offer['product_name'] . "\" Too Good To Go à " . number_format($offer['discount_price'], 2) . "€. Merci !"); ?>"
                               target="_blank"
                               class="block w-full py-4 bg-green-500 text-white text-center rounded-xl font-bold text-lg hover:bg-green-600 transition-all transform hover:scale-105">
                                <i class="fab fa-whatsapp mr-2"></i>Réserver maintenant
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Lien retour -->
            <div class="text-center mt-8">
                <a href="/" class="inline-block px-6 py-3 glass rounded-xl font-semibold text-gray-900 hover:bg-white transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au menu complet
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto mt-12 text-center">
        <div class="glass rounded-2xl p-6">
            <div class="flex items-center justify-center gap-2 mb-3">
                <i class="fas fa-leaf text-green-600"></i>
                <p class="text-gray-700 font-semibold">Ensemble contre le gaspillage alimentaire</p>
            </div>
            <p class="text-gray-600 text-sm">
                <?php echo htmlspecialchars($restaurantName); ?>
                <?php if ($restaurantAddress): ?>
                    • <?php echo htmlspecialchars($restaurantAddress); ?>
                <?php endif; ?>
            </p>
        </div>
    </footer>
</body>
</html>
