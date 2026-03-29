<!DOCTYPE html>
<html lang="fr">
<head>
<?php
/**
 * Page Click & Collect dynamique
 */
require_once __DIR__ . '/../backend/InstanceManager.php';

try {
    InstanceManager::init();
    $config = InstanceManager::loadConfig();
    $restaurant = $config['app'] ?? [];
    $location = $config['location'] ?? [];
    $contact = $config['contact'] ?? [];
    $features = $config['features'] ?? [];

    // Vérifier si la fonctionnalité est activée
    $clickCollectEnabled = $features['click_and_collect'] ?? false;

    // Inclure les meta tags dynamiques
    include __DIR__ . '/includes/meta-tags-page.php';

} catch (Exception $e) {
    // Fallback en cas d'erreur
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Click & Collect</title>';
}
?>

    <!-- Styles -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pages.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <a href="index.html" class="back-link">
                <i class="fas fa-arrow-left"></i>
                <span>Retour</span>
            </a>
        </div>
        <div class="logo">
            <span class="logo-text"><?php echo htmlspecialchars($restaurant['name'] ?? 'Restaurant'); ?></span>
        </div>
        <div class="header-right">
            <a href="cart.html" class="cart-link">
                <i class="fas fa-shopping-cart"></i>
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="page-content">
        <?php if (!$clickCollectEnabled): ?>
        <section class="info-section">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <p>Le service Click & Collect n'est pas encore activé pour ce restaurant.</p>
            </div>
        </section>
        <?php else: ?>

        <!-- Hero Section -->
        <section class="page-hero" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <div class="page-hero-content">
                <div class="hero-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h1>Click & Collect</h1>
                <p>Commandez en ligne et récupérez vos produits sans attente !</p>
            </div>
        </section>

        <!-- How It Works -->
        <section class="info-section">
            <h2><i class="fas fa-question-circle"></i> Comment ça marche ?</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3>Commandez en ligne</h3>
                    <p>Parcourez notre menu et passez commande en quelques clics</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="fas fa-clock"></i></div>
                    <h3>Choisissez votre créneau</h3>
                    <p>Sélectionnez l'heure de retrait qui vous convient</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="fas fa-store"></i></div>
                    <h3>Récupérez votre commande</h3>
                    <p>Venez chercher votre commande prête, sans file d'attente</p>
                </div>
            </div>
        </section>

        <!-- Benefits -->
        <section class="info-section section-alt-bg">
            <h2><i class="fas fa-star"></i> Les avantages</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Gain de temps</h3>
                    <p>Plus d'attente, votre commande est prête à l'heure que vous avez choisie</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                    <h3>Pas de frais de livraison</h3>
                    <p>Économisez les frais de livraison en venant récupérer vous-même</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                        <i class="fas fa-fire"></i>
                    </div>
                    <h3>Produits frais</h3>
                    <p>Récupérez vos produits tout juste préparés, à la température idéale</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Flexible</h3>
                    <p>Choisissez votre créneau horaire selon votre disponibilité</p>
                </div>
            </div>
        </section>

        <!-- Opening Hours -->
        <section class="info-section">
            <h2><i class="fas fa-clock"></i> Horaires de retrait</h2>
            <div class="hours-info">
                <p><i class="fas fa-info-circle"></i> Vous pouvez récupérer votre commande pendant nos horaires d'ouverture.</p>
                <div class="hours-grid" id="openingHours">
                    <!-- Les horaires seront chargés dynamiquement via JavaScript -->
                    <p style="text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-spinner fa-spin"></i> Chargement des horaires...
                    </p>
                </div>
            </div>
        </section>

        <!-- Location -->
        <section class="info-section section-alt-bg">
            <h2><i class="fas fa-map-marker-alt"></i> Notre adresse</h2>
            <div class="location-card">
                <div class="location-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="location-details">
                    <h3><?php echo htmlspecialchars($restaurant['name'] ?? 'Restaurant'); ?></h3>
                    <p>
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($location['address'] ?? ''); ?>
                        <?php if (!empty($location['postalCode']) && !empty($location['city'])): ?>
                        <br><?php echo htmlspecialchars($location['postalCode'] . ' ' . $location['city']); ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($contact['phone'])): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($contact['phoneDisplay'] ?? $contact['phone']); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($location['googleMapsUrl'])): ?>
                <a href="<?php echo htmlspecialchars($location['googleMapsUrl']); ?>" target="_blank" class="btn btn-outline">
                    <i class="fas fa-directions"></i>
                    Itinéraire
                </a>
                <?php endif; ?>
            </div>
        </section>

        <!-- FAQ -->
        <section class="info-section">
            <h2><i class="fas fa-question-circle"></i> Questions fréquentes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <h3>Quel est le délai minimum pour passer commande ?</h3>
                    <p>Nous recommandons de passer commande au minimum 30 minutes avant l'heure de retrait souhaitée.</p>
                </div>
                <div class="faq-item">
                    <h3>Puis-je modifier ma commande après l'avoir passée ?</h3>
                    <p>Contactez-nous rapidement par téléphone. Si la préparation n'a pas commencé, nous pourrons modifier votre commande.</p>
                </div>
                <div class="faq-item">
                    <h3>Comment savoir si ma commande est prête ?</h3>
                    <p>Vous recevrez une notification par SMS lorsque votre commande sera prête à être retirée.</p>
                </div>
                <div class="faq-item">
                    <h3>Que se passe-t-il si je suis en retard ?</h3>
                    <p>Pas de problème ! Votre commande reste disponible. Nous la conserverons au chaud pour vous.</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="info-section section-alt-bg">
            <div class="cta-box">
                <h2>Prêt à commander ?</h2>
                <p>Commandez maintenant et récupérez votre commande sans attente !</p>
                <a href="index.html" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Commander en Click & Collect
                </a>
            </div>
        </section>

        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($restaurant['name'] ?? 'Restaurant'); ?>. Tous droits réservés.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/config.js"></script>
    <script src="js/app.js"></script>
    <script src="js/dynamic-content.js"></script>
</body>
</html>
