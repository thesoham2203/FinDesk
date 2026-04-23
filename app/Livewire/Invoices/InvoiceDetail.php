<?php

declare(strict_types=1);


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

    public string $partialPaymentAmount = '';

    public string $dueAmount = '';

    public bool $showPartialPaymentModal = false;

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
