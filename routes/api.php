<?php

<<<<<<< HEAD
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\MasterStockController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\ApiV2\StockMonitoringController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Boneva Kasir (dashboardLO)
|--------------------------------------------------------------------------
| Base URL: https://laravel.fathurrzqn8n.web.id/api
|
| 1. AUTENTIKASI & MULTI-USER:
|    - POST /api/login
|    - POST /api/logout
|    - GET  /api/me
|    - GET  /api/users (Admin only)
|
| 2. STOK AWAL (KUOTA KOMODITAS):
|    - GET  /api/stock-awal
|    - POST /api/stock-awal (Admin only)
|
| 3. STOK TERJUAL (PENJUALAN PER VARIAN):
|    - GET  /api/stock-terjual
|    - POST /api/stock-terjual/reset (Admin only)
|
| 4. RINGKASAN STOK (Stok Awal, Terjual, Sisa):
|    - GET  /api/stock-summary
|    - GET  /api/stock-groups
|
| 5. TRANSAKSI (CHECKOUT KASIR):
|    - GET    /api/transaksi
|    - POST   /api/transaksi
|    - GET    /api/transaksi/{id}
|    - PUT    /api/transaksi/{id}
|    - DELETE /api/transaksi/{id}
|
| 6. PRODUK & MASTER STOCKS:
|    - GET  /api/products
|    - GET  /api/master-stocks
*/

// ==========================================
// 1. AUTENTIKASI & MULTI-USER
// ==========================================
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::get('me', [AuthController::class, 'me']);
Route::get('users', [AuthController::class, 'users']);

// ==========================================
// 2. STOK AWAL / KUOTA KOMODITAS
// ==========================================
Route::get('stock-awal', [MasterStockController::class, 'getStockAwal']);
Route::post('stock-awal', [MasterStockController::class, 'setStockAwal']);

// ==========================================
// 3. STOK TERJUAL / PENJUALAN PER VARIAN
// ==========================================
Route::get('stock-terjual', [MasterStockController::class, 'getStockTerjual']);
Route::post('stock-terjual/reset', [MasterStockController::class, 'resetStockTerjual']);

// ==========================================
// 4. RINGKASAN STOK (Stok Awal vs Terjual vs Sisa)
// ==========================================
Route::get('stock-summary', [MasterStockController::class, 'stockSummary']);
Route::get('stock-groups', [MasterStockController::class, 'stockGroups']);
Route::get('stocks/groups', [MasterStockController::class, 'stockGroups']);
Route::get('stocks/categories', [MasterStockController::class, 'stockGroups']);

// ==========================================
// 4B. MONITORING STOK (HARIAN & BULANAN)
// ==========================================
Route::get('stock-monitoring', [StockMonitoringController::class, 'index']);
Route::get('monitoring-stock', [StockMonitoringController::class, 'index']);
Route::get('stock-monitoring/daily', [StockMonitoringController::class, 'daily']);
Route::get('stock-monitoring/monthly', [StockMonitoringController::class, 'monthly']);
Route::post('stock-monitoring/masuk', [StockMonitoringController::class, 'stockMasuk']);
Route::post('stock-masuk', [StockMonitoringController::class, 'stockMasuk']);
Route::post('stock-monitoring/sync', [StockMonitoringController::class, 'sync']);

// ==========================================
// 5. TRANSAKSI / KASIR CHECKOUT
// ==========================================
Route::prefix('transaksi')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/{id}', [TransactionController::class, 'show']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
});

// ==========================================
// 6. MASTER DATA & COMPATIBILITY ENDPOINTS
// ==========================================
Route::apiResource('products', ProductController::class);
Route::apiResource('master-stocks', MasterStockController::class);

Route::post('master-stocks/sync', [MasterStockController::class, 'sync']);
Route::post('products/sync', [MasterStockController::class, 'sync']);
Route::post('sync-stocks', [MasterStockController::class, 'sync']);
Route::post('sync-products', [MasterStockController::class, 'sync']);

Route::get('stocks', [MasterStockController::class, 'index']);
Route::post('stocks', [MasterStockController::class, 'updateStock']);
Route::get('stock', [MasterStockController::class, 'index']);
Route::post('stock', [MasterStockController::class, 'updateStock']);
Route::post('stocks/update', [MasterStockController::class, 'updateStock']);
Route::post('stock/update', [MasterStockController::class, 'updateStock']);
Route::post('update-stock', [MasterStockController::class, 'updateStock']);
Route::post('stock-in', [MasterStockController::class, 'updateStock']);

// ==========================================
// 7. MASTER DATA PELANGGAN (CUSTOMERS)
// ==========================================
Route::apiResource('customers', CustomerController::class);
Route::apiResource('pelanggan', CustomerController::class);

=======
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
use App\Http\Controllers\Api\V1\NotificationController;

// Auth routes (public)
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/verify', [AuthController::class, 'verifyToken']);
});

// Xendit webhook v1 (public, no auth)
Route::post('v1/webhook/xendit', [\App\Http\Controllers\Api\XenditWebhookController::class, 'handle']);

// IAK PPOB Webhook (public, no auth)
Route::post('v1/webhook/iak', [\App\Http\Controllers\Api\IakWebhookController::class, 'handle']);

// Protected v1 routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/check', [AuthController::class, 'checkToken']);
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

    // Notifications & FCM
    Route::post('/devices/fcm-token', [NotificationController::class, 'registerFcmToken']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // AI-STICH Placeholder
    Route::get('/ai/forecast', [AiController::class, 'forecast']);
});
>>>>>>> 13f94dc553435ee468ca1644484e7db79d112b6e
