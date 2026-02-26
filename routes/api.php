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
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('groups', GroupController::class);
    Route::post('/groups/{group}/contacts', [GroupController::class, 'addContacts']);
    Route::delete('/groups/{group}/contacts/{contactId}', [GroupController::class, 'removeContact']);

    Route::apiResource('messages', MessageController::class)->only(['index', 'store']);

    Route::apiResource('keywords', KeywordController::class);

    Route::post('/simulate-receive', [SimulationController::class, 'receive']);
    Route::get('/simulator/history/{contact}', [SimulationController::class, 'history']);
});


