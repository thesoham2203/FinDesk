<?php

declare(strict_types=1);

/**
 * InvoiceForm Livewire Component (DYNAMIC LINE ITEMS)
 *
 * WHAT: The most complex Livewire component in the project. Handles invoice creation/edit
 *       with DYNAMIC LINE ITEMS. Users can add/remove rows, and all calculations happen
 *       server-side in real-time as they type.
 *
 * WHY: This is the main invoice creation interface. It teaches:
 *      - Array-based Livewire properties (line items as array of arrays)
 *      - Dynamic form rows (add/remove from array)
 *      - Computed recalculation (totals update when inputs change)
 *      - wire:model.live with debounce for performance
 *      - Money handling: user enters dollars, stored as cents, displayed formatted
 *
 * IMPLEMENT: Every method has detailed TODOs explaining the logic.
 *            Line items array, calculations, database save with transaction.
 *
 * REFERENCE:
 * - Livewire Properties & Array Binding: https://livewire.laravel.com/docs/properties
 * - Livewire Lifecycle & Updated Hook: https://livewire.laravel.com/docs/lifecycle
 * - Database Transactions: https://laravel.com/docs/13.x/database#transactions
 * - Money Calculations: Always use cents (integers) for precision
 */

namespace App\Livewire\Invoices;

use App\Actions\Invoice\GenerateInvoiceNumber;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TaxRate;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

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

    // Line items — ARRAY OF ARRAYS
    // Each element: ['description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_rate_id' => '', 'line_total' => 0, 'tax_amount' => 0]
    public array $lineItems = [];

    // Calculated totals (in cents)
    public int $subtotal = 0;

    public int $taxTotal = 0;

    public int $grandTotal = 0;

    /**
     * TODO: Mount the component. Handle both create and edit modes.
     * If creating:
     *   1. Set default dates (issue = today, due = today + 30 days)
     *   2. Add one empty line item via addLineItem()
     *   3. Recalculate totals
     * If editing:
     *   1. Authorize: Check user can edit (must be Draft status)
     *   2. Load invoice with lineItems
     *   3. Populate all form fields from invoice
     *   4. Convert money from cents to dollars for display (unit_price)
     *   5. Recalculate totals
     */
    public function mount(?Invoice $invoice = null): void
    {
        if ($invoice === null) {
            // Creating new invoice
            $this->issueDate = now()->toDateString();
            $this->dueDate = now()->addDays(30)->toDateString();
            $this->addLineItem();
        } else {
            // Editing existing invoice
            $this->authorize('update', $invoice);

            if ($invoice->status->value !== 'draft') {
                abort(403, 'Only draft invoices can be edited.');
            }

            $this->invoiceId = $invoice->id;
            $this->clientId = (string) $invoice->client_id;
            $this->issueDate = $invoice->issue_date->toDateString();
            $this->dueDate = $invoice->due_date->toDateString();
            $this->notes = $invoice->notes;
            $this->currency = $invoice->currency->value;

            // Load line items and convert from cents to dollars for display
            $this->lineItems = $invoice->lineItems->map(function ($item) {
                return [
                    'description' => $item->description,
                    'quantity' => (string) $item->quantity,
                    'unit_price' => (string) ($item->unit_price / 100),
                    'tax_rate_id' => (string) ($item->tax_rate_id ?? ''),
                    'line_total' => $item->line_total,
                    'tax_amount' => $item->tax_amount,
                ];
            })->toArray();

            $this->calculateTotals();
        }
    }

    /**
     * TODO: Add a new empty line item row to the lineItems array.
     * Default quantity = 1, unit_price = '', tax_rate_id = default or ''
     */
    public function addLineItem(): void
    {
        $defaultTaxRate = TaxRate::where('is_default', true)->first();

        $this->lineItems[] = [
            'description' => '',
            'quantity' => '1',
            'unit_price' => '',
            'tax_rate_id' => $defaultTaxRate?->id ? (string) $defaultTaxRate->id : '',
            'line_total' => 0,
            'tax_amount' => 0,
        ];
    }

    /**
     * TODO: Remove a line item at the given index.
     * Prevent removing if only 1 line item remains.
     * Re-index the array after removal.
     * Recalculate totals.
     */
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

    /**
     * TODO: Livewire lifecycle hook that fires when ANY property changes.
     * If property name is in lineItems array (e.g., 'lineItems.0.quantity'),
     * parse the index and recalculate that line item and totals.
     *
     * HINT: Use str_starts_with('lineItems.') and explode('-') to parse index.
     */
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

    /**
     * TODO: Calculate totals for a single line item at given index.
     * 1. Get quantity and unit_price from $this->lineItems[$index]
     * 2. Convert unit_price from dollars to cents: (int)round(floatval($unitPrice) * 100)
     * 3. Calculate line_total = quantity × unit_price_in_cents (round to nearest cent)
     * 4. Look up tax rate percentage from TaxRate model
     * 5. Calculate tax_amount = line_total × (percentage / 100) (round to nearest cent)
     * 6. Update $this->lineItems[$index]['line_total'] and ['tax_amount']
     */
    public function calculateLineItem(int $index): void
    {
        if (! isset($this->lineItems[$index])) {
            return;
        }

        $item = &$this->lineItems[$index];

        // Parse quantity and unit price
        $quantity = (float) ($item['quantity'] ?? 0);
        $unitPriceDollars = (float) ($item['unit_price'] ?? 0);

        // Convert unit price from dollars to cents
        $unitPriceCents = (int) round($unitPriceDollars * 100);

        // Calculate line total (quantity × unit price in cents)
        $lineTotal = (int) round($quantity * $unitPriceCents);

        // Look up tax rate and calculate tax amount
        $taxAmount = 0;
        if (! empty($item['tax_rate_id'])) {
            $taxRate = TaxRate::find($item['tax_rate_id']);
            if ($taxRate !== null) {
                $taxAmount = (int) round($lineTotal * ($taxRate->percentage / 100));
            }
        }

        // Update line item
        $item['line_total'] = $lineTotal;
        $item['tax_amount'] = $taxAmount;
    }

    /**
     * TODO: Recalculate grand totals from all line items.
     * 1. $this->subtotal = sum of all lineItems[*]['line_total']
     * 2. $this->taxTotal = sum of all lineItems[*]['tax_amount']
     * 3. $this->grandTotal = $this->subtotal + $this->taxTotal
     */
    public function calculateTotals(): void
    {
        $this->subtotal = (int) array_sum(array_column($this->lineItems, 'line_total'));
        $this->taxTotal = (int) array_sum(array_column($this->lineItems, 'tax_amount'));
        $this->grandTotal = $this->subtotal + $this->taxTotal;
    }

    /**
     * TODO: Validate and save the invoice to database.
     * 1. Validate header fields and line items
     * 2. DB::transaction(function() { ... }) — wrap everything in a transaction
     * 3. If creating:
     *    a. Generate invoice number via GenerateInvoiceNumber action
     *    b. Create Invoice record (status = Draft)
     * 4. If editing:
     *    a. Delete existing line items (will recreate)
     * 5. Create InvoiceLineItem records for each line item
     *    - Convert unit_price from dollars to cents when saving
     *    - Store line_total and tax_amount (already in cents)
     * 6. Update invoice: subtotal, tax_total, total
     * 7. Redirect to invoice detail with success message
     */
    public function save(): void
    {
        $this->validate([
            'clientId' => 'required|exists:clients,id',
            'issueDate' => 'required|date',
            'dueDate' => 'required|date|after_or_equal:issueDate',
            'currency' => 'required|string',
            'lineItems' => 'required|array|min:1',
            'lineItems.*.description' => 'required|string|max:255',
            'lineItems.*.quantity' => 'required|numeric|min:0.01',
            'lineItems.*.unit_price' => 'required|numeric|min:0.01',
            'lineItems.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
        ]);

        DB::transaction(function () {
            if ($this->invoiceId === null) {
                // Create new invoice
                $invoiceNumber = (new GenerateInvoiceNumber())->execute();

                $invoice = Invoice::create([
                    'client_id' => $this->clientId,
                    'created_by' => auth()->id(),
                    'invoice_number' => $invoiceNumber,
                    'status' => 'draft',
                    'issue_date' => $this->issueDate,
                    'due_date' => $this->dueDate,
                    'notes' => $this->notes,
                    'subtotal' => $this->subtotal,
                    'tax_total' => $this->taxTotal,
                    'total' => $this->grandTotal,
                    'currency' => $this->currency,
                ]);

                $this->invoiceId = $invoice->id;
            } else {
                // Update existing invoice
                $invoice = Invoice::findOrFail($this->invoiceId);
                $invoice->lineItems()->delete();
                $invoice->update([
                    'issue_date' => $this->issueDate,
                    'due_date' => $this->dueDate,
                    'notes' => $this->notes,
                    'subtotal' => $this->subtotal,
                    'tax_total' => $this->taxTotal,
                    'total' => $this->grandTotal,
                ]);
            }

            // Create line items (convert unit_price from dollars to cents)
            foreach ($this->lineItems as $item) {
                $unitPriceCents = (int) round((float) ($item['unit_price']) * 100);

                Invoice::findOrFail($this->invoiceId)->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPriceCents,
                    'tax_rate_id' => $item['tax_rate_id'] ?: null,
                    'line_total' => $item['line_total'],
                    'tax_amount' => $item['tax_amount'],
                ]);
            }
        });

        $message = $this->invoiceId ? 'Invoice updated successfully.' : 'Invoice created successfully.';
        $this->dispatch('flash', type: 'success', message: $message);

        redirect()->route('invoices.show', $this->invoiceId);
    }

    /**
     * TODO: Return all clients for dropdown.
     *
     * @return \Illuminate\Database\Eloquent\Collection<Client>
     */
    #[Computed]
    public function clients()
    {
        return Client::all();
    }

    /**
     * TODO: Return all active tax rates for dropdowns.
     *
     * @return \Illuminate\Database\Eloquent\Collection<TaxRate>
     */
    #[Computed]
    public function taxRates()
    {
        return TaxRate::where('is_active', true)->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.invoices.invoice-form');
    }
}
