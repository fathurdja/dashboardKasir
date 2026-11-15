<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DailyStockSnapshot;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    /**
     * Daily stock report — opening vs closing for each product.
     */
    public function daily(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());

        // Try snapshots first
        $snapshots = DailyStockSnapshot::with('product:id,name')
            ->where('date', $date)
            ->get();

        if ($snapshots->isNotEmpty()) {
            $data = $snapshots->map(fn ($s) => [
                'product_id' => $s->product_id,
                'product_name' => $s->product?->name ?? 'Unknown',
                'opening_stock' => $s->opening_stock,
                'closing_stock' => $s->closing_stock,
                'sold_qty' => $s->sold_qty,
                'added_qty' => $s->added_qty,
                'bonus_qty' => $s->bonus_qty,
                'net_change' => $s->added_qty - $s->sold_qty - $s->bonus_qty,
            ]);

            return response()->json([
                'date' => $date,
                'source' => 'snapshot',
                'data' => $data,
            ]);
        }

        // Fallback: calculate live from stock_transactions
        $products = Product::where('is_active', true)
            ->withSum(['stockTransactions as stock_in' => fn($q) => $q->where('type', 'in')], 'quantity')
            ->withSum(['stockTransactions as stock_out' => fn($q) => $q->where('type', 'out')], 'quantity')
            ->withSum([
                'stockTransactions as sold_today' => fn($q) =>
                    $q->where('type', 'out')->whereDate('created_at', $date)
            ], 'quantity')
            ->withSum([
                'stockTransactions as added_today' => fn($q) =>
                    $q->where('type', 'in')->whereDate('created_at', $date)
            ], 'quantity')
            ->get();

        $data = $products->map(function ($product) {
            $currentStock = ($product->stock_in ?? 0) - ($product->stock_out ?? 0);
            $soldToday = $product->sold_today ?? 0;
            $addedToday = $product->added_today ?? 0;
            $openingStock = $currentStock + $soldToday - $addedToday;

            return [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'opening_stock' => max(0, $openingStock),
                'closing_stock' => max(0, $currentStock),
                'sold_qty' => $soldToday,
                'added_qty' => $addedToday,
                'bonus_qty' => 0,
                'net_change' => $addedToday - $soldToday,
            ];
        });

        return response()->json([
            'date' => $date,
            'source' => 'live',
            'data' => $data,
        ]);
    }
}
