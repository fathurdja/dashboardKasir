<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    /**
     * Placeholder endpoint for AI-STICH forecasting.
     */
    public function forecast(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'AI-STICH Forecast placeholder',
            'data' => [
                'recommendation' => 'Fitur AI belum diimplementasikan.',
            ]
        ]);
    }
}
