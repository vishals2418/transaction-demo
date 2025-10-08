<?php

use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Transactions routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);

    // Additional routes for validating receiver and adding money
    Route::get('/validate-receiver/{id}', [TransactionController::class, 'validateReceiver']);
    Route::post('/add-money', [TransactionController::class, 'addMoney']);
});
