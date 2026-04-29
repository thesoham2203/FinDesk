<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ClientIndex;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the client index component', function () {
    $client = Client::factory()->create(['name' => 'Acme Corp']);

    Livewire::test(ClientIndex::class)
        ->assertStatus(200)
        ->assertSee('Acme Corp');
});

it('can search for clients', function () {
    Client::factory()->create(['name' => 'Apple Inc']);
    Client::factory()->create(['name' => 'Microsoft Corp']);

    Livewire::test(ClientIndex::class)
        ->set('search', 'Apple')
        ->assertSee('Apple Inc')
        ->assertDontSee('Microsoft Corp');
});

it('can delete a client without invoices', function () {
    $client = Client::factory()->create();

    Livewire::test(ClientIndex::class)
        ->call('delete', $client->id)
        ->assertDispatched('flash', type: 'success');

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
});

it('cannot delete a client with existing invoices', function () {
    $client = Client::factory()->create();
    Invoice::factory()->create(['client_id' => $client->id]);

    Livewire::test(ClientIndex::class)
        ->call('delete', $client->id)
        ->assertDispatched('flash', type: 'error');

    $this->assertDatabaseHas('clients', ['id' => $client->id]);
});
