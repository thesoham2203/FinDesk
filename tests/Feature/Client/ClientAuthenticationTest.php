<?php

declare(strict_types=1);

namespace Tests\Feature\Client;

use App\Livewire\Actions\ClientLogout;
use App\Models\Client;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('clients can logout through the navigation component', function (): void {
    $client = Client::factory()->create([
        'remember_token' => Str::random(10),
    ]);

    $this->actingAs($client, 'client');

    $component = Volt::test('client.layout.navigation');

    $component->call('logout');

    $component
        ->assertHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest('client');
});

test('ClientLogout action logs out the client directly', function (): void {
    $client = Client::factory()->create([
        'remember_token' => Str::random(10),
    ]);

    $this->actingAs($client, 'client');
    $this->assertAuthenticatedAs($client, 'client');

    $action = new ClientLogout();
    $action();

    $this->assertGuest('client');
});

test('clients can authenticate using the client login screen', function (): void {
    $client = Client::factory()->create();

    Volt::test('pages.auth.client-login')
        ->set('form.email', $client->email)
        ->set('form.password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('client.dashboard', absolute: false));

    $this->assertAuthenticatedAs($client, 'client');
});

test('clients cannot authenticate with an invalid password', function (): void {
    $client = Client::factory()->create();

    Volt::test('pages.auth.client-login')
        ->set('form.email', $client->email)
        ->set('form.password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('form.email')
        ->assertNoRedirect();

    $this->assertGuest('client');
});

test('client login requests are rate limited', function (): void {
    $client = Client::factory()->create();

    $component = Volt::test('pages.auth.client-login')
        ->set('form.email', $client->email)
        ->set('form.password', 'wrong-password');

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $component->call('login');
    }

    $component
        ->assertHasErrors('form.email')
        ->assertNoRedirect();
});
