<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class NotificationBell extends Component
{
    /**
     * Get the count of unread notifications for the current user.
     */
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-bell');
    }
}
