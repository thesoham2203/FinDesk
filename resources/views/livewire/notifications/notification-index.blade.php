<?php

use Livewire\Volt\Component;

new class extends Component { };

?>

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
        @if (auth()->user()->unreadNotifications()->count() > 0)
            <button wire:click="markAllAsRead"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                Mark All as Read
            </button>
        @endif
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
        @forelse ($this->notifications as $notification)
            <div
                class="bg-white rounded-lg shadow p-6 transition-colors {{ $notification->read_at ? 'bg-gray-50' : 'bg-blue-50 border-l-4 border-blue-600' }}">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Notification Title -->
                        <h3 class="text-lg font-semibold {{ $notification->read_at ? 'text-gray-900' : 'text-blue-900' }}">
                            {{ $notification->data['title'] }}
                        </h3>

                        <!-- Notification Message -->
                        <p class="mt-2 text-gray-700">
                            {{ $notification->data['message'] }}
                        </p>

                        <!-- Timestamp -->
                        <p class="mt-2 text-sm text-gray-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="ml-4 flex flex-col items-end gap-2">
                        @if (!$notification->read_at)
                            <button wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Mark as Read
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Action Link (if available) -->
                @if (isset($notification->data['action_url']))
                    <div class="mt-4">
                        <a href="{{ $notification->data['action_url'] }}" wire:navigate
                            class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View Details
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </path>
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">No notifications</h3>
                <p class="mt-1 text-gray-500">You're all caught up! Check back later.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if ($this->notifications->hasPages())
        <div class="mt-8">
            {{ $this->notifications->links() }}
        </div>
    @endif
</div>