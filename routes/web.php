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
        }
        )->name('dashboard');

        Route::get('/contacts', function () {
            return view('contacts.index');
        }
        )->name('view.contacts.index');

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

        Route::get('/test-billing', function (\Illuminate\Http\Request $request) {
            $account = $request->get('account');
            if (!$account) return view('test_billing');
            
            try {
                $service = app(\App\Services\MorescoDbService::class);
                
                $pdo = $service->getConnection();
                $stmtMem = $pdo->prepare("SELECT MemberName FROM dbo.vw_members_list WHERE member_ID = ?");
                $stmtMem->execute([$account]);
                $memberRaw = $stmtMem->fetch(\PDO::FETCH_ASSOC);
                $memberName = $memberRaw ? $memberRaw['MemberName'] : 'Not Found';
            
                $stmtMap = $pdo->prepare("SELECT account_no FROM dbo.account WHERE member_id = ?");
                $stmtMap->execute([$account]);
                $mappedRows = $stmtMap->fetchAll(\PDO::FETCH_ASSOC);
                $mapped = array_map(fn($r) => $r['account_no'], $mappedRows);
                
                if (empty($mapped)) $mapped = [$account];
                $inPlaceholders = str_repeat('?,', count($mapped) - 1) . '?';
            
                $stmtAcc = $pdo->prepare("SELECT TOP 5 * FROM dbo.VW_ACCOUNTS_METER_READING WHERE account_no IN ($inPlaceholders) ORDER BY billmo DESC, rdng_date DESC");
                $stmtAcc->execute($mapped);
                $metering = $stmtAcc->fetchAll(\PDO::FETCH_ASSOC);
                
                $stmtBill = $pdo->prepare("SELECT TOP 5 * FROM dbo.vw_AccountTransactions WHERE account_no IN ($inPlaceholders) ORDER BY trans_date DESC");
                $stmtBill->execute($mapped);
                $ledger = $stmtBill->fetchAll(\PDO::FETCH_ASSOC);
                
                $billing = $service->getMemberBillingData($account);
                
                // Get Outage Info
                $saCode = $memberRaw ? $memberRaw['sa_code'] : null;
                $outage = $service->getMemberOutageData($account, $saCode);
            
                return view('test_billing', [
                    'member' => ['name' => $memberName],
                    'member_raw' => $memberRaw,
                    'mapped' => $mapped,
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
