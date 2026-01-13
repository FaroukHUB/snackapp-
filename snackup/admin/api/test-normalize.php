<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../bootstrap.php';

function normalizePhone($phone) {
    $normalized = preg_replace('/[^0-9+]/', '', $phone);
    $normalized = ltrim($normalized, '+');
    if (preg_match('/^0([1-9]\d{8})$/', $normalized, $matches)) {
        $normalized = '33' . $matches[1];
    }
    return $normalized;
}

echo "=== TEST Normalisation ===\n\n";

$testPhone = '0555227881';
$normalized = normalizePhone($testPhone);
echo "Input: $testPhone\n";
echo "Normalized: $normalized\n\n";

// Chercher tous les clients
$allCustomers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
echo "Searching in " . count($allCustomers) . " customers...\n\n";

$found = false;
foreach ($allCustomers as $customer) {
    $customerPhone = $customer['phone'] ?? '';
    $normalizedCustomer = normalizePhone($customerPhone);
    
    if ($normalizedCustomer === $normalized) {
        echo "FOUND MATCH!\n";
        echo "Customer ID: " . $customer['id'] . "\n";
        echo "Customer name: " . $customer['name'] . "\n";
        echo "Customer phone (stored): " . $customerPhone . "\n";
        echo "Customer phone (normalized): " . $normalizedCustomer . "\n";
        echo "Loyalty points: " . ($customer['loyalty_points'] ?? 0) . "\n";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "NO MATCH FOUND for $testPhone (normalized: $normalized)\n";
    echo "\nFirst 5 customers for reference:\n";
    for ($i = 0; $i < min(5, count($allCustomers)); $i++) {
        $c = $allCustomers[$i];
        echo "- " . $c['name'] . " : " . $c['phone'] . " (normalized: " . normalizePhone($c['phone']) . ")\n";
    }
}
