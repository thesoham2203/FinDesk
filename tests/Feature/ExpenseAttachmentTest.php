<?php

declare(strict_types=1);

use App\Actions\Expense\CreateExpense;
use App\Enums\Currency;
use App\Enums\ExpenseStatus;
use App\Models\Attachment;
use App\Models\Department;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create(['department_id' => $this->department->id]);
    $this->category = ExpenseCategory::factory()->create(['requires_receipt' => true]);
});

it('creates an expense with an attachment using CreateExpense action', function (): void {
    $file = UploadedFile::fake()->create('receipt.pdf', 250, 'application/pdf');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Business Trip',
            'description' => 'Flight and hotel for client meeting',
            'amount' => 15000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    expect($expense)->toBeInstanceOf(Expense::class);
    expect($expense->title)->toBe('Business Trip');
    expect($expense->status)->toBe(ExpenseStatus::Draft);
    expect($expense->user_id)->toBe($this->user->id);
});

it('stores attachment correctly in the database when expense is created with receipt', function (): void {
    $file = UploadedFile::fake()->create('invoice.jpg', 512, 'image/jpeg');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Office Supplies',
            'description' => 'Printer cartridges',
            'amount' => 5000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'user_id' => $this->user->id,
        'original_name' => 'invoice.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 524288,
        'disk' => 'local',
    ]);
});

it('has correct attachment properties after creation', function (): void {
    $file = UploadedFile::fake()->create('document.docx', 1024, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Training Materials',
            'description' => 'Quarterly training documents',
            'amount' => 8000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    $attachment = Attachment::where('attachable_id', $expense->id)->first();

    expect($attachment)->not->toBeNull();
    expect($attachment->original_name)->toBe('document.docx');
    expect($attachment->mime_type)->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    expect($attachment->size)->toBe(1048576);
    expect($attachment->disk)->toBe('local');
    expect($attachment->attachable_type)->toBe(Expense::class);
    expect($attachment->attachable_id)->toBe($expense->id);
    expect($attachment->user_id)->toBe($this->user->id);
});

it('retrieves attachment via expense.attachments relationship', function (): void {
    $file = UploadedFile::fake()->create('receipt.pdf', 256, 'application/pdf');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Travel Expense',
            'description' => 'Hotel booking',
            'amount' => 25000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    $attachments = $expense->attachments()->get();

    expect($attachments)->toHaveCount(1);
    expect($attachments->first()->original_name)->toBe('receipt.pdf');
    expect($attachments->first()->mime_type)->toBe('application/pdf');
});

it('can retrieve attachment with correct user association', function (): void {
    $file = UploadedFile::fake()->create('expense.png', 768, 'image/png');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Marketing Assets',
            'description' => 'Design files for campaign',
            'amount' => 12000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    $attachment = $expense->attachments()->first();

    expect($attachment->user_id)->toBe($this->user->id);
    expect($attachment->user->id)->toBe($this->user->id);
});

it('does not create attachment when expense is created without receipt', function (): void {
    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Lunch Meeting',
            'description' => 'Team lunch',
            'amount' => 3000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: null,
    );

    expect($expense->attachments()->count())->toBe(0);
});

it('stores file in correct location on storage', function (): void {
    $file = UploadedFile::fake()->create('receipt.pdf', 512, 'application/pdf');

    $expense = resolve(CreateExpense::class)->execute(
        user: $this->user,
        data: [
            'title' => 'Conference Registration',
            'description' => 'Annual tech conference',
            'amount' => 20000,
            'currency' => Currency::INR,
            'category_id' => $this->category->id,
            'date' => now()->toDateString(),
        ],
        receipt: $file,
    );

    $attachment = $expense->attachments()->first();

    expect($attachment->path)->toMatch('/^expenses\//');
    Storage::disk('local')->assertExists($attachment->path);
});
