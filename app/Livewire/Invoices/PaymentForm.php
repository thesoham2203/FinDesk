<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Actions\Payment\RecordPayment;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read array<int, PaymentMethod> $paymentMethods
 * @property-read int $invoiceTotal
 * @property-read int $totalPaid
 * @property-read int $remaining
 */
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

    /**
     * Mount the component and load invoice, calculate balances.
     */
    public function mount(int $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
        $this->paymentDate = now()->format('Y-m-d');
    }

    /**
     * Record a payment against the invoice.
     */
    public function save(): void
    {
        /** @var array{amount: string, paymentDate: string, paymentMethod: string, referenceNumber: ?string, notes: ?string} $validated */
        $validated = $this->validate();

        // Convert amount from dollars to cents
        $amountInCents = (int) round((float) $validated['amount'] * 100);

        $invoice = Invoice::query()->findOrFail($this->invoiceId);
        $this->authorize('recordPayment', $invoice);

        try {
            // Record the payment (Observer fires PaymentRecorded event)
            $paymentData = [
                'amount' => $amountInCents,
                'payment_date' => $validated['paymentDate'],
                'payment_method' => $validated['paymentMethod'],
            ];

            if ($validated['referenceNumber'] !== null && $validated['referenceNumber'] !== '') {
                $paymentData['reference_number'] = $validated['referenceNumber'];
            }

            if ($validated['notes'] !== null && $validated['notes'] !== '') {
                $paymentData['notes'] = $validated['notes'];
            }

            new RecordPayment()->execute($invoice, $paymentData);

            // Dispatch event to parent component to refresh invoice data
            $this->dispatch('payment-recorded');

            // Reset form and refresh balances
            $this->reset(['amount', 'referenceNumber', 'notes']);
            $this->paymentDate = now()->format('Y-m-d');

            $this->dispatch('flash', type: 'success', message: 'Payment recorded successfully.');
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->dispatch('flash', type: 'error', message: $invalidArgumentException->getMessage());
        }
    }

    #[Computed]
    public function invoiceTotal(): int
    {
        $invoice = Invoice::query()->findOrFail($this->invoiceId);

        return (int) $invoice->total;
    }

    #[Computed]
    public function totalPaid(): int
    {
        $invoice = Invoice::query()->findOrFail($this->invoiceId);

        return (int) $invoice->payments()->sum('amount');
    }

    #[Computed]
    public function remaining(): int
    {
        return $this->invoiceTotal - $this->totalPaid;
    }

    /**
     * Get available payment methods for dropdown.
     *
     * @return array<int, PaymentMethod>
     */
    #[Computed]
    public function paymentMethods(): array
    {
        return PaymentMethod::cases();
    }

    public function render(): View
    {
        return view('livewire.invoices.payment-form', [
            'invoiceTotal' => $this->invoiceTotal,
            'totalPaid' => $this->totalPaid,
            'remaining' => $this->remaining,
        ]);
    }
}
