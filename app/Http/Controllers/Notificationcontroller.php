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
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
        ]);
    }

    /** Small JSON feed for the bell dropdown (polled every 5s while visible). */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->whereNull('archived_at')
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'unread' => $user->unreadNotifications()->whereNull('archived_at')->count(),
            'items'  => $notifications->map(fn ($notification) => $this->toItem($notification)),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function listing(Request $request): JsonResponse
    {
        $data = $request->validate([
            'archived' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $archived = (bool) ($data['archived'] ?? false);
        $page = (int) ($data['page'] ?? 1);
        $query = $request->user()->notifications();
        $archived
            ? $query->whereNotNull('archived_at')
            : $query->whereNull('archived_at');
        $notifications = $query->latest()->paginate(30, ['*'], 'page', $page);

        return response()->json([
            'items' => $notifications->getCollection()->map(fn ($notification) => $this->toItem($notification)),
            'has_more' => $notifications->hasMorePages(),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereNull('archived_at')->whereKey($id)->first()?->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->whereNull('archived_at')->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markSelectedRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ]);

        $request->user()->unreadNotifications()
            ->whereNull('archived_at')
            ->whereIn('id', $data['ids'])
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function archiveSelected(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ]);

        $request->user()->notifications()
            ->whereNull('archived_at')
            ->whereIn('id', $data['ids'])
            ->update(['archived_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function restoreSelected(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ]);

        $request->user()->notifications()
            ->whereNotNull('archived_at')
            ->whereIn('id', $data['ids'])
            ->update(['archived_at' => null]);

        return response()->json(['ok' => true]);
    }

    private function toItem($notification): array
    {
        return [
            'id' => $notification->id,
            'from' => $notification->data['from'] ?? 'System',
            'title' => $notification->data['title'] ?? '',
            'message' => $notification->data['message'] ?? '',
            'url' => $notification->data['url'] ?? null,
            'type' => $notification->data['type'] ?? 'info',
            'read' => $notification->read_at !== null,
            'time' => $notification->created_at->diffForHumans(),
            'sent' => $notification->created_at->format('M j, Y g:i A'),
        ];
    }
}