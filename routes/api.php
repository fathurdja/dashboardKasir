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

Route::post('/xendit/webhook', [\App\Http\Controllers\Api\XenditWebhookController::class, 'handle']);

// =============================================================================
// API v1 — Mobile Flutter Integration
// =============================================================================

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\SyncController as SyncControllerV1;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\QrisPaymentController;
use App\Http\Controllers\Api\V1\DeliveryController;
use App\Http\Controllers\Api\V1\StockReportController;
use App\Http\Controllers\Api\V1\AiController;

// Auth routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Xendit webhook v1 (public, no auth)
Route::post('v1/webhook/xendit', [\App\Http\Controllers\Api\XenditWebhookController::class, 'handle']);

// Protected v1 routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/register-device', [AuthController::class, 'registerDevice']);

    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{receiptNumber}', [TransactionController::class, 'show']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{receiptNumber}/void', [TransactionController::class, 'void']);
    Route::put('/transactions/{receiptNumber}/pay-bon', [TransactionController::class, 'payBon']);

    // Sync
    Route::post('/sync/upload', [SyncControllerV1::class, 'upload']);
    Route::get('/sync/download', [SyncControllerV1::class, 'download']);
    Route::get('/sync/status', [SyncControllerV1::class, 'status']);

    // Analytics
    Route::get('/analytics/daily', [AnalyticsController::class, 'daily']);
    Route::get('/analytics/hourly', [AnalyticsController::class, 'hourly']);
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
    Route::get('/analytics/top-products', [AnalyticsController::class, 'topProducts']);
    Route::get('/analytics/bon-report', [AnalyticsController::class, 'bonReport']);

    // QRIS Payment
    Route::post('/payments/qris/create', [QrisPaymentController::class, 'create']);
    Route::get('/payments/qris/{receiptNumber}/status', [QrisPaymentController::class, 'status']);

    // Delivery
    Route::post('/delivery/start-shift', [DeliveryController::class, 'startShift']);
    Route::post('/delivery/end-shift', [DeliveryController::class, 'endShift']);
    Route::get('/delivery/my-orders', [DeliveryController::class, 'myOrders']);
    Route::put('/delivery/orders/{receiptNumber}/status', [DeliveryController::class, 'updateDeliveryStatus']);
    Route::get('/delivery/performance', [DeliveryController::class, 'performance']);

    // Stock Report
    Route::get('/stock-report/daily', [StockReportController::class, 'daily']);

    // AI-STICH Placeholder
    Route::get('/ai/forecast', [AiController::class, 'forecast']);
});
