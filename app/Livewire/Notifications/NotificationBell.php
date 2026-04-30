<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        return $user instanceof User ? $user->unreadNotifications()->count() : 0;
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-bell');
    }
}
