<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Livewire\Forms\InvoiceHeaderForm;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Livewire;

uses(RefreshDatabase::class);

final class InvoiceHeaderFormTestComponent extends Component
{
    public InvoiceHeaderForm $form;

    public function mount(Invoice $invoice): void
    {
        $this->form->setFromInvoice($invoice);
    }

    public function render(): View
    {
        return view('layouts.guest');
    }
}

test('invoice header form can be populated from an invoice', function (): void {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->for($client)->create([
        'issue_date' => '2026-04-01',
        'due_date' => '2026-04-15',
        'notes' => 'Header notes',
        'currency' => Currency::USD->value,
    ]);

    Livewire::test(InvoiceHeaderFormTestComponent::class, ['invoice' => $invoice])
        ->assertSet('form.clientId', (string) $invoice->client_id)
        ->assertSet('form.issueDate', '2026-04-01')
        ->assertSet('form.dueDate', '2026-04-15')
        ->assertSet('form.notes', 'Header notes')
        ->assertSet('form.currency', Currency::USD->value);
});
