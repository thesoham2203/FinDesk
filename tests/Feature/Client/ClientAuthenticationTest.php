<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Livewire\Actions\ClientLogout;
use App\Models\Client;
use Livewire\Volt\Volt;

test('clients can logout through the navigation component', function (): void {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client');

    $component = Volt::test('client.layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest('client');
});

test('ClientLogout action logs out the client directly', function (): void {
    $client = Client::factory()->create();

    $this->actingAs($client, 'client');
    $this->assertAuthenticatedAs($client, 'client');

    $action = new ClientLogout();
    $action();

    $this->assertGuest('client');
});
