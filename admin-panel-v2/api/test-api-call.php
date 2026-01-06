<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simuler un appel GET
$_GET['action'] = 'check_points';
$_GET['phone'] = '0555227881';

echo "=== Simulating API call ===\n";
echo "GET params: " . print_r($_GET, true) . "\n\n";
echo "=== Output from loyalty-public.php: ===\n";

// Capturer l'output
ob_start();
require '/home/zajr1824/Marvelous.mon-agenceweb.fr/admin-panel-v2/api/loyalty-public.php';
$output = ob_get_clean();

echo $output;
echo "\n\n=== End of output ===\n";
