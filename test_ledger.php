<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Query for account 05021320
$pdo = app(\App\Services\MorescoDbService::class)->getConnection();

$stmt = $pdo->prepare("SELECT * FROM dbo.vw_AccountTransactions WHERE account_no = '05021320' ORDER BY trans_date DESC");
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "TRANSACTIONS FOR 05021320:\n";
foreach($rows as $r) {
    echo "Date: {$r['trans_date']} | Credit (Bill): {$r['credit']} | Debit (Pay): {$r['debit']} | OR: {$r['official_receipt']} | Bal: {$r['balance']}\n";
}

// Check the other account the user mentioned (feb bill not paid, but running balance is 0? wait the user didn't give the account number)
// Let's query accounts where they have a bill in Feb, but no payment since then, and true_balance <= 0
$stmt = $pdo->prepare("
    SELECT account_no, SUM(credit) - SUM(debit) AS true_balance
    FROM dbo.vw_AccountTransactions
    WHERE (isReversed IS NULL OR isReversed = 0)
    GROUP BY account_no
    HAVING SUM(credit) - SUM(debit) <= 0
");
$stmt->execute();
$balRows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
echo "\nFound " . count($balRows) . " accounts with true_balance <= 0\n";
