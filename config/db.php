<?php
// config/db.php

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Automatically detect local XAMPP environment vs live server
if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, 'localhost:') === 0 || strpos($host, '127.0.0.1:') === 0) {
    // Localhost XAMPP Credentials
    $servername = 'localhost';
    $username   = 'root';
    $password   = '';
    $dbname     = 'customshirtgen';
} else {
    // Live Server Credentials (Hostinger)
    $servername = 'localhost';
    $username   = 'u919635044_eilotu'; // Update to u919635044_welzee when new DB is created
    $password   = 'Eilotu@123';
    $dbname     = 'u919635044_eilotu';
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    exit('Error connecting to database');
}
?>
