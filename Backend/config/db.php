<?php
$host = 'localhost';
$dbname = 'tinder_clone';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    return $pdo;
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>