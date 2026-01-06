<?php
// Servir TOUT le JavaScript en UN SEUL fichier
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Charger dans l'ordre correct
echo "// ========== APP.JS ==========\n";
echo file_get_contents(__DIR__ . '/js/app.js');
echo "\n\n// ========== CUSTOMERS.JS ==========\n";
echo file_get_contents(__DIR__ . '/js/customers.js');
echo "\n\n// ========== PRODUCTS.JS ==========\n";
echo file_get_contents(__DIR__ . '/js/products.js');
