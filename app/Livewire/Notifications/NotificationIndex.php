<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/**
 * @property-read LengthAwarePaginator<int, DatabaseNotification> $notifications
 */
final class NotificationIndex extends Component
{
    use WithPagination;

    /**
     * Get paginated list of all notifications for the current user.
     *
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        $user = auth()->user();
        throw_unless($user instanceof User, RuntimeException::class, 'User must be logged in.');

        /** @var LengthAwarePaginator<int, DatabaseNotification> $paginator */
        $paginator = $user->notifications()->latest()->paginate(20);

        return $paginator;
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $notificationId): void
    {
        $user = auth()->user();
        throw_unless($user instanceof User, RuntimeException::class, 'User must be logged in.');

        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        /** @var array<string, mixed> $data */
        $data = $notification->data;

        // Redirect to action URL if available
        if (isset($data['action_url']) && is_string($data['action_url'])) {
            $this->redirect($data['action_url'], navigate: true);
        } else {
            $this->dispatch('flash', type: 'success', message: 'Notification marked as read.');
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): void
    {
        $user = auth()->user();
        throw_unless($user instanceof User, RuntimeException::class, 'User must be logged in.');

        $user->unreadNotifications()->update(['read_at' => now()]);

        $this->dispatch('flash', type: 'success', message: 'All notifications marked as read.');
    }

    public function render(): View
    {
        return view('livewire.notifications.notification-index', [
            'notifications' => $this->notifications(),
        ]);
    }
}
