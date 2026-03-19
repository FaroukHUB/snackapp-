<?php
/**
 * Sitemap XML dynamique
 * Génère le sitemap.xml selon l'instance courante
 */
header('Content-Type: application/xml; charset=utf-8');

require_once __DIR__ . '/../backend/InstanceManager.php';

try {
    InstanceManager::init();
    $config = InstanceManager::loadConfig();
    $restaurant = $config['app'] ?? [];
    $location = $config['location'] ?? [];

    // Obtenir le domaine principal
    $instance = InstanceManager::detectInstance();
    $instancesConfig = InstanceManager::getInstancesConfig();
    $domains = $instancesConfig['instances'][$instance]['domains'] ?? [];
    $primaryDomain = $domains[0] ?? 'localhost';
    $baseUrl = 'https://' . $primaryDomain;

    $lastMod = date('Y-m-d');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n\n";

    // Page d'accueil
    echo "    <url>\n";
    echo "        <loc>{$baseUrl}/snackup/frontend/</loc>\n";
    echo "        <lastmod>{$lastMod}</lastmod>\n";
    echo "        <changefreq>daily</changefreq>\n";
    echo "        <priority>1.0</priority>\n";
    echo "        <image:image>\n";
    echo "            <image:loc>{$baseUrl}/images/hero.jpg</image:loc>\n";
    echo "            <image:title>" . htmlspecialchars($restaurant['name'] ?? 'Restaurant') . "</image:title>\n";
    echo "        </image:image>\n";
    echo "    </url>\n\n";

    // Page panier
    echo "    <url>\n";
    echo "        <loc>{$baseUrl}/snackup/frontend/cart.html</loc>\n";
    echo "        <lastmod>{$lastMod}</lastmod>\n";
    echo "        <changefreq>weekly</changefreq>\n";
    echo "        <priority>0.8</priority>\n";
    echo "    </url>\n\n";

    // Page à propos
    echo "    <url>\n";
    echo "        <loc>{$baseUrl}/snackup/frontend/a-propos.html</loc>\n";
    echo "        <lastmod>{$lastMod}</lastmod>\n";
    echo "        <changefreq>monthly</changefreq>\n";
    echo "        <priority>0.9</priority>\n";
    echo "    </url>\n\n";

    echo "</urlset>\n";

} catch (Exception $e) {
    http_response_code(500);
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<error>' . htmlspecialchars($e->getMessage()) . '</error>';
}
