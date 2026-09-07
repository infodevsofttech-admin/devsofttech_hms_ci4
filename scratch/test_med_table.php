<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'hms_data_ci4';

$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Test building medicines HTML
require_once 'vendor/autoload.php';

echo "Database connection successful\n";
