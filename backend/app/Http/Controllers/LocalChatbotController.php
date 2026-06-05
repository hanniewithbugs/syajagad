<?php

namespace App\Http\Controllers;

use App\Services\LocalChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocalChatbotController extends Controller
{
    public function quick(Request $request, LocalChatbotService $chatbot): JsonResponse
    {
        $validated = $request->validate([
            'intent' => ['nullable', 'string'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json([
            'data' => $chatbot->answer(Auth::user(), $validated['intent'] ?? null, $validated['message'] ?? null),
        ]);
    }
}
