<?php

declare(strict_types=1);

use App\Livewire\Admin\ClientForm;
use App\Models\Client;
use Livewire\Livewire;

it('populates form fields when editing a client', function (): void {
    $client = Client::factory()->create([
        'name' => 'Acme Corp',
        'email' => 'acme@example.com',
        'phone' => '9876543210',
        'address' => 'Mumbai',
        'tax_number' => 'GST123',
        'notes' => 'Important client',
    ]);

    $component = Livewire::test(ClientForm::class, [
        'client' => $client,
    ]);

    expect($component->get('clientId'))->toBe($client->id)
        ->and($component->get('name'))->toBe('Acme Corp')
        ->and($component->get('email'))->toBe('acme@example.com')
        ->and($component->get('phone'))->toBe('9876543210')
        ->and($component->get('address'))->toBe('Mumbai')
        ->and($component->get('taxNumber'))->toBe('GST123')
        ->and($component->get('notes'))->toBe('Important client');
});

it('creates a new client when clientId is null', function (): void {
    Client::creating(function (Client $client) {
        $client->password = $client->password ?? bcrypt('password');
    });

    $component = Livewire::test(ClientForm::class)
        ->set('name', 'New Client')
        ->set('email', 'new@example.com')
        ->set('phone', '9876543210')
        ->set('address', 'Mumbai')
        ->set('taxNumber', 'GST999')
        ->set('notes', 'Test notes')
        ->call('save');

    expect(Client::query()->where('email', 'new@example.com')->exists())->toBeTrue();

    $client = Client::query()->where('email', 'new@example.com')->first();

    expect($client->name)->toBe('New Client')
        ->and($client->tax_number)->toBe('GST999');
});

it('updates an existing client when clientId is set', function (): void {
    $client = Client::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Livewire::test(ClientForm::class, [
        'client' => $client,
    ])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('phone', '1234567890')
        ->set('address', 'Pune')
        ->set('taxNumber', 'GST111')
        ->set('notes', 'Updated notes')
        ->call('save');

    $client->refresh();

    expect($client->name)->toBe('Updated Name')
        ->and($client->email)->toBe('updated@example.com')
        ->and($client->address)->toBe('Pune')
        ->and($client->tax_number)->toBe('GST111')
        ->and($client->notes)->toBe('Updated notes');
});

it('redirects to client index after saving', function (): void {
    $client = Client::factory()->create();

    Livewire::test(ClientForm::class, [
        'client' => $client,
    ])
        ->set('name', 'Updated')
        ->set('email', 'updated@example.com')
        ->set('phone', '1234567890')
        ->set('address', 'Pune')
        ->set('taxNumber', 'GST222')
        ->call('save')
        ->assertRedirect(route('admin.clients.index'));
});
