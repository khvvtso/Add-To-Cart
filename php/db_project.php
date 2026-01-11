<?php
// php/db_project.php
// Connect to the `product_management` database (separate helper so we don't change the existing db.php)
$host = 'localhost';
$db   = 'product_management';
$user = 'root'; // default for XAMPP
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo_project = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // In production, you might log the error instead of showing it
    die('Database connection failed: ' . $e->getMessage());
}
