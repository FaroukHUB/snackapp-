<!DOCTYPE html>
<html lang="fr">
<head>
<?php
/**
 * Page Fidélité dynamique
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
    $loyaltyEnabled = $features['loyalty_card'] ?? false;

    // Inclure les meta tags dynamiques
    include __DIR__ . '/includes/meta-tags-page.php';

} catch (Exception $e) {
    // Fallback en cas d'erreur
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Programme Fidélité</title>';
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
        <?php if (!$loyaltyEnabled): ?>
        <section class="info-section">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <p>Le programme de fidélité n'est pas encore activé pour ce restaurant.</p>
            </div>
        </section>
        <?php else: ?>

        <!-- Hero Section -->
        <section class="page-hero" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <div class="page-hero-content">
                <div class="hero-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h1>Programme Fidélité</h1>
                <p>Cumulez des points à chaque commande et gagnez des récompenses exclusives !</p>
            </div>
        </section>

        <!-- How It Works -->
        <section class="info-section">
            <h2><i class="fas fa-question-circle"></i> Comment ça marche ?</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                    <h3>Créez votre compte</h3>
                    <p>Automatique avec votre numéro lors de votre première commande</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="fas fa-shopping-bag"></i></div>
                    <h3>Commandez</h3>
                    <p>Gagnez 10 points par euro dépensé</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="fas fa-gift"></i></div>
                    <h3>Débloquez des récompenses</h3>
                    <p>Échangez vos points contre des produits gratuits</p>
                </div>
            </div>
        </section>

        <!-- Rewards -->
        <section class="info-section section-alt-bg">
            <h2><i class="fas fa-trophy"></i> Vos récompenses</h2>
            <p class="section-subtitle">Débloquez ces récompenses exclusives avec vos points</p>

            <div class="rewards-grid" id="rewardsGrid">
                <!-- Les récompenses seront chargées dynamiquement via JavaScript -->
                <div class="text-center" style="grid-column: 1/-1; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: var(--primary);"></i>
                    <p style="margin-top: 16px; color: var(--text-secondary);">Chargement des récompenses...</p>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="info-section">
            <h2><i class="fas fa-question-circle"></i> Questions fréquentes</h2>
            <div class="faq-list">
                <div class="faq-item">
                    <h3>Comment créer mon compte fidélité ?</h3>
                    <p>Votre compte est créé automatiquement avec votre numéro de téléphone lors de votre première commande. Pas besoin d'inscription !</p>
                </div>
                <div class="faq-item">
                    <h3>Mes points expirent-ils ?</h3>
                    <p>Vos points sont valables 12 mois à partir de votre dernière commande.</p>
                </div>
                <div class="faq-item">
                    <h3>Combien de points gagne-t-on par euro ?</h3>
                    <p>Vous gagnez 10 points par euro dépensé chez <?php echo htmlspecialchars($restaurant['name'] ?? 'notre restaurant'); ?>.</p>
                </div>
                <div class="faq-item">
                    <h3>Puis-je cumuler plusieurs récompenses ?</h3>
                    <p>Oui ! Vous pouvez échanger autant de récompenses que vous le souhaitez tant que vous avez assez de points.</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="info-section section-alt-bg">
            <div class="cta-box">
                <h2>Prêt à gagner des récompenses ?</h2>
                <p>Commencez à cumuler des points dès maintenant !</p>
                <a href="index.html" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Commander maintenant
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
    <script src="js/loyalty.js"></script>
    <script src="js/dynamic-content.js"></script>
</body>
</html>
