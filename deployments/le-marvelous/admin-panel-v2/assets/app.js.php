<?php
// Servir app.js via PHP
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache 1 heure
header('Pragma: cache');

// Charger et afficher le contenu
echo file_get_contents(__DIR__ . '/js/app.js');
