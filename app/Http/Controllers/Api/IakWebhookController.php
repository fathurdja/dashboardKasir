<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PpobTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IakWebhookController extends Controller
{
    /**
     * Handle incoming webhook from IAK
     */
    public function handle(Request $request)
    {
        // IAK sends JSON payload
        $payload = $request->all();
        
        Log::info('IAK Webhook Received:', $payload);

        // IAK structure typically puts data inside 'data' key for callback
        $data = $payload['data'] ?? $payload;

        if (!isset($data['ref_id'])) {
            return response()->json(['message' => 'Invalid payload, ref_id missing'], 400);
        }

        $transaction = PpobTransaction::where('ref_id', $data['ref_id'])->first();

        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // IAK status codes: 0 = Process/Pending, 1 = Success, 2 = Failed
        $iakStatus = $data['status'] ?? null;
        
        if ($iakStatus == 1) {
            $transaction->status = 'SUCCESS';
            $transaction->sn = $data['sn'] ?? null;
        } elseif ($iakStatus == 2) {
            $transaction->status = 'FAILED';
        }

        $transaction->iak_response = json_encode($payload);
        $transaction->save();

        return response()->json(['message' => 'Webhook processed successfully'], 200);
    }
}
