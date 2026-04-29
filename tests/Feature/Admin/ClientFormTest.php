<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Admin\ClientForm;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($this->admin);
});

it('renders the client form component', function (): void {
    Livewire::test(ClientForm::class)
        ->assertStatus(200);
});

it('can create a client', function (): void {
    Livewire::test(ClientForm::class)
        ->set('name', 'Acme Corp')
        ->set('email', 'contact@acme.com')
        ->set('phone', '1234567890')
        ->set('address', '123 Business Way')
        ->set('taxNumber', 'TAX-123')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.clients.index'));

    $this->assertDatabaseHas('clients', [
        'name' => 'Acme Corp',
        'email' => 'contact@acme.com',
    ]);
});

it('can update a client', function (): void {
    $client = Client::factory()->create();

    Livewire::test(ClientForm::class, ['client' => $client])
        ->set('name', 'Updated Client Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.clients.index'));

    $client->refresh();
    expect($client->name)->toBe('Updated Client Name');
});

it('validates required fields', function (): void {
    Livewire::test(ClientForm::class)
        ->set('name', '')
        ->set('email', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required', 'email' => 'required']);
});
