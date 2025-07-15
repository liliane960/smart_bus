<?php
$host = "localhost";
$dbname = "smart_bus";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
