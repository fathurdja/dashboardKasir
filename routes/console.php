<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rollover sisa stok akhir hari kemarin menjadi stok masuk awal hari ini setiap jam 12 malam
Schedule::command('stock:rollover')->dailyAt('00:00');

