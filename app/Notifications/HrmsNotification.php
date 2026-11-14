<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class HrmsNotification extends Notification
{
    public ?string $url;

    public function __construct(
        public string $title,
        public string $message,
        ?string $url = null,
        public string $type = 'info',
        public ?string $dedupeKey = null,
        public string $from = 'System',

        // Deprecated: ignored. Kept only so existing calls that still
        // pass this argument don't break.
        public ?array $targetRoles = null,
    ) {
        // Always store internal links as "/path?query" so they open on
        // whichever domain the user is on (local or hosting).
        $this->url = self::toRelativeUrl($url);
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title'      => $this->title,
            'message'    => $this->message,
            'url'        => $this->url,
            'type'       => $this->type,
            'dedupe_key' => $this->dedupeKey,
            'from'       => $this->from,
        ];
    }

    /**
     * Convert a local/own-domain URL into a root-relative path.
     *
     *  http://localhost/payroll?x=1  ->  /payroll?x=1
     *  https://mysite.com/payroll    ->  /payroll
     *  payroll                       ->  /payroll
     *  https://other-site.com/page   ->  unchanged (truly external)
     */
    public static function toRelativeUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if ($host !== '') {
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

            $isInternal = in_array($host, ['localhost', '127.0.0.1', '::1'], true)
                || str_ends_with($host, '.test')
                || str_ends_with($host, '.local')
                || ($appHost !== '' && $host === $appHost)
                || (! app()->runningInConsole() && $host === strtolower(request()->getHost()));

            if (! $isInternal) {
                return $url; // truly external link, keep as is
            }
        }

        $path = '/' . ltrim($parts['path'] ?? '', '/');

        return $path
            . (isset($parts['query']) ? '?' . $parts['query'] : '')
            . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    /**
     * Send notification only once while an unread copy exists.
     */
    public static function notifyOnce($notifiable, self $notification): void
    {
        if ($notification->dedupeKey) {
            $exists = $notifiable->unreadNotifications()
                ->whereNull('archived_at')
                ->where('type', self::class)
                ->where('data->dedupe_key', $notification->dedupeKey)
                ->exists();

            if ($exists) {
                return;
            }
        }

        $notifiable->notify($notification);
    }
}