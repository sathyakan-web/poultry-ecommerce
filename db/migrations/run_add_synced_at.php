<?php
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'poultry_ecommerce';
$DB_USER = 'root';
$DB_PASS = '';
$DB_CHARSET = 'utf8mb4';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }

$alters = [
    'ALTER TABLE products ADD COLUMN synced_at DATETIME NULL AFTER created_at',
    'ALTER TABLE inventory ADD COLUMN synced_at DATETIME NULL AFTER updated_at',
];

foreach ($alters as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "OK: $sql\n";
    } else {
        echo "SKIP/ERR: $sql\n";
    }
}
mysqli_close($conn);
