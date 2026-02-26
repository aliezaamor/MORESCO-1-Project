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


        Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class , 'update'])->name('profile.update');

        Route::get('/settings', [App\Http\Controllers\SettingsController::class , 'index'])->name('settings.index');
        Route::put('/settings/password', [App\Http\Controllers\SettingsController::class , 'updatePassword'])->name('settings.password.update');
    });
