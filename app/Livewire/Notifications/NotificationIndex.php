<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
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
    public function notifications(): LengthAwarePaginator
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

    public function render(): View
    {
        return view('livewire.notifications.notification-index');
    }
}
