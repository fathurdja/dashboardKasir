<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::with('variants')->where('is_active', true)->get();
        if ($products->isEmpty()) return;

        $statuses = ['pending', 'completed', 'canceled'];
        $orderTypes = ['dine-in', 'take-away'];
        $paymentMethods = ['Cash', 'Qris', 'Debit'];

        // Temporarily unguard to allow mass assignment of created_at
        Order::unguard();
        OrderItem::unguard();
        StockTransaction::unguard();

        // 1. Create 20 random orders for the past 30 days (relative to 17 March 2026)
        for ($i = 0; $i < 20; $i++) {
            $status = $statuses[array_rand($statuses)];
            $orderDate = Carbon::create(2026, 3, 17)->subDays(rand(1, 30))->subHours(rand(0, 23));

            $this->createDummyOrder($status, $orderDate, $products, $orderTypes, $paymentMethods);
        }

        // 2. Create 15 orders specifically for 17 March 2026 to populate Dashboard charts
        for ($i = 0; $i < 15; $i++) {
            $status = 'completed'; // Force completed for sales charts
            $orderDate = Carbon::create(2026, 3, 17, rand(8, 22), rand(0, 59), 0);

            $this->createDummyOrder($status, $orderDate, $products, $orderTypes, $paymentMethods);
        }

        // 3. Force one product to have Low Stock (<= 5) for the LowStockWidget
        $lowStockProduct = $products->first();
        if ($lowStockProduct) {
            $in = StockTransaction::where('product_id', $lowStockProduct->id)->where('type', 'in')->sum('quantity');
            $out = StockTransaction::where('product_id', $lowStockProduct->id)->where('type', 'out')->sum('quantity');
            $currentStock = $in - $out;
            
            if ($currentStock > 3) {
                StockTransaction::create([
                    'product_id' => $lowStockProduct->id,
                    'type' => 'out',
                    'quantity' => $currentStock - 3,
                    'reference_id' => null,
                    'notes' => 'Adjustment to trigger low stock warning on dashboard',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        Order::reguard();
        OrderItem::reguard();
        StockTransaction::reguard();
    }

    private function createDummyOrder($status, $orderDate, $products, $orderTypes, $paymentMethods)
    {
        $order = Order::create([
            'receipt_number' => 'ORD-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'status' => $status,
            'order_type' => $orderTypes[array_rand($orderTypes)],
            'total_price' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'payment_method' => $paymentMethods[array_rand($paymentMethods)],
            'created_at' => $orderDate,
            'updated_at' => $orderDate,
        ]);

        $numItems = rand(1, 4);
        $totalPrice = 0;

        for ($j = 0; $j < $numItems; $j++) {
            $product = $products->random();
            $quantity = rand(1, 3);
            $unitPrice = $product->price;

            $variant = $product->variants->isNotEmpty() ? $product->variants->random() : null;
            $variantId = $variant ? $variant->id : null;
            if ($variant) {
                $unitPrice += $variant->additional_price;
            }

            $subtotal = $unitPrice * $quantity;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            $totalPrice += $subtotal;

            // If completed, register stock out
            if ($status === 'completed') {
                StockTransaction::create([
                    'product_id' => $product->id,
                    'variant_id' => $variantId,
                    'type' => 'out',
                    'quantity' => $quantity,
                    'reference_id' => $order->id,
                    'notes' => 'Sale from Order ' . $order->receipt_number,
                    'created_at' => $orderDate,
                    'updated_at' => $orderDate,
                ]);
            }
        }

        $tax = $totalPrice * 0.11;

        $order->update([
            'total_price' => $totalPrice + $tax,
            'tax_amount' => $tax,
        ]);
    }
}
