<?php

declare(strict_types=1);

namespace App\Livewire\Client\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Activity;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoiceDetail extends Component
{
    #[Locked]
    public int $invoiceId;

    public ?Invoice $invoice = null;

    public function mount(Invoice $invoice): void
    {
        // Verify ownership
        abort_if($invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        $this->invoiceId = $invoice->id;
        $this->invoice = $invoice->load(['client', 'creator', 'lineItems', 'payments', 'activities']);
    }

    public function markAsViewed(): void
    {
        abort_if($this->invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        // Only transition from Sent to Viewed
        if ($this->invoice->status !== InvoiceStatus::Sent) {
            $this->dispatch('flash', type: 'error', message: 'Only sent invoices can be marked as viewed.');

            return;
        }

        $this->invoice->transitionTo(InvoiceStatus::Viewed);

        // Log activity with client_id in properties
        Activity::query()->create([
            'user_id' => null,
            'subject_type' => Invoice::class,
            'subject_id' => $this->invoice->id,
            'description' => 'Invoice marked as viewed by client',
            'properties' => ['client_id' => auth('client')->id()],
        ]);

        $this->dispatch('flash', type: 'success', message: 'Invoice marked as viewed.');
        $this->invoice = $this->invoice->fresh(['client', 'creator', 'lineItems', 'payments', 'activities']);
    }

    public function downloadPdf(): StreamedResponse
    {
        abort_if($this->invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        $pdf = Pdf::loadView('livewire.invoices.pdf', ['invoice' => $this->invoice]);

        $filename = 'invoice-'.$this->invoice->invoice_number.'-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    public function render(): View
    {
        return view('livewire.client.invoices.invoice-detail')->layout('layouts.client-app');
    }
}
