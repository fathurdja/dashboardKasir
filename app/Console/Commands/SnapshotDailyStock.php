<?php

namespace App\Console\Commands;

use App\Models\DailyStockSnapshot;
use App\Models\Product;
use Illuminate\Console\Command;

class SnapshotDailyStock extends Command
{
    protected $signature = 'app:snapshot-daily-stock {--date= : Date to snapshot (default: today)}';

    protected $description = 'Record opening stock snapshot for all active products';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->toDateString();

        $this->info("Creating stock snapshot for {$date}...");

        $products = Product::where('is_active', true)
            ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->get();

        $created = 0;

        foreach ($products as $product) {
            $currentStock = ($product->stock_in ?? 0) - ($product->stock_out ?? 0);

            DailyStockSnapshot::updateOrCreate(
                ['product_id' => $product->id, 'date' => $date],
                ['opening_stock' => max(0, $currentStock), 'closing_stock' => max(0, $currentStock)]
            );

            $created++;
        }

        $this->info("Snapshot created for {$created} products.");

        return self::SUCCESS;
    }
}
