<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Query for an account where true_balance == bill_amount
$pdo = app(\App\Services\MorescoDbService::class)->getConnection();

$stmt = $pdo->prepare("
    SELECT a.account_no, 
           (SUM(a.credit) - SUM(a.debit)) AS true_balance,
           MAX(b.bill_amount) AS latest_bill
    FROM dbo.vw_AccountTransactions a
    -- Join to get their latest bill amount
    OUTER APPLY (
        SELECT TOP 1 credit AS bill_amount
        FROM dbo.vw_AccountTransactions b
        WHERE b.account_no = a.account_no
          AND b.credit > 0
          AND b.[official_receipt] IS NULL
          AND (b.isReversed IS NULL OR b.isReversed = 0)
        ORDER BY b.trans_date DESC
    ) b
    WHERE (a.isReversed IS NULL OR a.isReversed = 0)
    GROUP BY a.account_no
    HAVING (SUM(a.credit) - SUM(a.debit)) = MAX(b.bill_amount)
       AND (SUM(a.credit) - SUM(a.debit)) > 500 -- Just to make sure it's a real bill
");
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    $testAccount = $rows[0]['account_no'];
    echo "Found account with exact matching balance and bill: " . $testAccount . " (Bal: " . $rows[0]['true_balance'] . ", Bill: " . $rows[0]['latest_bill'] . ")\n\n";
    
    $service = app(\App\Services\SmsProcessingService::class);
    $result = $service->processIncomingMessage('+639123456789', 'BILL ' . $testAccount);

    echo "Auto Reply Generated for account {$testAccount}:\n";
    echo $result['auto_reply']->content . "\n";

} else {
    echo "No matching accounts found.\n";
}
