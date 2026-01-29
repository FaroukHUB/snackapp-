<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

echo "1. Start\n";

try {
    echo "2. Loading bootstrap...\n";
    require_once __DIR__ . '/../bootstrap.php';
    echo "3. Bootstrap OK\n";

    echo "4. Loading SettingsRepository...\n";
    require_once SNACK_ROOT . '/snackup/backend/repositories/SettingsRepository.php';
    echo "5. SettingsRepository OK\n";

    echo "6. Testing DB connection...\n";
    $cities = SettingsRepository::getAllDeliveryCities();
    echo "7. DB OK - Cities: " . count($cities) . "\n";

    echo json_encode(['success' => true, 'cities' => $cities]);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
