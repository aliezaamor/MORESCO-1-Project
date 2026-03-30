<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ContactController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\SimulationController;

Route::get('/', function () {
    return response()->json([
        'message' => 'SMS System API',
        'status' => 'Running',
        'endpoints' => [
            'contacts' => url('/api/contacts'),
            'groups' => url('/api/groups'),
        ]
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', function (\App\Services\MorescoDbService $morescoDb) {
        return response()->json([
            'total_contacts' => \App\Models\Contact::count(),
            'total_groups' => \App\Models\Group::where('source', 'app')->count(),
            'moresco_contacts' => $morescoDb->countMembers(''),
            'service_areas' => count($morescoDb->getServiceAreaGroups()),
            'municipalities' => count($morescoDb->getMunicipalityGroups()),
            'barangays' => count($morescoDb->getBarangayGroups()),
            'outgoing_individual' => \App\Models\Message::where('type', 'outgoing')->count(),
            'incoming_individual' => \App\Models\Message::where('type', 'incoming')->count(),
            'outgoing_broadcast' => \App\Models\Message::where('type', 'broadcast')->count(),
            'incoming_keyword' => \App\Models\Message::where('type', 'incoming_keyword')->count(),
            'outgoing_keyword' => \App\Models\Message::where('type', 'auto_reply')->count(),
            'active_keywords' => \App\Models\Keyword::where('is_active', true)->count(),
        ]);
    });

    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{group}/contacts', [GroupController::class, 'addContacts']);
    Route::delete('/groups/{group}/contacts/{contactId}', [GroupController::class, 'removeContact']);

    Route::apiResource('messages', MessageController::class)->only(['index', 'store']);

    Route::apiResource('keywords', KeywordController::class);

    Route::post('/simulate-receive', [SimulationController::class, 'receive']);
    Route::get('/simulator/history/{contact}', [SimulationController::class, 'history']);
});

// Unprotected Webhook for Yeastar Gateway
// Using match(['get', 'post']) to ensure it captures however the TG400 decides to send it!
Route::any('/yeastar/webhook', [\App\Http\Controllers\YeastarController::class, 'webhook']);



Route::get('/test-billing/{account}', function ($account) {
    $service = app(\App\Services\MorescoDbService::class);
    
    // Get raw data from new logic
    $pdo = $service->getConnection();
    
    // 1. Map ID
    $stmtMap = $pdo->prepare("SELECT account_no FROM dbo.account WHERE member_id = ?");
    $stmtMap->execute([$account]);
    $mappedRows = $stmtMap->fetchAll(\PDO::FETCH_ASSOC);
    $mapped = array_map(fn($r) => $r['account_no'], $mappedRows);
    
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
        'raw_ledger_latest_5' => $ledger,
        'keywords' => \App\Models\Keyword::where('keyword', 'LIKE', '%BILL%')->get()
    ]);
});

