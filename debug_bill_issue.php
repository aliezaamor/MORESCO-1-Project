<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MorescoDbService;

$service = app(MorescoDbService::class);
$accountNo = '01040436';

echo "Debugging Account: $accountNo\n";

try {
    $pdo = $service->getConnection();
    
    echo "\n--- VW_ACCOUNTS_METER_READING ---\n";
    $stmt = $pdo->prepare("SELECT TOP 3 * FROM dbo.VW_ACCOUNTS_METER_READING WHERE account_no = ? ORDER BY billmo DESC, rdng_date DESC");
    $stmt->execute([$accountNo]);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($readings);

    echo "\n--- Calculated Balance (Excluding status_id 7) ---\n";
    $stmt = $pdo->prepare("SELECT SUM(credit) as total_credit, SUM(debit) as total_debit, SUM(credit) - SUM(debit) AS true_balance 
                           FROM dbo.vw_AccountTransactions 
                           WHERE account_no = ? AND (isReversed IS NULL OR isReversed = 0) AND status_id <> 7");
    $stmt->execute([$accountNo]);
    print_r($stmt->fetch(PDO::FETCH_ASSOC));

    echo "\n--- Service Method getMemberBillingData Output ---\n";
    $billing = $service->getMemberBillingData($accountNo);
    print_r($billing);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
