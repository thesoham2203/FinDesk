<?php

declare(strict_types=1);

namespace App\Livewire\Client\Invoices;

use App\Enums\InvoiceStatus;
use App\Models\Activity;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property-read Invoice $invoice
 */
final class InvoiceDetail extends Component
{
    #[Locked]
    public int $invoiceId;

    public function mount(Invoice $invoice): void
    {
        // Verify ownership
        abort_if($invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        $this->invoiceId = $invoice->id;
    }

    #[Computed]
    public function invoice(): Invoice
    {
        return Invoice::query()
            ->with(['client', 'creator', 'lineItems', 'payments', 'activities'])
            ->findOrFail($this->invoiceId);
    }

    public function markAsViewed(): void
    {
        $invoice = $this->invoice;
        abort_if($invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        // Only transition from Sent to Viewed
        if ($invoice->status !== InvoiceStatus::Sent) {
            $this->dispatch('flash', type: 'error', message: 'Only sent invoices can be marked as viewed.');

            return;
        }

        $invoice->transitionTo(InvoiceStatus::Viewed);

        // Log activity with client_id in properties
        Activity::query()->create([
            'user_id' => null,
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
            'description' => 'Invoice marked as viewed by client',
            'properties' => ['client_id' => auth('client')->id()],
        ]);

        $this->dispatch('flash', type: 'success', message: 'Invoice marked as viewed.');
    }

    public function downloadPdf(): StreamedResponse
    {
        $invoice = $this->invoice;
        abort_if($invoice->client_id !== auth('client')->id(), 403, 'Unauthorized');

        $pdf = Pdf::loadView('livewire.invoices.pdf', ['invoice' => $invoice]);

        $filename = 'invoice-'.$invoice->invoice_number.'-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(static function () use ($pdf): void {
            echo $pdf->output();
        }, $filename);
    }

    public function render(): View
    {
        /** @var View $view */
        $view = view('livewire.client.invoices.invoice-detail', [
            'invoice' => $this->invoice,
        ])->layout('layouts.client-app');

        return $view;
    }
}
