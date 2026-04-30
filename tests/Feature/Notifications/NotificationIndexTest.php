<?php

declare(strict_types=1);

use App\Livewire\Notifications\NotificationIndex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

final class NotificationIndexTestNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        $payload = [
            'title' => $this->title,
            'message' => $this->message,
        ];

        if ($this->actionUrl !== null) {
            $payload['action_url'] = $this->actionUrl;
        }

        return $payload;
    }
}

test('notification index renders unread notifications', function (): void {
    $user = User::factory()->create();
    $user->notify(new NotificationIndexTestNotification('Unread notice', 'Needs attention'));

    Livewire::actingAs($user)
        ->test(NotificationIndex::class)
        ->assertStatus(200)
        ->assertSee('Unread notice')
        ->assertSee('Mark All as Read');
});

test('marking a notification with an action url redirects to the action', function (): void {
    $user = User::factory()->create();
    $user->notify(new NotificationIndexTestNotification(
        'Open invoice',
        'Invoice ready',
        route('dashboard', absolute: false),
    ));

    $notification = $user->notifications()->firstOrFail();

    Livewire::actingAs($user)
        ->test(NotificationIndex::class)
        ->call('markAsRead', $notification->id)
        ->assertRedirect(route('dashboard', absolute: false));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('marking a notification without an action url flashes a message', function (): void {
    $user = User::factory()->create();
    $user->notify(new NotificationIndexTestNotification('Reminder', 'Check the portal'));

    $notification = $user->notifications()->firstOrFail();

    Livewire::actingAs($user)
        ->test(NotificationIndex::class)
        ->call('markAsRead', $notification->id)
        ->assertDispatched('flash', type: 'success');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('mark all as read updates every unread notification', function (): void {
    $user = User::factory()->create();
    $user->notify(new NotificationIndexTestNotification('One', 'First'));
    $user->notify(new NotificationIndexTestNotification('Two', 'Second'));

    Livewire::actingAs($user)
        ->test(NotificationIndex::class)
        ->call('markAllAsRead')
        ->assertDispatched('flash', type: 'success');

    expect($user->fresh()->unreadNotifications()->count())->toBe(0);
});
