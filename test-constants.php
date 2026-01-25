<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test des constantes bootstrap<br><br>";

require_once __DIR__ . '/snackup/admin/bootstrap.php';

echo "SNACK_USE_JSON = " . (SNACK_USE_JSON ? 'TRUE' : 'FALSE') . "<br>";
echo "SNACK_DB_ERROR défini? " . (defined('SNACK_DB_ERROR') ? 'OUI: ' . SNACK_DB_ERROR : 'NON') . "<br>";
echo "SNACK_RESTAURANT_ID = " . SNACK_RESTAURANT_ID . "<br><br>";

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
echo "\$useMySQL = " . ($useMySQL ? 'TRUE (MySQL)' : 'FALSE (JSON)') . "<br>";
