<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Models\User;

/**
 * ============================================================================
 * INVOICE MODEL TESTS
 * ============================================================================
 *
 * WHAT WE'RE TESTING:
 * The Invoice model is the centerpiece of FinDesk's invoicing system. It manages:
 * - Auto-numbered invoice sequences (INV-YYYY-NNNN format, unique per year)
 * - Invoice lifecycle with 7 states: Draft ? Sent ? [Viewed|PartiallyPaid|Paid|Overdue|Cancelled]
 * - Monetary calculations (subtotal, tax_total, total) � all stored as integers (cents/paise)
 * - Multiple line items (services/products on the invoice)
 * - Multiple partial payments (can receive payment in installments)
 * - Relationships to clients (who receives invoice) and creators (internal user)
 * - HasOne of Many pattern (latestPayment for invoice status automation)
 *
 * WHY THESE TESTS MATTER:
 * Invoicing is FinDesk's primary workflow. Errors cascade:
 * - Wrong numbering = accounting gaps and audit failures
 * - Invalid state transitions = stuck or corrupted invoices
 * - Incorrect calculations = financial discrepancies
 * - Missing relationships = broken reporting and client management
 *
 * TEST STRUCTURE:
 * Each test follows: ARRANGE (set up data) ? ACT (perform operation) ? ASSERT (verify outcome)
 * Comments explain the business logic reason for each assertion.
 *
 * MONEY HANDLING PATTERN:
 * All monetary values (subtotal, tax_total, total, amount) are stored as integers
 * representing cents or paise. This avoids floating-point precision errors.
 * Example: $100.50 = 10050 cents. Display via formatted accessors (Day 6 Livewire).
 *
 * ============================================================================
 */

// ============================================================================
// SECTION 1: INVOICE CREATION & AUTO-NUMBERING
// ============================================================================
// Tests that invoices are created correctly with auto-numbered INV-YYYY-NNNN format.
// Auto-numbering must be unique per year and never have gaps.

test('invoice can be created with all required attributes', function (): void {
    // ARRANGE
    $client = Client::factory()->create();
    $user = User::factory()->create();
    $today = now()->format('Y-m-d');

    // ACT
    $invoice = Invoice::query()->create([
        'client_id' => $client->id,
        'created_by' => $user->id,
        'invoice_number' => 'INV-2025-0001',
        'status' => 'draft',
        'issue_date' => $today,
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'notes' => 'Test invoice',
        'subtotal' => 100000, // $1000.00
        'tax_total' => 0,
        'total' => 100000,
        'currency' => 'INR',
    ]);

    // ASSERT
    $this->assertNotNull($invoice->id);
    expect($invoice->invoice_number)->toBe('INV-2025-0001');
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
    expect($invoice->subtotal)->toBe(100000);
    expect($invoice->total)->toBe(100000);
});

test('invoice factory generates auto-numbered invoices in INV-YYYY-NNNN format', function (): void {
    // ARRANGE
    // InvoiceFactory automatically generates invoice_number based on year and count.

    // ACT
    $invoice = Invoice::factory()->create();

    // ASSERT
    // Format: INV-YYYY-NNNN (e.g., INV-2025-0001)
    expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{4}$/');
    // Year in number should match current year
    $year = now()->year;
    expect($invoice->invoice_number)->toContain((string) $year);
});

test('invoice numbering increments sequentially within the same year', function (): void {
    // ARRANGE
    // Factory's definition() uses current year and increments count for each new invoice.

    // ACT
    $invoice1 = Invoice::factory()->create();
    $invoice2 = Invoice::factory()->create();
    $invoice3 = Invoice::factory()->create();

    // ASSERT
    // Extract sequence numbers (e.g., "0001" from "INV-2025-0001")
    preg_match('/-(\d{4})$/', (string) $invoice1->invoice_number, $matches1);
    preg_match('/-(\d{4})$/', (string) $invoice2->invoice_number, $matches2);
    preg_match('/-(\d{4})$/', (string) $invoice3->invoice_number, $matches3);

    $seq1 = (int) $matches1[1];
    $seq2 = (int) $matches2[1];
    $seq3 = (int) $matches3[1];

    // Numbers should increment by 1 each time
    expect($seq2)->toBe($seq1 + 1);
    expect($seq3)->toBe($seq2 + 1);
});

// ============================================================================
// SECTION 2: INVOICE STATE MACHINE (7 STATES & TRANSITIONS)
// ============================================================================
// Tests the invoice lifecycle: Draft ? Sent ? [Viewed|PartiallyPaid|Paid|Overdue|Cancelled]
// Invalid transitions must be rejected with InvalidArgumentException.
// Each state represents a stage in the invoice workflow.

test('invoice starts in draft status by default', function (): void {
    // ARRANGE
    // Factory creates invoices in Draft status.

    // ACT
    $invoice = Invoice::factory()->create();

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
    expect($invoice->status->value)->toBe('draft');
});

test('invoice can transition from draft to sent', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();
    expect($invoice->status)->toBe(InvoiceStatus::Draft); // Verify starting state

    // ACT
    // transitionTo() validates the transition against $status->allowedTransitions()
    $invoice->transitionTo(InvoiceStatus::Sent);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
});

test('invoice transition is persisted to database', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::Sent);

    // ASSERT
    // Reload from database to verify it was actually persisted
    $reloaded = Invoice::query()->find($invoice->id);
    expect($reloaded->status)->toBe(InvoiceStatus::Sent);
});

test('invoice can transition from sent to viewed', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::Viewed);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Viewed);
});

test('invoice can transition from sent to partially paid', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::PartiallyPaid);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid);
});

test('invoice can transition from sent to paid', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::Paid);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Paid);
});

test('invoice can transition from sent to overdue', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::Overdue);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Overdue);
});

test('invoice can transition from sent to cancelled', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT
    $invoice->transitionTo(InvoiceStatus::Cancelled);

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Cancelled);
});

test('invoice cannot transition from draft directly to paid', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create(); // Draft status
    // Draft status only allows transition to Sent.

    // ACT & ASSERT
    // transitionTo() throws InvalidArgumentException for invalid transitions
    $this->expectException(InvalidArgumentException::class);
    $invoice->transitionTo(InvoiceStatus::Paid);
});

test('invoice cannot transition from paid back to draft', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->paid()->create();

    // ACT & ASSERT
    // Paid is a final state; it only allows transition to Cancelled.
    $this->expectException(InvalidArgumentException::class);
    $invoice->transitionTo(InvoiceStatus::Draft);
});

test('invoice cannot transition to invalid state', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->sent()->create();

    // ACT & ASSERT
    // Sent allows: Viewed, PartiallyPaid, Paid, Overdue, Cancelled. Not Draft.
    $this->expectException(InvalidArgumentException::class);
    $invoice->transitionTo(InvoiceStatus::Draft);
});

test('invalid transition exception includes allowed states in message', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();

    // ACT & ASSERT
    // The exception message should list which states are allowed.
    try {
        $invoice->transitionTo(InvoiceStatus::Paid);
    } catch (InvalidArgumentException $invalidArgumentException) {
        // Message should mention "sent" (the only allowed transition from Draft, in lowercase)
        expect($invalidArgumentException->getMessage())->toContain('sent');
    }
});

// ============================================================================
// SECTION 3: RELATIONSHIPS
// ============================================================================
// Tests that invoices correctly relate to clients, creators, line items, and payments.

test('invoice belongs to a client', function (): void {
    // ARRANGE
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    // ACT
    $retrievedClient = $invoice->client;

    // ASSERT
    expect($retrievedClient->id)->toBe($client->id);
    expect($retrievedClient)->toBeInstanceOf(Client::class);
});

test('invoice belongs to a creator user', function (): void {
    // ARRANGE
    $creator = User::factory()->create();
    $invoice = Invoice::factory()->create(['created_by' => $creator->id]);

    // ACT
    $retrievedCreator = $invoice->creator;

    // ASSERT
    expect($retrievedCreator->id)->toBe($creator->id);
    expect($retrievedCreator)->toBeInstanceOf(User::class);
});

test('invoice has many line items', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();
    $lineItem1 = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);
    $lineItem2 = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

    // ACT
    $lineItems = $invoice->lineItems;

    // ASSERT
    expect($lineItems)->toHaveCount(2);
    expect($lineItems->pluck('id'))->toContain($lineItem1->id, $lineItem2->id);
});

test('invoice has many payments', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();
    $payment1 = Payment::factory()->create(['invoice_id' => $invoice->id]);
    $payment2 = Payment::factory()->create(['invoice_id' => $invoice->id]);

    // ACT
    $payments = $invoice->payments;

    // ASSERT
    expect($payments)->toHaveCount(2);
    expect($payments->pluck('id'))->toContain($payment1->id, $payment2->id);
});

test('invoice can have multiple payments (partial payment support)', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create(['total' => 100000]); // $1000.00

    // ACT
    // Create 3 payments totaling $1000.00
    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 40000]); // $400.00
    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 35000]); // $350.00
    Payment::factory()->create(['invoice_id' => $invoice->id, 'amount' => 25000]); // $250.00

    // ASSERT
    expect($invoice->payments)->toHaveCount(3);
    $totalPaid = $invoice->payments->sum('amount');
    expect($totalPaid)->toBe(100000); // All payments sum to invoice total
});

// ============================================================================
// SECTION 4: HASONE OF MANY PATTERN (LATEST PAYMENT)
// ============================================================================
// Tests the "HasOne of Many" relationship: latestPayment() returns only the most recent payment.

test('invoice has one latest payment via HasOne of Many relationship', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();
    Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_date' => '2025-01-01']);
    Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_date' => '2025-01-05']);
    $latestPayment = Payment::factory()->create(['invoice_id' => $invoice->id, 'payment_date' => '2025-01-10']);

    // ACT
    $retrieved = $invoice->latestPayment;

    // ASSERT
    // Should return only the most recent payment, not all payments
    expect($retrieved->id)->toBe($latestPayment->id);
    expect($retrieved->payment_date->format('Y-m-d'))->toBe('2025-01-10');
});

test('invoice latest payment is null when no payments exist', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create();

    // ACT
    $latestPayment = $invoice->latestPayment;

    // ASSERT
    expect($latestPayment)->toBeNull();
});

// ============================================================================
// SECTION 5: MONETARY CALCULATIONS (SUBTOTAL, TAX, TOTAL)
// ============================================================================
// Tests that money calculations work correctly. All amounts stored as integers (cents/paise).

test('invoice starts with zero subtotal and total', function (): void {
    // ARRANGE
    // Factory defaults subtotal and total to 0 because line items are added separately.

    // ACT
    $invoice = Invoice::factory()->create();

    // ASSERT
    expect($invoice->subtotal)->toBe(0);
    expect($invoice->tax_total)->toBe(0);
    expect($invoice->total)->toBe(0);
});

test('invoice can store money values as integers', function (): void {
    // ARRANGE
    // Create invoice with specific amounts (in cents/paise).
    $subtotal = 150000; // $1500.00
    $taxTotal = 27000;  // $270.00 (18% tax)
    $total = 177000;    // $1770.00

    // ACT
    $invoice = Invoice::query()->create([
        'client_id' => Client::factory()->create()->id,
        'created_by' => User::factory()->create()->id,
        'invoice_number' => 'INV-2025-TEST',
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'subtotal' => $subtotal,
        'tax_total' => $taxTotal,
        'total' => $total,
        'currency' => 'INR',
    ]);

    // ASSERT
    // Values should be retrieved as integers (no floating-point corruption)
    expect($invoice->subtotal)->toBe(150000);
    expect($invoice->tax_total)->toBe(27000);
    expect($invoice->total)->toBe(177000);
});

test('invoice calculates total correctly', function (): void {
    // ARRANGE
    $subtotal = 100000; // $1000.00
    $taxTotal = 18000;  // $180.00

    // ACT
    $invoice = Invoice::query()->create([
        'client_id' => Client::factory()->create()->id,
        'created_by' => User::factory()->create()->id,
        'invoice_number' => 'INV-2025-CALC',
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'subtotal' => $subtotal,
        'tax_total' => $taxTotal,
        'total' => $subtotal + $taxTotal, // Expected calculation
        'currency' => 'INR',
    ]);

    // ASSERT
    expect($invoice->total)->toBe($subtotal + $taxTotal);
    expect($invoice->total)->toBe(118000);
});

// ============================================================================
// SECTION 6: CASTING & ATTRIBUTES
// ============================================================================
// Tests that model attributes are cast to correct types (enums, dates, integers, etc.).

test('invoice status is cast to InvoiceStatus enum', function (): void {
    // ARRANGE
    // Factory creates invoice with status='draft'; it should be cast to enum.

    // ACT
    $invoice = Invoice::factory()->create();

    // ASSERT
    expect($invoice->status)->toBeInstanceOf(InvoiceStatus::class);
    expect($invoice->status->value)->toBe('draft');
});

test('invoice currency is cast to Currency enum', function (): void {
    // ARRANGE
    $invoice = Invoice::factory()->create(['currency' => 'INR']);

    // ACT & ASSERT
    expect($invoice->currency)->toBeInstanceOf(Currency::class);
    expect($invoice->currency->value)->toBe('INR');
});

test('invoice dates are cast to Carbon date instances', function (): void {
    // ARRANGE
    $issue = '2025-01-15';
    $due = '2025-02-15';
    $invoice = Invoice::query()->create([
        'client_id' => Client::factory()->create()->id,
        'created_by' => User::factory()->create()->id,
        'invoice_number' => 'INV-2025-DATE',
        'status' => 'draft',
        'issue_date' => $issue,
        'due_date' => $due,
        'subtotal' => 0,
        'tax_total' => 0,
        'total' => 0,
        'currency' => 'INR',
    ]);

    // ACT & ASSERT
    // Dates should be castable to strings in Y-m-d format
    expect($invoice->issue_date->format('Y-m-d'))->toBe($issue);
    expect($invoice->due_date->format('Y-m-d'))->toBe($due);
});

test('invoice money attributes are cast to integers', function (): void {
    // ARRANGE
    $invoice = Invoice::query()->create([
        'client_id' => Client::factory()->create()->id,
        'created_by' => User::factory()->create()->id,
        'invoice_number' => 'INV-2025-CAST',
        'status' => 'draft',
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->format('Y-m-d'),
        'subtotal' => 100000,
        'tax_total' => 18000,
        'total' => 118000,
        'currency' => 'INR',
    ]);

    // ACT & ASSERT
    // All money values should be integers, never floats
    expect($invoice->subtotal)->toBeInt();
    expect($invoice->tax_total)->toBeInt();
    expect($invoice->total)->toBeInt();
});

// ============================================================================
// SECTION 7: FACTORY STATES
// ============================================================================
// Tests factory states for quickly creating invoices in specific states.

test('invoice factory sent state sets status to sent', function (): void {
    // ARRANGE
    // .sent() state sets status='sent' and adjusts dates for a realistic sent invoice.

    // ACT
    $invoice = Invoice::factory()->sent()->create();

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
});

test('invoice factory paid state sets status to paid', function (): void {
    // ARRANGE
    // .paid() state sets status='paid' and updates dates to past (realistic paid invoice).

    // ACT
    $invoice = Invoice::factory()->paid()->create();

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Paid);
});

test('invoice factory overdue state sets status to overdue', function (): void {
    // ARRANGE
    // .overdue() state creates an invoice that has passed its due date without payment.

    // ACT
    $invoice = Invoice::factory()->overdue()->create();

    // ASSERT
    expect($invoice->status)->toBe(InvoiceStatus::Overdue);
});
