<?php

namespace App\Console\Commands;

use App\Models\StockMonitoring;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockMidnightRolloverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:rollover {date? : Target date (Y-m-d, default: today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollover stok akhir hari kemarin menjadi stok masuk awal hari ini (jam 12 malam)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = $this->argument('date') ?: date('Y-m-d');
        $this->info("Starting Midnight Stock Rollover for target date: {$targetDate}");

        $connections = ['mysql_v2', 'mysql'];

        foreach ($connections as $conn) {
            try {
                $this->info("Processing connection: {$conn}...");
                Config::set('database.default', $conn);
                DB::setDefaultConnection($conn);

                StockMonitoring::rollover($targetDate);

                $rows = StockMonitoring::whereDate('tanggal', $targetDate)->get();
                foreach ($rows as $r) {
                    $this->line(" - [{$conn}] {$r->kdklp}: Awal/Bawaan: {$r->stok_awal} | Tambahan: {$r->stok_tambahan} | Total Masuk: {$r->stock_masuk} | Terjual: {$r->stock_terjual} | Sisa: {$r->sisa_stock}");
                }
            } catch (\Throwable $e) {
                $this->error("Error on connection {$conn}: " . $e->getMessage());
                Log::error("Stock rollover error on {$conn}: " . $e->getMessage());
            }
        }

        $this->info("Stock Rollover finished successfully.");
        return 0;
    }
}
