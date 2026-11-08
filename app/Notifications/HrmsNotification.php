<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * One generic in-app (database) notification.
 * $type only drives the colour dot: info | success | warning | danger
 */
class HrmsNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $type = 'info',
        public ?string $dedupeKey = null,
<<<<<<< HEAD
        public string $from = 'System',
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
    ) {
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
<<<<<<< HEAD
            'from'       => $this->from,
=======
>>>>>>> e05b184454bbf6ba7bf5b37b088389827694a120
        ];
    }
}