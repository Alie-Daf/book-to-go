<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

// One simple in-app notice: a message and an optional link.  Use: $user->notify(new AppNotice('Hello', '/path'))
class AppNotice extends Notification
{
    public function __construct(private string $message, private ?string $url = null) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array { return ['message' => $this->message, 'url' => $this->url]; }
}