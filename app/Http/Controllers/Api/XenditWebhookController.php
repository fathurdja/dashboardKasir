<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\Log;

class XenditWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        Log::info('Xendit Webhook Received', $data);

        // Verification token check (optional but recommended in production)
        $callbackToken = env('XENDIT_CALLBACK_TOKEN');
        if ($callbackToken && $request->header('x-callback-token') !== $callbackToken) {
            return response()->json(['message' => 'Invalid token'], 403);
        }

        if (isset($data['status']) && $data['status'] === 'PAID') {
            $order = Order::where('id', $data['external_id'])->first();

            if ($order && $order->status !== 'completed') {
                $order->update([
                    'status' => 'completed',
                    'xendit_status' => 'PAID',
                ]);

                // Reduce stock
                foreach ($order->items as $item) {
                    StockTransaction::create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'type' => 'out',
                        'quantity' => $item->quantity,
                        'reference_id' => $order->id,
                        'notes' => 'Penjualan (Xendit): ' . $order->receipt_number,
                    ]);
                }
            }
        } elseif (isset($data['status']) && in_array($data['status'], ['EXPIRED', 'SETTLED'])) {
            $order = Order::where('id', $data['external_id'])->first();
            
            if ($order) {
                // Update specific status accordingly 
                // e.g., if expired, we might mark order as canceled
                if ($data['status'] === 'EXPIRED' && $order->status === 'pending') {
                    $order->update([
                        'status' => 'canceled',
                        'xendit_status' => 'EXPIRED',
                    ]);
                } else {
                    $order->update([
                        'xendit_status' => $data['status'],
                    ]);
                }
            }
        }

        return response()->json(['message' => 'success']);
    }
}
