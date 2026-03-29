<?php
/**
 * Meta Tags dynamiques pour les pages (Fidélité, Click & Collect, etc.)
 * Variables attendues : $config, $restaurant, $location, $pageName (optionnel)
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

// Déterminer le nom de la page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = $restaurant['name'] ?? 'Restaurant';
$pageDescription = '';

switch ($currentPage) {
    case 'fidelite':
        $pageTitle = 'Programme Fidélité - ' . ($restaurant['name'] ?? 'Restaurant');
        $pageDescription = 'Rejoignez le programme fidélité ! Cumulez des points à chaque commande et débloquez des récompenses exclusives.';
        break;
    case 'click-collect':
        $pageTitle = 'Click & Collect - ' . ($restaurant['name'] ?? 'Restaurant');
        $pageDescription = 'Commandez en ligne et récupérez vos produits sans attente. Click & Collect rapide et pratique.';
        break;
    case 'a-propos':
        $pageTitle = 'À Propos - ' . ($restaurant['name'] ?? 'Restaurant');
        $pageDescription = 'Découvrez ' . ($restaurant['name'] ?? 'notre restaurant') . ', notre histoire et nos valeurs.';
        break;
    default:
        $pageTitle = ($restaurant['name'] ?? 'Restaurant');
        $pageDescription = 'Commandez en ligne chez ' . ($restaurant['name'] ?? 'notre restaurant');
}
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="restaurant, commande en ligne, <?php echo strtolower($restaurant['name'] ?? ''); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($restaurant['name'] ?? 'Restaurant'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo $baseUrl; ?>/snackup/frontend/<?php echo $currentPage; ?>.php">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $baseUrl; ?>/snackup/frontend/<?php echo $currentPage; ?>.php">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="<?php echo $baseUrl; ?>/images/hero.webp">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>">
    <meta property="og:locale" content="<?php echo $config['app']['locale'] ?? 'fr_FR'; ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo $baseUrl; ?>/snackup/frontend/<?php echo $currentPage; ?>.php">
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
    <meta name="theme-color" content="<?php echo $config['branding']['primaryColor'] ?? '#e63946'; ?>">
    <meta name="msapplication-TileColor" content="<?php echo $config['branding']['primaryColor'] ?? '#e63946'; ?>">
    <meta name="msapplication-TileImage" content="../images/favicon.png">
