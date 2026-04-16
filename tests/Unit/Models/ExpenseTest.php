<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

test('expense can be created with factory defaults', function (): void {
    // ARRANGE & ACT: Create an expense using the factory
    // The factory automatically creates a User, Department, and Category
    $expense = Expense::factory()->create();

    // ASSERT: Verify all key attributes exist and have correct types
    expect($expense)->not->toBeNull()
        ->and($expense->id)->toBeInt()          // Auto-increment integer
        ->and($expense->title)->toBeString()
        ->and($expense->description)->toBeString()
        ->and($expense->amount)->toBeInt();      // Money stored as integer (cents)
});

test('expense amount is stored as integer (cents/paise)', function (): void {
    // WHY THIS TEST: FinDesk stores money as integers to avoid floating-point
    // precision errors. $199.99 is stored as 19999. This is critical for
    // financial accuracy in totals and calculations.

    // ARRANGE: Create expense with amount = 19999 (i.e., ₹199.99)
    $expense = Expense::factory()->create(['amount' => 19999]);

    // ACT: Read back from database
    $expense->refresh();

    // ASSERT: Amount should still be the exact integer we stored
    expect($expense->amount)->toBe(19999)
        ->and($expense->amount)->toBeInt();
});

// NOTE: Accessor tests (formattedAmount) removed - private methods can't be tested
// TODO: Implement as public accessor methods or properties before testing in UI

// ============================================================================
// SECTION 2: ENUM CASTING (STATUS & CURRENCY)
// ============================================================================

test('expense status is cast to ExpenseStatus enum', function (): void {
    // WHY THIS TEST: Laravel casts the 'status' string stored in the DB
    // to an ExpenseStatus enum object. This means $expense->status returns
    // an enum, NOT a string. This is important for type safety.

    // ARRANGE: Create expense with Draft status
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);

    // ASSERT: The status should be an enum instance, not a raw string
    expect($expense->status)->toBeInstanceOf(ExpenseStatus::class)
        ->and($expense->status)->toBe(ExpenseStatus::Draft);
});

test('expense currency is cast to Currency enum', function (): void {
    // ARRANGE: Create expense with INR currency
    $expense = Expense::factory()->create(['currency' => Currency::INR]);

    // ASSERT: Currency should be an enum, not a raw string
    expect($expense->currency)->toBeInstanceOf(Currency::class)
        ->and($expense->currency)->toBe(Currency::INR);
});

// ============================================================================
// SECTION 3: RELATIONSHIPS
// ============================================================================

test('expense belongs to a user', function (): void {
    // WHY THIS TEST: Every expense is submitted by a user.
    // This tests the user() BelongsTo relationship.

    // ARRANGE: Create a user and an expense for that user
    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    // ACT: Access the user relationship
    $expenseUser = $expense->user;

    // ASSERT: The relationship returns the correct user
    expect($expenseUser)->not->toBeNull()
        ->and($expenseUser->id)->toBe($user->id);
});

test('expense belongs to a category', function (): void {
    // WHY THIS TEST: Categories define rules for expenses (max amount,
    // requires receipt). This relationship is needed for validation.

    // ARRANGE: Create a category and an expense in that category
    $category = ExpenseCategory::factory()->create(['name' => 'Travel']);
    $expense = Expense::factory()->create(['category_id' => $category->id]);

    // ACT: Access the category relationship
    $expenseCategory = $expense->category;

    // ASSERT: Returns the correct category
    expect($expenseCategory)->not->toBeNull()
        ->and($expenseCategory->id)->toBe($category->id)
        ->and($expenseCategory->name)->toBe('Travel');
});

test('expense belongs to a department', function (): void {
    // WHY THIS TEST: Departments have budgets. We need this relationship
    // to check if an expense would push a department over budget.

    // ARRANGE: Create a department and assign expense to it
    $department = Department::factory()->create(['name' => 'Engineering']);
    $user = User::factory()->create(['department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'user_id' => $user->id,
        'department_id' => $department->id,
    ]);

    // ACT: Access the department relationship
    $expenseDepartment = $expense->department;

    // ASSERT: Returns the correct department
    expect($expenseDepartment)->not->toBeNull()
        ->and($expenseDepartment->id)->toBe($department->id)
        ->and($expenseDepartment->name)->toBe('Engineering');
});

test('expense has a reviewer (the manager who approved/rejected)', function (): void {
    // WHY THIS TEST: When an expense is approved or rejected, we record
    // WHO reviewed it. This uses a BelongsTo with a custom foreign key.

    // ARRANGE: Create a manager and an approved expense
    $manager = User::factory()->manager()->create();
    $expense = Expense::factory()->approved()->create([
        'reviewed_by' => $manager->id,
    ]);

    // ACT: Access the reviewer relationship
    $reviewer = $expense->reviewer;

    // ASSERT: The reviewer is the manager who approved it
    expect($reviewer)->not->toBeNull()
        ->and($reviewer->id)->toBe($manager->id);
});

test('expense reviewer is null for draft expenses', function (): void {
    // WHY THIS TEST: Draft expenses haven't been reviewed yet,
    // so the reviewer relationship should return null.

    // ARRANGE: Create a draft expense (no reviewer)
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'reviewed_by' => null,
    ]);

    // ASSERT: No reviewer assigned yet
    expect($expense->reviewer)->toBeNull();
});

// ============================================================================
// SECTION 4: POLYMORPHIC RELATIONSHIPS (ATTACHMENTS & ACTIVITIES)
// ============================================================================

test('expense can have many attachments (polymorphic)', function (): void {
    // WHY THIS TEST: Attachments use a polymorphic relationship. This means
    // the same Attachment model can belong to Expense OR InvoiceLineItem.
    // The 'attachable_type' column stores the model class (App\Models\Expense).
    // The 'attachable_id' column stores the expense ID.

    // ARRANGE: Create an expense and attach files to it
    $expense = Expense::factory()->create();
    $attachment1 = Attachment::factory()->create([
        'attachable_type' => Expense::class,  // Polymorphic type
        'attachable_id' => $expense->id,       // Polymorphic ID
    ]);
    $attachment2 = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
    ]);

    // ACT: Access the attachments relationship
    $attachments = $expense->attachments;

    // ASSERT: Both attachments are returned
    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('id')->toArray())
        ->toContain($attachment1->id, $attachment2->id);
});

test('expense can have many activities (audit log)', function (): void {
    // WHY THIS TEST: Activities track what happened to an expense
    // (created, submitted, approved, rejected). Also polymorphic.

    // ARRANGE: Create an expense. The ExpenseObserver automatically creates an activity.
    $expense = Expense::factory()->create();
    $user = $expense->user;

    // Manually log an additional activity (Expense submitted)
    Activity::query()->create([
        'user_id' => $user->id,
        'subject_type' => Expense::class,
        'subject_id' => $expense->id,
        'description' => 'Expense submitted',
        'properties' => [],
    ]);

    // ACT: Access the activities relationship (should have 2: 1 from observer + 1 manual)
    $activities = $expense->activities;

    // ASSERT: Both activity entries are returned
    expect($activities)->toHaveCount(2)
        ->and($activities->pluck('description'))->toContain('Expense submitted');
});

// ============================================================================
// SECTION 5: STATE MACHINE — ALLOWED TRANSITIONS
// ============================================================================
// These tests verify the ExpenseStatus enum's allowedTransitions() method.
// The state machine is a CRITICAL business rule in FinDesk.

test('draft expense can only transition to submitted', function (): void {
    // WHY: An employee creates a Draft and can only Submit it.
    // They can't jump directly to Approved or Reimbursed.

    $allowed = ExpenseStatus::Draft->allowedTransitions();

    expect($allowed)->toHaveCount(1)
        ->and($allowed)->toContain(ExpenseStatus::Submitted);
});

test('submitted expense can transition to approved or rejected', function (): void {
    // WHY: A manager reviews a Submitted expense and either Approves or Rejects.

    $allowed = ExpenseStatus::Submitted->allowedTransitions();

    expect($allowed)->toHaveCount(2)
        ->and($allowed)->toContain(ExpenseStatus::Approved, ExpenseStatus::Rejected);
});

test('approved expense can only transition to reimbursed', function (): void {
    // WHY: Once approved, the only next step is reimbursement.
    // It cannot go back to Draft or Submitted.

    $allowed = ExpenseStatus::Approved->allowedTransitions();

    expect($allowed)->toHaveCount(1)
        ->and($allowed)->toContain(ExpenseStatus::Reimbursed);
});

test('rejected expense can only transition back to draft', function (): void {
    // WHY: A rejected expense goes back to Draft so the employee
    // can edit and resubmit it.

    $allowed = ExpenseStatus::Rejected->allowedTransitions();

    expect($allowed)->toHaveCount(1)
        ->and($allowed)->toContain(ExpenseStatus::Draft);
});

test('reimbursed expense cannot transition to any status', function (): void {
    // WHY: Reimbursed is a terminal state. The expense lifecycle is complete.

    $allowed = ExpenseStatus::Reimbursed->allowedTransitions();

    expect($allowed)->toBeEmpty();
});

test('invalid transition is not in allowed list', function (): void {
    // WHY: Ensures that you can't skip steps. For example,
    // a Draft expense can't jump to Approved without being Submitted first.

    $allowedFromDraft = ExpenseStatus::Draft->allowedTransitions();

    // Draft should NOT be able to go directly to Approved, Rejected, or Reimbursed
    expect($allowedFromDraft)
        ->not->toContain(ExpenseStatus::Approved)
        ->not->toContain(ExpenseStatus::Rejected)
        ->not->toContain(ExpenseStatus::Reimbursed);
});

test('expense can transition from draft to submitted', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
    ]);

    $expense->transitionTo(ExpenseStatus::Submitted);

    expect($expense->status)->toBe(ExpenseStatus::Submitted);
});

test('expense cannot transition from draft directly to approved', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
    ]);

    expect(fn (): Expense => $expense->transitionTo(ExpenseStatus::Approved))
        ->toThrow(InvalidArgumentException::class, 'Cannot transition from Draft to Approved');
});

// ============================================================================
// SECTION 6: SCOPES — QUERY FILTERING (TODO: Scopes need public methods)
// ============================================================================
// Scopes.withStatus, pending, and submittedInMonth need to be refactored
// to use Laravel's proper scope{MethodName} pattern before they can be tested.

// ============================================================================
// SECTION 7: FACTORY STATES — TESTING DIFFERENT SCENARIOS
// ============================================================================

test('factory submitted state sets correct fields', function (): void {
    // WHY THIS TEST: Verifies that the factory's submitted() state
    // properly sets the status and submitted_at timestamp.

    // ARRANGE & ACT: Create a submitted expense using factory state
    $expense = Expense::factory()->submitted()->create();

    // ASSERT: Status is Submitted and submitted_at is set
    expect($expense->status)->toBe(ExpenseStatus::Submitted)
        ->and($expense->submitted_at)->not->toBeNull();
});

test('factory approved state sets reviewer and timestamps', function (): void {
    // ARRANGE & ACT: Create an approved expense
    $expense = Expense::factory()->approved()->create();

    // ASSERT: Has all approval-related fields
    expect($expense->status)->toBe(ExpenseStatus::Approved)
        ->and($expense->submitted_at)->not->toBeNull()
        ->and($expense->reviewed_at)->not->toBeNull()
        ->and($expense->reviewed_by)->not->toBeNull();
});

test('factory rejected state sets reason and reviewer', function (): void {
    // ARRANGE & ACT: Create a rejected expense
    $expense = Expense::factory()->rejected()->create();

    // ASSERT: Has rejection-specific fields
    expect($expense->status)->toBe(ExpenseStatus::Rejected)
        ->and($expense->rejection_reason)->not->toBeNull()
        ->and($expense->rejection_reason)->toBeString()
        ->and($expense->reviewed_by)->not->toBeNull();
});
