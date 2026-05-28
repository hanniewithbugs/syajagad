<?php

namespace App\Http\Controllers;

use App\Services\LocalChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LocalChatbotController extends Controller
{
    public function quick(Request $request, LocalChatbotService $chatbot): JsonResponse
    {
        $validated = $request->validate([
            'intent' => ['required', 'string', Rule::in(LocalChatbotService::INTENTS)],
        ]);

        return response()->json([
            'data' => $chatbot->answer(Auth::user(), $validated['intent']),
        ]);
    }
}
