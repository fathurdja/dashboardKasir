<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StockTransaction;
use App\Models\XenditPayment;
use App\Services\XenditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrisPaymentController extends Controller
{
    /**
     * Create a QRIS invoice via Xendit.
     */
    public function create(Request $request, XenditService $xenditService): JsonResponse
    {
        $validated = $request->validate([
            'receipt_number' => 'required|string|exists:orders,receipt_number',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string',
        ]);

        $order = Order::where('receipt_number', $validated['receipt_number'])->firstOrFail();

        // Check if there's already a pending payment for this order
        $existingPayment = XenditPayment::where('order_id', $order->id)
            ->where('status', 'PENDING')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'xendit_invoice_id' => $existingPayment->xendit_invoice_id,
                'qr_string' => $existingPayment->qr_string,
                'invoice_url' => $existingPayment->xendit_invoice_url,
                'expires_at' => $existingPayment->expires_at?->toIso8601String(),
                'status' => $existingPayment->status,
            ]);
        }

        try {
            $result = $xenditService->createInvoice($order);

            if (empty($result) || !isset($result['id'])) {
                return response()->json([
                    'message' => 'Failed to create Xendit invoice',
                ], 500);
            }

            // Save payment record
            $payment = XenditPayment::create([
                'order_id' => $order->id,
                'xendit_invoice_id' => $result['id'],
                'xendit_invoice_url' => $result['invoice_url'] ?? null,
                'qr_string' => $result['qr_string'] ?? null,
                'amount' => $validated['amount'],
                'status' => 'PENDING',
                'expires_at' => isset($result['expiry_date']) ? now()->parse($result['expiry_date']) : now()->addMinutes(5),
                'xendit_response' => $result,
            ]);

            // Update order with Xendit info
            $order->update([
                'xendit_external_id' => $result['id'],
                'xendit_invoice_url' => $result['invoice_url'] ?? null,
                'xendit_status' => 'PENDING',
            ]);

            return response()->json([
                'xendit_invoice_id' => $payment->xendit_invoice_id,
                'qr_string' => $payment->qr_string,
                'invoice_url' => $payment->xendit_invoice_url,
                'expires_at' => $payment->expires_at?->toIso8601String(),
                'status' => $payment->status,
            ]);

        } catch (\Exception $e) {
            Log::error('QRIS payment creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create QRIS payment',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Check QRIS payment status by receipt number.
     */
    public function status(string $receiptNumber): JsonResponse
    {
        $order = Order::where('receipt_number', $receiptNumber)->firstOrFail();

        $payment = XenditPayment::where('order_id', $order->id)
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'No payment found for this receipt',
            ], 404);
        }

        return response()->json([
            'receipt_number' => $receiptNumber,
            'xendit_invoice_id' => $payment->xendit_invoice_id,
            'status' => $payment->status,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'expires_at' => $payment->expires_at?->toIso8601String(),
            'amount' => round((float) $payment->amount, 2),
        ]);
    }
}
