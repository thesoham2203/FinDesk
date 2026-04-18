<?php

declare(strict_types=1);

/**
 * InvoiceHeaderForm — Livewire Form Object
 *
 * WHAT: A reusable Livewire Form Object for invoice header fields.
 *       Form Objects group related properties and validation rules.
 *
 * WHY: The HLD requires at least one Form Object. This extracts invoice header
 *      field properties and validation out of the InvoiceForm component.
 *      Makes code more organized and reusable.
 *
 * IMPLEMENT: Define properties with validation attributes. Parent component
 *            accesses via $this->form->propertyName.
 *
 * REFERENCE:
 * - Livewire Form Objects: https://livewire.laravel.com/docs/forms#form-objects
 * - Validation Attributes: https://livewire.laravel.com/docs/validation#validate-attribute
 */

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
