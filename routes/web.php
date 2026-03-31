<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AdminActivityController;


// Auth Routes
Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
Route::post('/login', [AuthController::class , 'login']);
Route::get('/register', [AuthController::class , 'showRegister'])->name('register');
Route::post('/register', [AuthController::class , 'register']);
Route::post('/logout', [AuthController::class , 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [PasswordResetController::class , 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class , 'sendResetLinkEmail'])->name('password.email');
Route::get('/verify-password', [PasswordResetController::class , 'showVerifyForm'])->name('password.verify');
Route::post('/verify-password', [PasswordResetController::class , 'verifyCode'])->name('password.confirm');
Route::get('/reset-password/{token}', [PasswordResetController::class , 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class , 'reset'])->name('password.update');

// Admin activity logs
Route::get('/admin/activities', [AdminActivityController::class, 'index'])->name('admin.activities');

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

        Route::get('/contacts', function () {
            return view('contacts.index');
        }
        )->name('view.contacts.index');

        // Admin-only Accounts Master List
        Route::prefix('accounts')->group(function () {
            Route::get('/', [\App\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
            Route::get('/data', [\App\Http\Controllers\AccountController::class, 'data'])->name('accounts.data');
            Route::get('/{account}', [\App\Http\Controllers\AccountController::class, 'show'])->name('accounts.show');
        });

        Route::get('/messages', function () {
            return view('messages.index');
        }
        )->name('view.messages.index');

        Route::get('/keywords', function () {
            return view('keywords.index');
        }
        )->name('view.keywords.index');

        Route::get('/simulator', function () {
            return view('simulator.index');
        }
        )->name('view.simulator.index');

        // SMS Activity Monitor (rate limiting)
        Route::get('/sms/activity', [\App\Http\Controllers\RateLimitController::class, 'index'])->name('sms.activity');
        Route::get('/sms/activity/data', [\App\Http\Controllers\RateLimitController::class, 'data'])->name('sms.activity.data');
        Route::get('/sms/activity/listener-status', [\App\Http\Controllers\RateLimitController::class, 'listenerStatus'])->name('sms.activity.listener');
        Route::post('/sms/activity/{contact}/unblock', [\App\Http\Controllers\RateLimitController::class, 'unblock'])->name('sms.activity.unblock');

        Route::get('/test-billing', function (\Illuminate\Http\Request $request) {
            $account = $request->get('account');
            if (!$account) return view('test_billing');
            
            try {
                $service = app(\App\Services\MorescoDbService::class);
                
                $pdo = $service->getConnection();
                $stmtMap = $pdo->prepare("SELECT member_id FROM dbo.account WHERE account_no = ?");
                $stmtMap->execute([$account]);
                $accountRecord = $stmtMap->fetch(\PDO::FETCH_ASSOC);
                
                $memberRaw = null;
                $memberName = 'Not Found';
                
                if ($accountRecord) {
                    $stmtMem = $pdo->prepare("SELECT MemberName, sa_code FROM dbo.vw_members_list WHERE member_ID = ?");
                    $stmtMem->execute([$accountRecord['member_id']]);
                    $memberRaw = $stmtMem->fetch(\PDO::FETCH_ASSOC);
                    $memberName = $memberRaw ? $memberRaw['MemberName'] : 'Not Found';
                }
            
                $stmtAcc = $pdo->prepare("SELECT TOP 5 * FROM dbo.VW_ACCOUNTS_METER_READING WHERE account_no = ? ORDER BY billmo DESC, rdng_date DESC");
                $stmtAcc->execute([$account]);
                $metering = $stmtAcc->fetchAll(\PDO::FETCH_ASSOC);
                
                $stmtBill = $pdo->prepare("SELECT TOP 5 * FROM dbo.vw_AccountTransactions WHERE account_no = ? ORDER BY trans_date DESC");
                $stmtBill->execute([$account]);
                $ledger = $stmtBill->fetchAll(\PDO::FETCH_ASSOC);
                
                $billing = $service->getMemberBillingData($account);
                
                // Get Outage Info
                $saCode = $memberRaw ? $memberRaw['sa_code'] : null;
                $outage = $service->getMemberOutageData($account, $saCode);
            
                return view('test_billing', [
                    'member' => ['name' => $memberName],
                    'member_raw' => $memberRaw,
                    'mapped' => [$account],
                    'billing' => $billing,
                    'outage' => $outage,
                    'metering' => $metering,
                    'ledger' => $ledger
                ]);
            } catch (\Exception $e) {
                return view('test_billing', ['error' => $e->getMessage()]);
            }
        });


        Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class , 'update'])->name('profile.update');

        Route::get('/settings', [App\Http\Controllers\SettingsController::class , 'index'])->name('settings.index');
        Route::put('/settings/password', [App\Http\Controllers\SettingsController::class , 'updatePassword'])->name('settings.password.update');
    });
