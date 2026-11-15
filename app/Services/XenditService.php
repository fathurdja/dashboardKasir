<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class XenditService
{
    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = env('XENDIT_SECRET_KEY', '');
    }

    public function createInvoice(Order $order)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => (string) $order->id,
                    'amount' => (float) $order->total_price,
                    'description' => 'Payment for Order #' . $order->receipt_number,
                    'success_redirect_url' => url('/admin/orders/' . $order->id),
                    'failure_redirect_url' => url('/admin/orders'),
                ]);

            if ($response->failed()) {
                \Illuminate\Support\Facades\Log::error('Xendit Error: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Xendit Request Failed: ' . $e->getMessage());
            return [];
        }
    }
}
