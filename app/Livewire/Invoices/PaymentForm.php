<?php

declare(strict_types=1);


namespace App\Livewire\Invoices;

use App\Actions\Payment\RecordPayment;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class PaymentForm extends Component
{
    #[Locked]
    public int $invoiceId;

    #[Validate('required|numeric|min:0.01')]
    public string $amount = '';

    #[Validate('required|date')]
    public string $paymentDate = '';

    #[Validate('required|string')]
    public string $paymentMethod = '';

    #[Validate('nullable|string|max:100')]
    public string $referenceNumber = '';

    #[Validate('nullable|string|max:500')]
    public string $notes = '';

    #[Computed]
    public int $invoiceTotal = 0;

    #[Computed]
    public int $totalPaid = 0;

    #[Computed]
    public int $remaining = 0;

    /**
     * Mount the component and load invoice, calculate balances.
     */
    public function mount(int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
        $this->paymentDate = now()->format('Y-m-d');
        $this->refreshBalances();
    }

    /**
     * Record a payment against the invoice.
     */
    public function save(): void
    {
        // Validate form inputs
        $validated = $this->validate();

        // Convert amount from dollars to cents
        $amountInCents = (int) round((float) $validated['amount'] * 100);

        $invoice = Invoice::findOrFail($this->invoiceId);
        $this->authorize('recordPayment', $invoice);

        try {
            // Record the payment (Observer fires PaymentRecorded event)
            (new RecordPayment())->execute($invoice, [
                'amount' => $amountInCents,
                'payment_date' => $validated['paymentDate'],
                'payment_method' => $validated['paymentMethod'],
                'reference_number' => $validated['referenceNumber'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Dispatch event to parent component to refresh invoice data
            $this->dispatch('payment-recorded');

            // Reset form and refresh balances
            $this->reset(['amount', 'referenceNumber', 'notes']);
            $this->paymentDate = now()->format('Y-m-d');
            $this->refreshBalances();

            $this->dispatch('flash', type: 'success', message: 'Payment recorded successfully.');
        } catch (InvalidArgumentException $e) {
            $this->dispatch('flash', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Get available payment methods for dropdown.
     *
     * @return list<PaymentMethod>
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    public function render(): View
    {
        return view('livewire.invoices.payment-form');
    }

    /**
     * Refresh calculated balance amounts from the database.
     */
    private function refreshBalances(): void
    {
        $invoice = Invoice::findOrFail($this->invoiceId);
        $this->invoiceTotal = $invoice->total;
        $this->totalPaid = $invoice->payments()->sum('amount');
        $this->remaining = $this->invoiceTotal - $this->totalPaid;
    }
}
