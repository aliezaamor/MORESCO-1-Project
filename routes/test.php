<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-billing/{account}', function ($account) {
    $service = app(\App\Services\MorescoDbService::class);
    
    // Get raw data from new logic
    $pdo = $service->getConnection();
    
    // 1. Map ID
    $stmtMap = $pdo->prepare("SELECT account_no FROM dbo.account WHERE member_id = ?");
    $stmtMap->execute([$account]);
    $mapped = $stmtMap->fetchAll(\PDO::FETCH_COLUMN);
    
    if (empty($mapped)) $mapped = [$account];
    $inPlaceholders = str_repeat('?,', count($mapped) - 1) . '?';

    // 2. Raw Metering data
    $stmtAcc = $pdo->prepare("SELECT TOP 5 * FROM dbo.VW_ACCOUNTS_METER_READING WHERE account_no IN ($inPlaceholders) ORDER BY billmo DESC, rdng_date DESC");
    $stmtAcc->execute($mapped);
    $metering = $stmtAcc->fetchAll(\PDO::FETCH_ASSOC);
    
    // 3. Raw Billing ledger data
    $stmtBill = $pdo->prepare("SELECT TOP 5 * FROM dbo.vw_AccountTransactions WHERE account_no IN ($inPlaceholders) ORDER BY trans_date DESC");
    $stmtBill->execute($mapped);
    $ledger = $stmtBill->fetchAll(\PDO::FETCH_ASSOC);

    return response()->json([
        'mapped_accounts' => $mapped,
        'final_service_output' => $service->getMemberBillingData($account),
        'raw_metering_latest_5' => $metering,
        'raw_ledger_latest_5' => $ledger
    ]);
});
