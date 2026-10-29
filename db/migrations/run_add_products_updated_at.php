<?php
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'poultry_ecommerce';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }

$sql = "ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
if (mysqli_query($conn, $sql)) {
    echo "OK: added products.updated_at\n";
} else {
    echo "ERR: " . mysqli_error($conn) . "\n";
}
mysqli_close($conn);
