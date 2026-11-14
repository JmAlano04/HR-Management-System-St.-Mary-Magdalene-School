<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NotificationController extends Controller
{
    /**
     * Full notification page.
     */
    public function index(Request $request)
    {
        $notifications = $this->visibleNotifications($request->user())
            ->latest()
            ->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Small JSON feed for notification bell.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $this->visibleNotifications($user)
            ->whereNull('archived_at')
            ->latest()
            ->limit(10)
            ->get();

        $unreadCount = $this->visibleNotifications($user)
            ->whereNull('archived_at')
            ->whereNull('read_at')
            ->count();

        return $this->json([
            'unread' => $unreadCount,
            'items'  => $notifications
                ->map(fn ($n) => $this->toItem($n))
                ->values(),
        ]);
    }

    /**
     * Paginated notification listing.
     */
    public function listing(Request $request): JsonResponse
    {
        $data = $request->validate([
            'archived' => ['sometimes', 'boolean'],
            'page'     => ['sometimes', 'integer', 'min:1'],
        ]);

        $archived = $request->boolean('archived');
        $page = (int) ($data['page'] ?? 1);

        $query = $this->visibleNotifications($request->user());

        if ($archived) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        $notifications = $query->latest()
            ->paginate(30, ['*'], 'page', $page);

        return $this->json([
            'items' => $notifications->getCollection()
                ->map(fn ($n) => $this->toItem($n))
                ->values(),

            'has_more' => $notifications->hasMorePages(),
        ]);
    }

    /**
     * Mark one notification as read.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $this->visibleNotifications($request->user())
            ->whereNull('archived_at')
            ->whereKey($id)
            ->first()
            ?->markAsRead();

        return $this->json(['ok' => true]);
    }

    /**
     * Mark all unread notifications as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        $this->visibleNotifications($request->user())
            ->whereNull('archived_at')
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return $this->json(['ok' => true]);
    }

    /**
     * Mark selected notifications as read.
     */
    public function markSelectedRead(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);

        $this->visibleNotifications($request->user())
            ->whereNull('archived_at')
            ->whereNull('read_at')
            ->whereIn('id', $ids)
            ->update([
                'read_at' => now(),
            ]);

        return $this->json(['ok' => true]);
    }

    /**
     * Archive selected notifications.
     */
    public function archiveSelected(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);

        $this->visibleNotifications($request->user())
            ->whereNull('archived_at')
            ->whereIn('id', $ids)
            ->update([
                'archived_at' => now(),
            ]);

        return $this->json(['ok' => true]);
    }

    /**
     * Restore selected archived notifications.
     */
    public function restoreSelected(Request $request): JsonResponse
    {
        $ids = $this->validatedIds($request);

        $this->visibleNotifications($request->user())
            ->whereNotNull('archived_at')
            ->whereIn('id', $ids)
            ->update([
                'archived_at' => null,
            ]);

        return $this->json(['ok' => true]);
    }

    // ==========================================================
    // NOTIFICATION QUERY
    // ==========================================================

    /**
     * All notifications of the logged-in user (no role restrictions).
     */
    private function visibleNotifications($user): MorphMany
    {
        return $user->notifications();
    }

    // ==========================================================
    // HELPERS
    // ==========================================================

    private function validatedIds(Request $request): array
    {
        return $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'uuid'],
        ])['ids'];
    }

    private function json(array $payload): JsonResponse
    {
        return response()
            ->json($payload)
            ->header('Cache-Control', 'no-store, private');
    }

    private function toItem($notification): array
    {
        return [
            'id'       => $notification->id,
            'from'     => $notification->data['from'] ?? 'System',
            'title'    => $notification->data['title'] ?? '',
            'message'  => $notification->data['message'] ?? '',
            'url'      => method_exists(\App\Notifications\HrmsNotification::class, 'toRelativeUrl')
                ? \App\Notifications\HrmsNotification::toRelativeUrl($notification->data['url'] ?? null)
                : $this->toRelativeUrl($notification->data['url'] ?? null),
            'type'     => $notification->data['type'] ?? 'info',
            'read'     => $notification->read_at !== null,
            'archived' => $notification->archived_at !== null,
            'time'     => $notification->created_at->diffForHumans(),
            'sent'     => $notification->created_at->format('M j, Y g:i A'),
        ];
    }

    private function toRelativeUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (!str_contains($url, '://') && !str_starts_with($url, '//')) {
            return ltrim($url, '/');
        }

        $parts = parse_url($url);

        if (!isset($parts['path'])) {
            return null;
        }

        return ltrim($parts['path'], '/');
    }
}