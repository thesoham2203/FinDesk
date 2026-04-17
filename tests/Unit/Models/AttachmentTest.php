<?php

declare(strict_types=1);

use App\Models\Attachment;
use App\Models\Expense;
use App\Models\InvoiceLineItem;
use App\Models\User;

test('attachment can be created with factory', function (): void {
    $attachment = Attachment::factory()->create();

    expect($attachment)->not->toBeNull()
        ->and($attachment->id)->toBeInt()
        ->and($attachment->path)->toBeString()
        ->and($attachment->disk)->toBeString()
        ->and($attachment->original_name)->toBeString()
        ->and($attachment->mime_type)->toBeString()
        ->and($attachment->size)->toBeInt();
});

test('attachment has required attributes', function (): void {
    $attachment = Attachment::factory()->create([
        'original_name' => 'receipt.pdf',
        'mime_type' => 'application/pdf',
        'size' => 102400,
        'disk' => 'local',
    ]);

    expect($attachment->toArray())
        ->toHaveKeys([
            'id',
            'attachable_type',
            'attachable_id',
            'user_id',
            'path',
            'disk',
            'original_name',
            'mime_type',
            'size',
            'created_at',
            'updated_at',
        ])
        ->and($attachment->original_name)->toBe('receipt.pdf')
        ->and($attachment->mime_type)->toBe('application/pdf')
        ->and($attachment->size)->toBe(102400)
        ->and($attachment->disk)->toBe('local');
});

test('attachment belongs to a user', function (): void {
    $user = User::factory()->create();
    $attachment = Attachment::factory()->create(['user_id' => $user->id]);

    expect($attachment->user->id)->toBe($user->id)
        ->and($attachment->user)->toBeInstanceOf(User::class);
});

test('attachment morphs to expense', function (): void {
    $expense = Expense::factory()->create();
    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(Expense::class)
        ->and($attachment->attachable->id)->toBe($expense->id);
});

test('attachment morphs to invoice line item', function (): void {
    $lineItem = InvoiceLineItem::factory()->create();
    $attachment = Attachment::factory()->create([
        'attachable_type' => InvoiceLineItem::class,
        'attachable_id' => $lineItem->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(InvoiceLineItem::class)
        ->and($attachment->attachable->id)->toBe($lineItem->id);
});

test('attachment can be queried polymorphically from expense', function (): void {
    $expense = Expense::factory()->create();
    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
    ]);

    $expenseAttachments = $expense->attachments;

    expect($expenseAttachments->count())->toBe(1)
        ->and($expenseAttachments->first()->id)->toBe($attachment->id);
});

test('multiple attachments can be created for same polymorphic model', function (): void {
    $expense = Expense::factory()->create();
    $attachment1 = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'original_name' => 'receipt1.pdf',
    ]);
    $attachment2 = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'original_name' => 'receipt2.pdf',
    ]);

    $attachments = $expense->attachments;

    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('original_name'))->toContain('receipt1.pdf', 'receipt2.pdf');
});

test('attachment fillable fields are correct', function (): void {
    $fillable = (new Attachment())->getFillable();

    expect($fillable)->toContain(
        'attachable_type',
        'attachable_id',
        'user_id',
        'path',
        'disk',
        'original_name',
        'mime_type',
        'size'
    );
});

test('attachment can eager load user relationship', function (): void {
    $user = User::factory()->create();
    Attachment::factory()->count(3)->create(['user_id' => $user->id]);

    $attachments = Attachment::with('user')->where('user_id', $user->id)->get();

    expect($attachments)->toHaveCount(3)
        ->and($attachments->first()->user)->toBeInstanceOf(User::class);
});
