<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// For Laravel Sanctum User Info Authentication retrieval
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// POS Mobile App Sync Endpoint
// Protected with Sanctum middleware. App should send Bearer Token.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sync/orders', [\App\Http\Controllers\Api\SyncController::class, 'syncOrders']);
});
