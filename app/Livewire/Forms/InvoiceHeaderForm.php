<?php

declare(strict_types=1);


namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

final class InvoiceHeaderForm extends Form
{
    #[Validate('required|exists:clients,id')]
    public string $clientId = '';

    #[Validate('required|date')]
    public string $issueDate = '';

    #[Validate('required|date|after_or_equal:issueDate')]
    public string $dueDate = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    #[Validate('required|string')]
    public string $currency = 'INR';

    /**
     * TODO: Optional method to populate form from existing invoice.
     * Useful for edit mode — set all properties from Invoice model.
     */
    public function setFromInvoice(\App\Models\Invoice $invoice): void
    {
        $this->clientId = (string) $invoice->client_id;
        $this->issueDate = $invoice->issue_date->toDateString();
        $this->dueDate = $invoice->due_date->toDateString();
        $this->notes = $invoice->notes;
        $this->currency = $invoice->currency->value;
    }
}
