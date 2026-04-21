<?php

declare(strict_types=1);

/**
 * NotificationBell Livewire Component
 *
 * WHAT: Lightweight notification bell icon in the header showing unread notification count
 *       as a badge. Located in the navigation bar on every page.
 *
 * WHY: Users need quick access to unread notifications. A header bell is the standard UX
 *      pattern. wire:poll refreshes the unread count every 60 seconds.
 *
 * IMPLEMENT: Use #[Computed] for unread count (automatically cached per component instance).
 *            Minimal DOM with wire:poll for efficiency. Badge hidden when count is 0.
 *
 * REFERENCE:
 * - Polling: https://livewire.laravel.com/docs/polling
 * - Computed Properties: https://livewire.laravel.com/docs/computed-properties
 * - Database Notifications: https://laravel.com/docs/13.x/notifications#database-notifications
 */

namespace App\Livewire\Notifications;

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

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
