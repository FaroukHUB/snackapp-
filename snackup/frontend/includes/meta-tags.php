<?php
/**
 * Meta Tags dynamiques
 * Génère tous les meta tags selon l'instance courante
 */

if (!isset($config) || !isset($restaurant)) {
    throw new Exception('Configuration non chargée. Appelez ce fichier depuis un contexte valide.');
}

// Récupérer le domaine principal
$instance = InstanceManager::getCurrentInstance();
$instancesConfig = InstanceManager::getInstancesConfig();
$domains = $instancesConfig['instances'][$instance]['domains'] ?? [];
$primaryDomain = $domains[0] ?? 'localhost';
$baseUrl = 'https://' . $primaryDomain;

$pageTitle = $restaurant['name'] ?? 'Restaurant';
$pageDescription = ($restaurant['brandTagline'] ?? 'Commandez en ligne') . ' - ' . ($location['city'] ?? '');
$pageKeywords = 'restaurant, commande en ligne, ' . strtolower($restaurant['name'] ?? '');
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($restaurant['name'] ?? 'Restaurant'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $baseUrl; ?>/snackup/frontend/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="restaurant">
    <meta property="og:url" content="<?php echo $baseUrl; ?>/snackup/frontend/">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="<?php echo $baseUrl; ?>/images/hero.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>">
    <meta property="og:locale" content="<?php echo $config['app']['locale'] ?? 'fr_FR'; ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $baseUrl; ?>/snackup/frontend/">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo $baseUrl; ?>/images/hero.webp">
    <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>">

    <!-- Favicon & Theme -->
    <link rel="icon" type="image/png" href="../images/favicon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="<?php echo $config['branding']['primaryColor'] ?? '#2ec4b6'; ?>">
    <meta name="msapplication-TileColor" content="<?php echo $config['branding']['primaryColor'] ?? '#2ec4b6'; ?>">
    <meta name="msapplication-TileImage" content="../images/favicon.png">
