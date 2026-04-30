<?php

declare(strict_types=1);

namespace App\Livewire\Invoices;

use App\Actions\Invoice\GenerateInvoiceNumber;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

/**
 * @property-read EloquentCollection<int, Client> $clients
 * @property-read EloquentCollection<int, TaxRate> $taxRates
 * @property-read string $currencySymbol
 */
final class InvoiceForm extends Component
{
    // Invoice header fields
    #[Locked]
    public ?int $invoiceId = null;

    public string $clientId = '';

    public string $issueDate = '';

    public string $dueDate = '';

    public string $notes = '';

    public string $currency = 'INR';

    // Calculated totals (in cents)
    public int $subtotal = 0;

    public int $taxTotal = 0;

    public int $grandTotal = 0;

    /**
     * @var array<int, array{
     *     description: string,
     *     quantity: string,
     *     unit_price: string,
     *     tax_rate_id: string,
     *     line_total: int,
     *     tax_amount: int
     * }>
     */
    public array $lineItems = [];

    public function mount(?Invoice $invoice = null): void
    {
        if (! $invoice instanceof Invoice) {
            // Creating new invoice
            $this->issueDate = now()->toDateString();
            $this->dueDate = now()->addDays(30)->toDateString();
            $this->addLineItem();
        } else {
            // Editing existing invoice
            $this->authorize('update', $invoice);

            abort_if($invoice->status !== InvoiceStatus::Draft, 403, 'Only draft invoices can be edited.');

            $this->invoiceId = $invoice->id;
            $this->clientId = (string) $invoice->client_id;
            $this->issueDate = $invoice->issue_date->toDateString();
            $this->dueDate = $invoice->due_date->toDateString();
            $this->notes = $invoice->notes ?? '';
            $this->currency = $invoice->currency->value;

            /** @var array<int, array{description: string, quantity: string, unit_price: string, tax_rate_id: string, line_total: int, tax_amount: int}> $lineItems */
            $lineItems = $invoice->lineItems->map(fn (InvoiceLineItem $item): array => [
                'description' => $item->description,
                'quantity' => (string) $item->quantity,
                'unit_price' => (string) ($item->unit_price / 100),
                'tax_rate_id' => (string) ($item->tax_rate_id ?? ''),
                'line_total' => (int) $item->line_total,
                'tax_amount' => (int) $item->tax_amount,
            ])->toArray();

            $this->lineItems = $lineItems;

            $this->calculateTotals();
        }
    }

    public function addLineItem(): void
    {
        $defaultTaxRate = TaxRate::query()->where('is_default', true)->first();

        $this->lineItems[] = [
            'description' => '',
            'quantity' => '1',
            'unit_price' => '',
            'tax_rate_id' => $defaultTaxRate instanceof TaxRate ? (string) $defaultTaxRate->id : '',
            'line_total' => 0,
            'tax_amount' => 0,
        ];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->lineItems) <= 1) {
            $this->dispatch('flash', type: 'error', message: 'At least one line item is required.');

            return;
        }

        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
        $this->calculateTotals();
    }

    public function updated(string $propertyName): void
    {
        if (str_starts_with($propertyName, 'lineItems.')) {
            // Parse the line item index from property name (e.g., 'lineItems.2.quantity' => 2)
            $parts = explode('.', $propertyName);
            if (count($parts) >= 2) {
                $index = (int) $parts[1];
                $this->calculateLineItem($index);
                $this->calculateTotals();
            }
        }
    }

    public function calculateLineItem(int $index): void
    {
        if (! isset($this->lineItems[$index])) {
            return;
        }

        $item = &$this->lineItems[$index];

        // Parse quantity and unit price
        $quantity = (float) $item['quantity'];
        $unitPriceDollars = (float) $item['unit_price'];

        // Convert unit price from dollars to cents
        $unitPriceCents = (int) round($unitPriceDollars * 100);

        // Calculate line total (quantity × unit price in cents)
        $lineTotal = (int) round($quantity * $unitPriceCents);

        // Look up tax rate and calculate tax amount
        $taxAmount = 0;
        if (! empty($item['tax_rate_id'])) {
            /** @var TaxRate|null $taxRate */
            $taxRate = TaxRate::query()->find($item['tax_rate_id']);
            if ($taxRate instanceof TaxRate) {
                $taxAmount = (int) round($lineTotal * ($taxRate->percentage / 100));
            }
        }

        // Update line item
        $item['line_total'] = $lineTotal;
        $item['tax_amount'] = $taxAmount;
    }

    public function calculateTotals(): void
    {
        /** @var array<int, int> $lineTotals */
        $lineTotals = array_column($this->lineItems, 'line_total');

        /** @var array<int, int> $taxAmounts */
        $taxAmounts = array_column($this->lineItems, 'tax_amount');

        $this->subtotal = array_sum($lineTotals);
        $this->taxTotal = array_sum($taxAmounts);
        $this->grandTotal = $this->subtotal + $this->taxTotal;
    }

    public function save(): void
    {
        /** @var array{clientId: string, issueDate: string, dueDate: string, currency: string, notes: string, lineItems: array<int, array{description: string, quantity: string, unit_price: string, tax_rate_id: string}>} $validated */
        $validated = $this->validate([
            'clientId' => 'required|exists:clients,id',
            'issueDate' => 'required|date',
            'dueDate' => 'required|date|after_or_equal:issueDate',
            'currency' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'lineItems' => 'required|array|min:1',
            'lineItems.*.description' => 'required|string|max:255',
            'lineItems.*.quantity' => 'required|numeric|min:0.01',
            'lineItems.*.unit_price' => 'required|numeric|min:0.01',
            'lineItems.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
        ]);

        DB::transaction(function () use ($validated): void {
            $userId = auth()->id();
            throw_if($userId === null, RuntimeException::class, 'User must be logged in.');

            if ($this->invoiceId === null) {
                $invoiceNumber = new GenerateInvoiceNumber()->execute();

                $invoice = Invoice::query()->create([
                    'client_id' => $validated['clientId'],
                    'created_by' => $userId,
                    'invoice_number' => $invoiceNumber,
                    'status' => InvoiceStatus::Draft->value,
                    'issue_date' => $validated['issueDate'],
                    'due_date' => $validated['dueDate'],
                    'notes' => $validated['notes'],
                    'subtotal' => $this->subtotal,
                    'tax_total' => $this->taxTotal,
                    'total' => $this->grandTotal,
                    'currency' => $validated['currency'],
                ]);

                $this->invoiceId = $invoice->id;
            } else {
                // Update existing invoice
                $invoice = Invoice::query()->findOrFail($this->invoiceId);
                $invoice->lineItems()->delete();
                $invoice->update([
                    'issue_date' => $validated['issueDate'],
                    'due_date' => $validated['dueDate'],
                    'notes' => $validated['notes'],
                    'subtotal' => $this->subtotal,
                    'tax_total' => $this->taxTotal,
                    'total' => $this->grandTotal,
                ]);
            }

            // Create line items (convert unit_price from dollars to cents)
            foreach ($validated['lineItems'] as $item) {
                $quantity = (float) $item['quantity'];
                $unitPriceDollars = (float) $item['unit_price'];
                $unitPriceCents = (int) round($unitPriceDollars * 100);
                $lineTotal = (int) round($quantity * $unitPriceCents);

                // Calculate tax amount
                $taxAmount = 0;
                if (! empty($item['tax_rate_id'])) {
                    /** @var TaxRate|null $taxRate */
                    $taxRate = TaxRate::query()->find($item['tax_rate_id']);
                    if ($taxRate instanceof TaxRate) {
                        $taxAmount = (int) round($lineTotal * ($taxRate->percentage / 100));
                    }
                }

                /** @var Invoice $currentInvoice */
                $currentInvoice = Invoice::query()->findOrFail($this->invoiceId);
                $currentInvoice->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPriceCents,
                    'tax_rate_id' => $item['tax_rate_id'] !== '' ? (int) $item['tax_rate_id'] : null,
                    'line_total' => $lineTotal,
                    'tax_amount' => $taxAmount,
                ]);
            }
        });

        $message = $this->invoiceId ? 'Invoice updated successfully.' : 'Invoice created successfully.';
        $this->dispatch('flash', type: 'success', message: $message);

        $this->redirect(route('invoices.show', $this->invoiceId), navigate: true);
    }

    /**
     * @return EloquentCollection<int, Client>
     */
    #[Computed]
    public function clients(): EloquentCollection
    {
        return Client::all();
    }

    /**
     * @return EloquentCollection<int, TaxRate>
     */
    #[Computed]
    public function taxRates(): EloquentCollection
    {
        /** @var EloquentCollection<int, TaxRate> $rates */
        $rates = TaxRate::query()->where('is_active', true)->get();

        return $rates;
    }

    #[Computed]
    public function currencySymbol(): string
    {
        return match ($this->currency) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            default => '$',
        };
    }

    public function getCurrencySymbolProperty(): string
    {
        return match ($this->currency) {
            'INR' => '₹',
            'USD' => '$',
            'EUR' => '€',
            default => '$',
        };
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-form');
    }
}
