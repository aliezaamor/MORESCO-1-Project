<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS moresco CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "OK\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
