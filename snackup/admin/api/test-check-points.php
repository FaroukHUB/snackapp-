<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../bootstrap.php';

echo "=== TEST checkPoints ===\n\n";

$phone = '0555227881';
echo "Phone: $phone\n";

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
echo "Use MySQL: " . ($useMySQL ? 'YES' : 'NO') . "\n\n";

if ($useMySQL) {
    echo "Getting all customers...\n";
    $allCustomers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    echo "Found " . count($allCustomers) . " customers\n\n";
    
    if (count($allCustomers) > 0) {
        echo "First customer: " . print_r($allCustomers[0], true) . "\n";
    }
} else {
    echo "JSON mode not tested\n";
}
