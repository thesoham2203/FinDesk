<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Activity;
use App\Models\Invoice;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class InvoiceDetail extends Component
{
    #[Locked]
    public int $invoiceId;

    #[Validate('required|string|min:10|max:500')]
    public string $cancelReason = '';

    public bool $showCancelModal = false;

    public string $partialPaymentAmount = '';

    public string $dueAmount = '';

    public bool $showPartialPaymentModal = false;

    public function mount(Invoice $invoice): void
    {
        $this->authorize('view', $invoice);
        $this->invoiceId = $invoice->id;
    }

    #[Computed]
    public function invoice(): Invoice
    {
        return Invoice::query()
            ->with(['client', 'creator', 'lineItems', 'payments', 'activities'])
            ->findOrFail($this->invoiceId);
    }

    public function send(): void
    {
        $this->authorize('send', $this->invoice);

        $this->invoice->transitionTo(InvoiceStatus::Sent);

        $this->dispatch('flash', type: 'success', message: 'Invoice sent successfully.');
    }

    /**
     * TODO: Open the cancel modal dialog.
     */
    public function openCancelModal(): void
    {
        $this->showCancelModal = true;
    }

    public function cancelInvoice(): void
    {
        $this->authorize('cancel', $this->invoice);

        $this->validate([
            'cancelReason' => 'required|string|min:10|max:500',
        ]);

        $this->invoice->transitionTo(InvoiceStatus::Cancelled);

        // Log the cancellation reason in activity
        Activity::query()->create([
            'user_id' => auth()->id(),
            'subject_type' => Invoice::class,
            'subject_id' => $this->invoice->id,
            'description' => 'Invoice cancelled: '.$this->cancelReason,
        ]);

        $this->dispatch('flash', type: 'success', message: 'Invoice cancelled successfully.');
        $this->cancelReason = '';
        $this->showCancelModal = false;
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-detail', [
            'invoice' => $this->invoice,
        ]);
    }
}
