<?php

namespace App\Http\Controllers;

use App\Services\AiPaymentInsightService;
use Illuminate\Support\Facades\Auth;

class AiInsightController extends Controller
{
    public function paymentInsight(AiPaymentInsightService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'data' => $service->forSantri(Auth::user()),
        ]);
    }
}
