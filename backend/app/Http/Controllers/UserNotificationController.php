<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserNotificationController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $notifications = UserNotification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (UserNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'unread' => $notifications->where('read', false)->count(),
            'data' => $notifications,
        ]);
    }

    public function markAsRead(UserNotification $notification): \Illuminate\Http\JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            abort(404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function markAllAsRead(Request $request): \Illuminate\Http\JsonResponse
    {
        UserNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}
