<?php

declare(strict_types=1);

/**
 * InvoiceDetail Livewire Component
 *
 * WHAT: Displays full invoice details including header, line items, totals, and action buttons.
 *       Shows status, line items table, payment history placeholder, and activity log.
 *
 * WHY: Users need to view complete invoice information and perform actions like Send, Cancel.
 *      This is the detail/view page for a single invoice.
 *
 * IMPLEMENT: Load invoice with relationships, implement send() and cancel() methods,
 *            show modal for cancel reason.
 *
 * REFERENCE:
 * - Livewire Component Lifecycle: https://livewire.laravel.com/docs/lifecycle#mount
 * - Livewire Dialog Modal: https://livewire.laravel.com/docs/modals
 */

namespace App\Livewire\Invoices;

use App\Models\Invoice;
use Livewire\Attributes\Locked;
use Livewire\Component;

final class InvoiceDetail extends Component
{
    #[Locked]
    public int $invoiceId;

    public ?Invoice $invoice = null;

    public string $cancelReason = '';

    public bool $showCancelModal = false;

    /**
     * TODO: Mount the component and load invoice with all relationships.
     * 1. Load invoice with: client, creator, lineItems, payments, activities
     * 2. Authorize: $this->authorize('view', $invoice)
     */
    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);

        $this->invoiceId = $invoice->id;
        $this->invoice = $invoice->load(['client', 'creator', 'lineItems', 'payments', 'activities']);
    }

    /**
     * TODO: Send the invoice (transition from Draft to Sent).
     * 1. Authorize: $this->authorize('send', $this->invoice)
     * 2. Call $this->invoice->transitionTo(InvoiceStatus::Sent)
     * 3. Flash success message
     * 4. Refresh invoice data
     */
    public function send(): void
    {
        $this->authorize('update', $this->invoice);

        $this->invoice->transitionTo(\App\Enums\InvoiceStatus::Sent);

        $this->dispatch('flash', type: 'success', message: 'Invoice sent successfully.');
        $this->invoice = $this->invoice->fresh(['client', 'creator', 'lineItems', 'payments', 'activities']);
    }

    /**
     * TODO: Open the cancel modal dialog.
     */
    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    /**
     * TODO: Cancel the invoice (transition to Cancelled).
     * 1. Authorize: $this->authorize('update', $this->invoice)
     * 2. Validate cancelReason is provided
     * 3. Call $this->invoice->transitionTo(InvoiceStatus::Cancelled)
     * 4. Log activity with cancel reason
     * 5. Flash success message
     * 6. Refresh invoice data and close modal
     */
    public function cancel(): void
    {
        $this->authorize('update', $this->invoice);

        $this->validate([
            'cancelReason' => 'required|string|min:10|max:500',
        ]);

        $this->invoice->transitionTo(\App\Enums\InvoiceStatus::Cancelled);

        // Log the cancellation reason in activity
        \App\Models\Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => Invoice::class,
            'subject_id' => $this->invoice->id,
            'description' => 'Invoice cancelled: '.$this->cancelReason,
        ]);

        $this->dispatch('flash', type: 'success', message: 'Invoice cancelled successfully.');
        $this->cancelReason = '';
        $this->showCancelModal = false;
        $this->invoice = $this->invoice->fresh(['client', 'creator', 'lineItems', 'payments', 'activities']);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.invoices.invoice-detail');
    }
}
