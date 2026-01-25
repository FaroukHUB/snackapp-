<?php
// Vider complètement le cache OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache vidé\n";
} else {
    echo "❌ OPcache non disponible\n";
}

// Vérifier le statut
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "Cache hits: " . ($status['opcache_statistics']['hits'] ?? 0) . "\n";
    echo "Cache misses: " . ($status['opcache_statistics']['misses'] ?? 0) . "\n";
}

// Forcer le rechargement des fichiers modifiés
touch(__DIR__ . '/bootstrap.php');
touch(__DIR__ . '/config.php');
touch(__DIR__ . '/api/products.php');

echo "✅ Fichiers touchés\n";
?>
