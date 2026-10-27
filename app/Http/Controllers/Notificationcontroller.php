<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Full page: all notifications of the logged-in user. */
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /** Small JSON feed for the bell dropdown (polled every 30s). */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items'  => $user->notifications()->limit(10)->get()->map(fn ($n) => [
                'id'      => $n->id,
                'title'   => $n->data['title'] ?? '',
                'message' => $n->data['message'] ?? '',
                'url'     => $n->data['url'] ?? null,
                'type'    => $n->data['type'] ?? 'info',
                'read'    => $n->read_at !== null,
                'time'    => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}