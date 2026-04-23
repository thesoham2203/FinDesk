<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;

// ============================================================================
// USER ROLE ENUM
// ============================================================================

test('UserRole has exactly 4 roles', function (): void {
    // WHY: If someone adds or removes a role, this test catches it.
    // The HLD specifies exactly: Admin, Manager, Employee, Accountant.
    expect(UserRole::cases())->toHaveCount(4);
});

test('UserRole has all required cases', function (): void {
    // ARRANGE: Get all case values as an array of strings
    $values = array_map(fn (UserRole $role) => $role->value, UserRole::cases());

    // ASSERT: All four roles exist with correct backing values
    expect($values)->toContain('admin', 'manager', 'employee', 'accountant');
});

test('UserRole label returns human-readable text', function (): void {
    // WHY: Labels are shown in the UI (dropdowns, badges, tables).
    // They need to be readable, not raw enum values.
    expect(UserRole::Admin->label())->toBe('Administrator')
        ->and(UserRole::Manager->label())->toBe('Manager')
        ->and(UserRole::Employee->label())->toBe('Employee')
        ->and(UserRole::Accountant->label())->toBe('Accountant');
});

test('UserRole color returns a valid color string', function (): void {
    // WHY: Colors are used for badges/pills in the UI.
    // Each role needs a distinct visual indicator.
    expect(UserRole::Admin->color())->toBe('red')
        ->and(UserRole::Manager->color())->toBe('blue')
        ->and(UserRole::Employee->color())->toBe('green')
        ->and(UserRole::Accountant->color())->toBe('purple');
});

// ============================================================================
// EXPENSE STATUS ENUM
// ============================================================================

test('ExpenseStatus has exactly 6 statuses', function (): void {
    expect(ExpenseStatus::cases())->toHaveCount(6);
});

test('ExpenseStatus has all required cases', function (): void {
    $values = array_map(fn (ExpenseStatus $s) => $s->value, ExpenseStatus::cases());

    expect($values)->toContain('draft', 'submitted', 'approved', 'rejected', 'reimbursed');
});

test('ExpenseStatus label returns human-readable text', function (): void {
    expect(ExpenseStatus::Draft->label())->toBe('Draft')
        ->and(ExpenseStatus::Submitted->label())->toBe('Submitted')
        ->and(ExpenseStatus::Approved->label())->toBe('Approved')
        ->and(ExpenseStatus::Rejected->label())->toBe('Rejected')
        ->and(ExpenseStatus::Reimbursed->label())->toBe('Reimbursed')
        ->and(ExpenseStatus::PartiallyPaid->label())->toBe('Partially Paid');
});

test('ExpenseStatus color returns a valid color string', function (): void {
    expect(ExpenseStatus::Draft->color())->toBe('gray')
        ->and(ExpenseStatus::Submitted->color())->toBe('yellow')
        ->and(ExpenseStatus::Approved->color())->toBe('green')
        ->and(ExpenseStatus::Rejected->color())->toBe('red')
        ->and(ExpenseStatus::Reimbursed->color())->toBe('blue')
        ->and(ExpenseStatus::PartiallyPaid->color())->toBe('orange');
});

// ============================================================================
// INVOICE STATUS ENUM
// ============================================================================

test('InvoiceStatus has exactly 7 statuses', function (): void {
    // WHY: The HLD defines 7 invoice states:
    // Draft, Sent, Viewed, PartiallyPaid, Paid, Overdue, Cancelled
    expect(InvoiceStatus::cases())->toHaveCount(7);
});

test('InvoiceStatus has all required cases', function (): void {
    $values = array_map(fn (InvoiceStatus $s) => $s->value, InvoiceStatus::cases());

    expect($values)->toContain(
        'draft',
        'sent',
        'viewed',
        'partially_paid',
        'paid',
        'overdue',
        'cancelled',
    );
});

test('InvoiceStatus label returns human-readable text', function (): void {
    expect(InvoiceStatus::Draft->label())->toBe('Draft')
        ->and(InvoiceStatus::Sent->label())->toBe('Sent')
        ->and(InvoiceStatus::Viewed->label())->toBe('Viewed')
        ->and(InvoiceStatus::PartiallyPaid->label())->toBe('Partially Paid')
        ->and(InvoiceStatus::Paid->label())->toBe('Paid')
        ->and(InvoiceStatus::Overdue->label())->toBe('Overdue')
        ->and(InvoiceStatus::Cancelled->label())->toBe('Cancelled');
});

test('InvoiceStatus color returns a valid color string', function (): void {
    expect(InvoiceStatus::Draft->color())->toBe('gray')
        ->and(InvoiceStatus::Sent->color())->toBe('blue')
        ->and(InvoiceStatus::Viewed->color())->toBe('purple')
        ->and(InvoiceStatus::PartiallyPaid->color())->toBe('yellow')
        ->and(InvoiceStatus::Paid->color())->toBe('green')
        ->and(InvoiceStatus::Overdue->color())->toBe('red')
        ->and(InvoiceStatus::Cancelled->color())->toBe('black');
});

// State machine tests for InvoiceStatus
test('draft invoice can transition to sent or cancelled', function (): void {
    $allowed = InvoiceStatus::Draft->allowedTransitions();

    expect($allowed)->toContain(InvoiceStatus::Sent, InvoiceStatus::Cancelled)
        ->and($allowed)->toHaveCount(2);
});

test('sent invoice can transition to viewed, partially paid, paid, overdue, or cancelled', function (): void {
    $allowed = InvoiceStatus::Sent->allowedTransitions();

    expect($allowed)->toContain(
        InvoiceStatus::Viewed,
        InvoiceStatus::PartiallyPaid,
        InvoiceStatus::Paid,
        InvoiceStatus::Overdue,
        InvoiceStatus::Cancelled,
    )->and($allowed)->toHaveCount(5);
});

test('paid invoice can only transition to cancelled', function (): void {
    $allowed = InvoiceStatus::Paid->allowedTransitions();

    expect($allowed)->toHaveCount(1)
        ->and($allowed)->toContain(InvoiceStatus::Cancelled);
});

test('cancelled invoice cannot transition to any status', function (): void {
    // WHY: Cancelled is a terminal state, just like Reimbursed for expenses
    $allowed = InvoiceStatus::Cancelled->allowedTransitions();

    expect($allowed)->toBeEmpty();
});

test('overdue invoice can transition to partially paid, paid, or cancelled', function (): void {
    // WHY: An overdue invoice can still receive payments
    $allowed = InvoiceStatus::Overdue->allowedTransitions();

    expect($allowed)->toContain(
        InvoiceStatus::PartiallyPaid,
        InvoiceStatus::Paid,
        InvoiceStatus::Cancelled,
    )->and($allowed)->toHaveCount(3);
});

// ============================================================================
// PAYMENT METHOD ENUM
// ============================================================================

test('PaymentMethod has exactly 6 methods', function (): void {
    expect(PaymentMethod::cases())->toHaveCount(6);
});

test('PaymentMethod has all required cases', function (): void {
    $values = array_map(fn (PaymentMethod $m) => $m->value, PaymentMethod::cases());

    expect($values)->toContain(
        'bank_transfer',
        'credit_card',
        'cash',
        'cheque',
        'upi',
        'other',
    );
});

test('PaymentMethod label returns human-readable text', function (): void {
    expect(PaymentMethod::BankTransfer->label())->toBe('Bank Transfer')
        ->and(PaymentMethod::CreditCard->label())->toBe('Credit Card')
        ->and(PaymentMethod::Cash->label())->toBe('Cash')
        ->and(PaymentMethod::Cheque->label())->toBe('Cheque')
        ->and(PaymentMethod::UPI->label())->toBe('UPI')
        ->and(PaymentMethod::Other->label())->toBe('Other');
});

test('PaymentMethod color returns a valid color string', function (): void {
    expect(PaymentMethod::BankTransfer->color())->toBe('blue')
        ->and(PaymentMethod::CreditCard->color())->toBe('purple')
        ->and(PaymentMethod::Cash->color())->toBe('green')
        ->and(PaymentMethod::Cheque->color())->toBe('gray')
        ->and(PaymentMethod::UPI->color())->toBe('orange')
        ->and(PaymentMethod::Other->color())->toBe('gray');
});

// ============================================================================
// CURRENCY ENUM
// ============================================================================

test('Currency has exactly 4 currencies', function (): void {
    expect(Currency::cases())->toHaveCount(4);
});

test('Currency has all required cases', function (): void {
    $values = array_map(fn (Currency $c) => $c->value, Currency::cases());

    expect($values)->toContain('INR', 'USD', 'EUR', 'GBP');
});

test('Currency label returns human-readable text', function (): void {
    expect(Currency::INR->label())->toBe('Indian Rupee')
        ->and(Currency::USD->label())->toBe('US Dollar')
        ->and(Currency::EUR->label())->toBe('Euro')
        ->and(Currency::GBP->label())->toBe('British Pound');
});

test('Currency color returns a valid color string', function (): void {
    expect(Currency::INR->color())->toBe('orange')
        ->and(Currency::USD->color())->toBe('green')
        ->and(Currency::EUR->color())->toBe('blue')
        ->and(Currency::GBP->color())->toBe('red');
});

test('Currency symbol returns correct currency symbol', function (): void {
    // WHY: Symbols are used in money formatting accessors
    // throughout models (e.g., "₹ 199.99", "$ 50.00")
    expect(Currency::INR->symbol())->toBe('₹')
        ->and(Currency::USD->symbol())->toBe('$')
        ->and(Currency::EUR->symbol())->toBe('€')
        ->and(Currency::GBP->symbol())->toBe('£');
});
