<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderPrintController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/orders/{order}/print', OrderPrintController::class)->name('orders.print');
