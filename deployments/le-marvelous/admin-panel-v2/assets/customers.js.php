<?php
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
echo file_get_contents(__DIR__ . '/js/customers.js');
