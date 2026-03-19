<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MorescoDbService;

$service = app(MorescoDbService::class);
$accountNo = '01040436';

try {
    $pdo = $service->getConnection();
    
    echo "--- Status ID Distribution for Account $accountNo ---\n";
    $stmt = $pdo->prepare("SELECT status_id, COUNT(*) as count, SUM(debit) as sum_debit, SUM(credit) as sum_credit FROM dbo.vw_AccountTransactions WHERE account_no = ? GROUP BY status_id");
    $stmt->execute([$accountNo]);
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\n--- Duplicate OR check ---\n";
    $sql = "SELECT [official_receipt], COUNT(*) as count, SUM(debit) as total_debit 
            FROM dbo.vw_AccountTransactions 
            WHERE account_no = ? AND [official_receipt] IS NOT NULL 
            GROUP BY [official_receipt] 
            HAVING COUNT(*) > 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$accountNo]);
    $dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($dupes);

    if (!empty($dupes)) {
        foreach ($dupes as $dupe) {
            echo "\nDetails for OR {$dupe['official_receipt']}:\n";
            $stmt = $pdo->prepare("SELECT transaction_id, trans_date, debit, credit, status_id FROM dbo.vw_AccountTransactions WHERE [official_receipt] = ? AND account_no = ?");
            $stmt->execute([$dupe['official_receipt'], $accountNo]);
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
