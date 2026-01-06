<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
readfile(__DIR__ . '/restaurant.json');
