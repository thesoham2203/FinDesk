<?php

declare(strict_types=1);

use App\Actions\Expense\UpdateExpense;
use App\Enums\ExpenseStatus;
use App\Enums\UserRole;
use App\Livewire\Expenses\ExpenseDetail;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('updates expense properties', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'Updated Title',
        'description' => 'Updated description',
        'amount' => 25000,
        'currency' => 'USD',
        'category_id' => $category->id,
        'date' => now()->subDay()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updated = $updateAction->execute($expense, $data);

    expect($updated->title)->toBe('Updated Title')
        ->and($updated->description)->toBe('Updated description')
        ->and($updated->amount)->toBe(25000)
        ->and($updated->currency->value)->toBe('USD')
        ->and($updated->category_id)->toBe($category->id);
});

it('saves changes to database', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'New Title',
        'description' => 'New desc',
        'amount' => 15000,
        'currency' => 'EUR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updateAction->execute($expense, $data);

    $refreshed = $expense->fresh();
    expect($refreshed->title)->toBe('New Title')
        ->and($refreshed->amount)->toBe(15000);
});

it('throws exception if expense is not draft', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Submitted]);
    $data = ['title' => 'Test', 'amount' => 1000, 'currency' => 'INR', 'category_id' => 1, 'description' => '', 'date' => now()->format('Y-m-d')];

    $updateAction = resolve(UpdateExpense::class);

    expect(function () use ($updateAction, $expense, $data): void {
        $updateAction->execute($expense, $data);
    })->toThrow(InvalidArgumentException::class)
        ->and(function () use ($updateAction, $expense, $data): void {
            $updateAction->execute($expense, $data);
        })->toThrow(InvalidArgumentException::class, 'Only draft expenses can be updated.');
});

it('handles receipt replacement', function (): void {
    Storage::fake('private');

    $oldReceipt = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldReceipt->store('receipts', 'private');

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'receipt_path' => $oldPath,
    ]);

    $newReceipt = UploadedFile::fake()->image('new.jpg');
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updated = $updateAction->execute($expense, $data, $newReceipt);

    expect($updated->receipt_path)->not->toBe($oldPath)
        ->and($updated->receipt_path)->toContain('receipts');
});

it('deletes old receipt when replacing', function (): void {
    Storage::fake('private');

    $oldReceipt = UploadedFile::fake()->image('old.jpg');
    $oldPath = $oldReceipt->store('receipts', 'private');

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'receipt_path' => $oldPath,
    ]);

    $newReceipt = UploadedFile::fake()->image('new.jpg');
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updated = $updateAction->execute($expense, $data, $newReceipt);

    // Verify new receipt was stored and is different from old
    expect($updated->receipt_path)->not->toBe($oldPath)
        ->and($updated->receipt_path)->toContain('receipts');
});

it('preserves receipt path when no replacement provided', function (): void {
    Storage::fake('private');

    $receipt = UploadedFile::fake()->image('receipt.jpg');
    $path = $receipt->store('receipts', 'private');

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'receipt_path' => $path,
    ]);

    $category = ExpenseCategory::factory()->create();
    $data = [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updated = $updateAction->execute($expense, $data);

    expect($updated->receipt_path)->toBe($path);
});

it('returns updated expense instance', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Draft]);
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $returned = $updateAction->execute($expense, $data);

    expect($returned->id)->toBe($expense->id)
        ->and($returned->title)->toBe('Updated');
});

it('cannot update approved expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Approved]);
    $data = ['title' => 'Test', 'amount' => 1000, 'currency' => 'INR', 'category_id' => 1, 'description' => '', 'date' => now()->format('Y-m-d')];

    $updateAction = resolve(UpdateExpense::class);

    expect(function () use ($updateAction, $expense, $data): void {
        $updateAction->execute($expense, $data);
    })->toThrow(InvalidArgumentException::class);
});

it('cannot update rejected expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Rejected]);
    $data = ['title' => 'Test', 'amount' => 1000, 'currency' => 'INR', 'category_id' => 1, 'description' => '', 'date' => now()->format('Y-m-d')];

    $updateAction = resolve(UpdateExpense::class);

    expect(function () use ($updateAction, $expense, $data): void {
        $updateAction->execute($expense, $data);
    })->toThrow(InvalidArgumentException::class);
});

it('cannot update reimbursed expense', function (): void {
    $expense = Expense::factory()->create(['status' => ExpenseStatus::Reimbursed]);
    $data = ['title' => 'Test', 'amount' => 1000, 'currency' => 'INR', 'category_id' => 1, 'description' => '', 'date' => now()->format('Y-m-d')];

    $updateAction = resolve(UpdateExpense::class);

    expect(function () use ($updateAction, $expense, $data): void {
        $updateAction->execute($expense, $data);
    })->toThrow(InvalidArgumentException::class);
});

it('updates multiple fields in one call', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'title' => 'Original',
        'amount' => 5000,
        'description' => 'Original desc',
    ]);
    $category = ExpenseCategory::factory()->create();

    $data = [
        'title' => 'New Title',
        'description' => 'New description',
        'amount' => 10000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ];

    $updateAction = resolve(UpdateExpense::class);
    $updated = $updateAction->execute($expense, $data);

    expect($updated->title)->toBe('New Title')
        ->and($updated->description)->toBe('New description')
        ->and($updated->amount)->toBe(10000);
});

it('prevents non-admin from marking as reimbursed', function (): void {
    $department = Department::factory()->create();
    $employee = User::factory()->create(['role' => UserRole::Employee, 'department_id' => $department->id]);
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::PartiallyPaid,
        'user_id' => $employee->id,
        'department_id' => $department->id,
    ]);

    $component = Livewire::actingAs($employee)->test(ExpenseDetail::class, ['expense' => $expense]);

    $component->call('reimburse')->assertForbidden();
});

it('replaces receipt and creates attachment metadata', function (): void {
    Storage::fake('local');

    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'receipt_path' => 'receipts/old.pdf',
    ]);

    $existingAttachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'user_id' => $expense->user_id,
        'path' => 'receipts/old.pdf',
        'disk' => 'local',
    ]);

    $category = ExpenseCategory::factory()->create();
    $file = UploadedFile::fake()->create('new-receipt.pdf', 128, 'application/pdf');

    $updated = resolve(UpdateExpense::class)->execute($expense, [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ], $file);

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => Expense::class,
        'attachable_id' => $updated->id,
        'user_id' => $expense->user_id,
        'original_name' => 'new-receipt.pdf',
        'mime_type' => 'application/pdf',
        'disk' => 'local',
    ]);

    $this->assertDatabaseMissing('attachments', [
        'id' => $existingAttachment->id,
    ]);

    Storage::disk('local')->assertMissing('receipts/old.pdf');
});
it('reciept path is null when no receipt uploaded', function (): void {
    $expense = Expense::factory()->create([
        'status' => ExpenseStatus::Draft,
        'receipt_path' => null,
    ]);

    $category = ExpenseCategory::factory()->create();

    $updated = resolve(UpdateExpense::class)->execute($expense, [
        'title' => 'Updated',
        'description' => 'desc',
        'amount' => 5000,
        'currency' => 'INR',
        'category_id' => $category->id,
        'date' => now()->format('Y-m-d'),
    ]);

    expect($updated->receipt_path)->toBeNull();
});
