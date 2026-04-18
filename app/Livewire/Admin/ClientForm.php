<?php

declare(strict_types=1);

/**
 * ClientForm Livewire Component
 *
 * WHAT: Create or edit a client record. Simple form with standard contact/business fields.
 *
 * WHY: Clients need to be created before creating invoices.
 *      This component handles both create and edit modes.
 *
 * IMPLEMENT: Populate form fields from existing client or defaults.
 *            Validate and save to database.
 *
 * REFERENCE:
 * - Livewire Component Lifecycle: https://livewire.laravel.com/docs/lifecycle#mount
 */

namespace App\Livewire\Admin;

use App\Models\Client;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class ClientForm extends Component
{
    #[Locked]
    public ?int $clientId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $taxNumber = '';

    public string $notes = '';

    /**
     * TODO: Mount component, optionally with existing client.
     * If editing, populate form fields from $client.
     * If creating, leave fields empty.
     */
    public function mount(?Client $client = null): void
    {
        if ($client !== null) {
            $this->clientId = $client->id;
            $this->name = $client->name;
            $this->email = $client->email;
            $this->phone = $client->phone;
            $this->address = $client->address;
            $this->taxNumber = $client->tax_number;
            $this->notes = $client->notes;
        }
    }

    /**
     * TODO: Validate and save client.
     * If creating, create new record.
     * If editing, update existing record.
     * Flash success message and redirect.
     */
    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'taxNumber' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($this->clientId !== null) {
            // Update existing
            Client::findOrFail($this->clientId)->update([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'tax_number' => $this->taxNumber,
                'notes' => $this->notes,
            ]);
            $this->dispatch('flash', type: 'success', message: 'Client updated successfully.');
        } else {
            // Create new
            Client::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'tax_number' => $this->taxNumber,
                'notes' => $this->notes,
            ]);
            $this->dispatch('flash', type: 'success', message: 'Client created successfully.');
        }

        redirect()->route('admin.clients.index');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.client-form');
    }
}
