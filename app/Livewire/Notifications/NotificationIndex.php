<?php

declare(strict_types=1);

/**
 * NotificationIndex Livewire Component
 *
 * WHAT: Full page displaying all notifications (read and unread) with pagination.
 *       Users can mark individual notifications as read or mark all as read at once.
 *
 * WHY: Users need to review past notifications and manage their status. This is the
 *      notification inbox page accessible via the notification bell.
 *
 * IMPLEMENT: Paginated list of notifications with read/unread styling. Actions to mark
 *            as read (individually or all). Link to notification action_url if available.
 *
 * REFERENCE:
 * - Pagination: https://livewire.laravel.com/docs/pagination
 * - Database Notifications: https://laravel.com/docs/13.x/notifications#database-notifications
 */

namespace App\Livewire\Notifications;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

final class NotificationIndex extends Component
{
    use WithPagination;

    /**
     * Get paginated list of all notifications for the current user.
     */
    #[Computed]
    public function notifications()
    {
        return auth()->user()->notifications()->latest()->paginate(20);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        // Redirect to action URL if available
        if (isset($notification->data['action_url'])) {
            $this->redirect($notification->data['action_url'], navigate: true);
        } else {
            $this->dispatch('flash', type: 'success', message: 'Notification marked as read.');
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('flash', type: 'success', message: 'All notifications marked as read.');
    }

    public function render()
    {
        return view('livewire.notifications.notification-index');
    }
}
