<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Support\Sleep;

test('client can be created with factory', function (): void {
    $client = Client::factory()->create();

    expect($client)->not->toBeNull()
        ->and($client->id)->toBeInt()
        ->and($client->name)->toBeString()
        ->and($client->email)->toBeString();
});

test('client has required attributes', function (): void {
    $client = Client::factory()->create([
        'name' => 'Acme Corp',
        'email' => 'contact@acme.com',
        'phone' => '+1-555-0123',
        'address' => '123 Main St, City, State 12345',
        'tax_number' => 'GST123456789',
        'notes' => 'Important client',
    ]);

    expect($client->toArray())
        ->toHaveKeys([
            'id',
            'name',
            'email',
            'phone',
            'address',
            'tax_number',
            'notes',
            'created_at',
            'updated_at',
        ])
        ->and($client->name)->toBe('Acme Corp')
        ->and($client->email)->toBe('contact@acme.com')
        ->and($client->phone)->toBe('+1-555-0123')
        ->and($client->address)->toBe('123 Main St, City, State 12345')
        ->and($client->tax_number)->toBe('GST123456789')
        ->and($client->notes)->toBe('Important client');
});

test('client has many invoices', function (): void {
    $client = Client::factory()->create();
    $invoice1 = Invoice::factory()->create(['client_id' => $client->id]);
    Sleep::sleep(1); // Avoid invoice_number collision
    $invoice2 = Invoice::factory()->create(['client_id' => $client->id]);

    $clientInvoices = $client->invoices;

    expect($clientInvoices)->toHaveCount(2)
        ->and($clientInvoices->pluck('id'))->toContain($invoice1->id, $invoice2->id);
});

test('client invoices relationship returns only client invoices', function (): void {
    $client1 = Client::factory()->create();
    $client2 = Client::factory()->create();

    $invoice1 = Invoice::factory()->create(['client_id' => $client1->id]);
    Sleep::sleep(1); // Avoid invoice_number collision
    $invoice2 = Invoice::factory()->create(['client_id' => $client1->id]);
    Sleep::sleep(1);
    $invoice3 = Invoice::factory()->create(['client_id' => $client2->id]);

    expect($client1->invoices)->toHaveCount(2)
        ->and($client1->invoices->pluck('id'))->toContain($invoice1->id, $invoice2->id)
        ->and($client1->invoices->pluck('id'))->not->toContain($invoice3->id);
});

test('client fillable fields are correct', function (): void {
    $fillable = new Client()->getFillable();

    expect($fillable)->toContain(
        'name',
        'email',
        'phone',
        'address',
        'tax_number',
        'notes'
    );
});

test('client can be created with minimal attributes', function (): void {
    $client = Client::factory()->create([
        'name' => 'Test Client',
        'email' => 'test@client.com',
    ]);

    expect($client->name)->toBe('Test Client')
        ->and($client->email)->toBe('test@client.com');
});

test('client can be updated', function (): void {
    $client = Client::factory()->create();

    $client->update([
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $client->refresh();

    expect($client->name)->toBe('Updated Name')
        ->and($client->email)->toBe('updated@example.com');
});

test('client can be deleted', function (): void {
    $client = Client::factory()->create();
    $id = $client->id;

    $client->delete();

    expect(Client::query()->find($id))->toBeNull();
});

test('client eager load invoices', function (): void {
    $client = Client::factory()->create();
    // Create invoices with staggered times to avoid invoice_number collision
    Sleep::sleep(1);
    Invoice::factory()->create(['client_id' => $client->id]);
    Sleep::sleep(1);
    Invoice::factory()->create(['client_id' => $client->id]);
    Sleep::sleep(1);
    Invoice::factory()->create(['client_id' => $client->id]);

    $clients = Client::with('invoices')->where('id', $client->id)->get();

    expect($clients->first()->invoices)->toHaveCount(3)
        ->and($clients->first()->invoices->first())->toBeInstanceOf(Invoice::class);
});
