
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "plumbing_bill_shop";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed. Please check config/db.php settings.");
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/PHP_Projects/plumbing_bill_shop');
}
