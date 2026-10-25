<?php

use App\Http\Controllers\Api\MasterStockController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::post('master-stocks/sync', [MasterStockController::class, 'sync']);
Route::post('products/sync', [MasterStockController::class, 'sync']);
Route::post('sync-stocks', [MasterStockController::class, 'sync']);
Route::post('sync-products', [MasterStockController::class, 'sync']);

Route::get('stock-groups', [MasterStockController::class, 'stockGroups']);
Route::get('stocks/groups', [MasterStockController::class, 'stockGroups']);
Route::get('stocks/categories', [MasterStockController::class, 'stockGroups']);

Route::get('stocks', [MasterStockController::class, 'index']);
Route::post('stocks', [MasterStockController::class, 'updateStock']);
Route::get('stock', [MasterStockController::class, 'index']);
Route::post('stock', [MasterStockController::class, 'updateStock']);
Route::post('stocks/update', [MasterStockController::class, 'updateStock']);
Route::post('stock/update', [MasterStockController::class, 'updateStock']);
Route::post('update-stock', [MasterStockController::class, 'updateStock']);
Route::post('stock-in', [MasterStockController::class, 'updateStock']);

Route::apiResource('products', ProductController::class);

Route::prefix('transaksi')->group(function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::get('/{id}', [TransactionController::class, 'show']);
    Route::post('/', [TransactionController::class, 'store']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'destroy']);
});
Route::apiResource('master-stocks', MasterStockController::class);
