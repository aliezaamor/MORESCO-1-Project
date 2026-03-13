<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Query for accounts that have a balance > 0
$pdo = app(\App\Services\MorescoDbService::class)->getConnection();
$stmt = $pdo->prepare("
    SELECT account_no, SUM(credit) - SUM(debit) AS true_balance
    FROM dbo.vw_AccountTransactions
    WHERE (isReversed IS NULL OR isReversed = 0)
    GROUP BY account_no
    HAVING SUM(credit) - SUM(debit) > 500
    ORDER BY true_balance DESC
");
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    echo "Found account with balance: " . $rows[0]['account_no'] . " (Bal: " . $rows[0]['true_balance'] . ")\n\n";
    
    $service = app(\App\Services\SmsProcessingService::class);
    $result = $service->processIncomingMessage('+639123456789', 'BILL ' . $rows[0]['account_no']);

    echo "Auto Reply Generated for account {$rows[0]['account_no']}:\n";
    echo $result['auto_reply']->content . "\n";

} else {
    echo "No accounts with positive balance found.\n";
}

