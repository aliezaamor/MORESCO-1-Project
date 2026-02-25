<?php
$env = __DIR__ . '/../.env';
$envData = [];
foreach (file($env, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if ($line[0] === '#') continue;
    $parts = explode('=', $line, 2);
    if (count($parts) === 2) $envData[trim($parts[0])] = trim($parts[1]);
}
$host = $envData['DB_HOST'] ?? '127.0.0.1';
$port = $envData['DB_PORT'] ?? '3306';
$db   = $envData['DB_DATABASE'] ?? 'moresco';
$user = $envData['DB_USERNAME'] ?? 'root';
$pass = $envData['DB_PASSWORD'] ?? '';
try{
    $mysql = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $stmt = $mysql->query('DESCRIBE contacts');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo $c['Field'] . " (" . $c['Type'] . ")\n";
    }
} catch (Exception $e) { echo "ERR: " . $e->getMessage() . "\n"; }
