<?php
/**
 * Discover billing-related views and tables in the MORESCO SQL Server.
 * Run from project root: php test_billing_schema.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MorescoDbService;

$service = new MorescoDbService();
$pdo = (new ReflectionClass($service))->getMethod('getConnection');
$pdo->setAccessible(true);
$conn = $pdo->invoke($service);

echo "=== VIEWS & TABLES (billing/payment related) ===\n";
$stmt = $conn->query("
    SELECT TABLE_TYPE, TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%bill%'
       OR TABLE_NAME LIKE '%payment%'
       OR TABLE_NAME LIKE '%due%'
       OR TABLE_NAME LIKE '%receiv%'
       OR TABLE_NAME LIKE '%receipt%'
       OR TABLE_NAME LIKE '%statement%'
       OR TABLE_NAME LIKE '%invoice%'
       OR TABLE_NAME LIKE '%balance%'
    ORDER BY TABLE_TYPE, TABLE_NAME
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "No billing/payment related tables or views found.\n";
} else {
    foreach ($rows as $row) {
        echo "[{$row['TABLE_TYPE']}] {$row['TABLE_NAME']}\n";
    }
}

echo "\n=== ALL VIEWS (for reference) ===\n";
$stmt = $conn->query("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS ORDER BY TABLE_NAME
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  {$row['TABLE_NAME']}\n";
}
