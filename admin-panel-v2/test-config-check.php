<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$configFile = '../config/fabrik-burger.config.js';
$absolutePath = realpath($configFile);

echo json_encode([
    'file' => $configFile,
    'absolute_path' => $absolutePath,
    'exists' => file_exists($configFile),
    'readable' => is_readable($configFile),
    'writable' => is_writable($configFile),
    'size' => file_exists($configFile) ? filesize($configFile) : 0,
    'modified' => file_exists($configFile) ? date('Y-m-d H:i:s', filemtime($configFile)) : null,
    'permissions' => file_exists($configFile) ? substr(sprintf('%o', fileperms($configFile)), -4) : null
]);
?>
