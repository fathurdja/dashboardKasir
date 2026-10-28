<?php

use App\Http\Controllers\ApiV2\AuthController;
use App\Http\Controllers\ApiV2\CustomerController;
use App\Http\Controllers\ApiV2\MasterStockController;
use App\Http\Controllers\ApiV2\ProductController;
use App\Http\Controllers\ApiV2\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V2 Routes - Boneva Kasir (dashboardLO)
|--------------------------------------------------------------------------
| Base URL: https://laravel.fathurrzqn8n.web.id/api-v2
| Connected to: Database sql_dashboardlo_v2
|
| 1. AUTENTIKASI & MULTI-USER:
|    - POST /api-v2/login
|    - POST /api-v2/logout
|    - GET  /api-v2/me
|    - GET  /api-v2/users (Admin only)
|
| 2. STOK AWAL (KUOTA KOMODITAS):
|    - GET  /api-v2/stock-awal
|    - POST /api-v2/stock-awal (Admin only)
|
| 3. STOK TERJUAL (PENJUALAN PER VARIAN):
|    - GET  /api-v2/stock-terjual
|    - POST /api-v2/stock-terjual/reset (Admin only)
|
| 4. RINGKASAN STOK (Stok Awal, Terjual, Sisa):
|    - GET  /api-v2/stock-summary
|    - GET  /api-v2/stock-groups
|
| 5. TRANSAKSI (CHECKOUT KASIR):
|    - GET    /api-v2/transaksi
|    - POST   /api-v2/transaksi
|    - GET    /api-v2/transaksi/{id}
|    - PUT    /api-v2/transaksi/{id}
|    - DELETE /api-v2/transaksi/{id}
|
| 6. PRODUK & MASTER STOCKS:
|    - GET  /api-v2/products
|    - GET  /api-v2/master-stocks
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

