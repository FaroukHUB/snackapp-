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

$phone = '0555227881';
echo "1. Starting checkPoints with phone: $phone\n";

if (empty($phone)) {
    echo "2. Phone is empty - would call jsonError\n";
    exit;
}

$normalizedPhone = normalizePhone($phone);
echo "2. Normalized phone: $normalizedPhone\n";

$useMySQL = !SNACK_USE_JSON && !defined('SNACK_DB_ERROR');
echo "3. Use MySQL: " . ($useMySQL ? 'YES' : 'NO') . "\n";

if ($useMySQL) {
    echo "4. Calling CustomerRepository::getAll...\n";
    $allCustomers = CustomerRepository::getAll(SNACK_RESTAURANT_ID);
    echo "5. Got " . count($allCustomers) . " customers\n";
} else {
    echo "4. Using JSON mode\n";
    $allCustomers = loadData('customers.json') ?? [];
    echo "5. Got " . count($allCustomers) . " customers\n";
}

$matchingCustomers = [];
$totalPoints = 0;
$mainCustomer = null;

echo "6. Starting loop through customers...\n";
foreach ($allCustomers as $customer) {
    $customerPhone = $customer['phone'] ?? '';
    
    if (normalizePhone($customerPhone) === $normalizedPhone) {
        echo "7. Found match: " . $customer['name'] . " (" . $customerPhone . ")\n";
        $matchingCustomers[] = $customer;
        $totalPoints += (int)($customer['loyalty_points'] ?? 0);
        
        if ($mainCustomer === null) {
            $mainCustomer = $customer;
        }
    }
}

echo "8. Total matching customers: " . count($matchingCustomers) . "\n";
echo "9. Total points: $totalPoints\n";

if ($mainCustomer) {
    echo "10. Main customer found - would call jsonSuccess\n";
    $result = [
        'customer' => [
            'id' => $mainCustomer['id'],
            'name' => $mainCustomer['name'],
            'phone' => $mainCustomer['phone'],
            'loyalty_points' => $totalPoints,
            'duplicate_count' => count($matchingCustomers)
        ]
    ];
    echo "11. Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "10. No customer found - would call jsonError\n";
}
