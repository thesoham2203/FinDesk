<?php

declare(strict_types=1);

use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('authorized user can download attachment', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    // Create an attachment
    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/test.pdf',
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    // Put a test file on the disk
    Storage::disk('private')->put('attachments/test.pdf', 'test content');

    $this->actingAs($user);
    $response = $this->get(route('attachments.download', $attachment));

    $response->assertStatus(200)
        ->assertDownload('test.pdf');
});

it('unauthorized user gets 403 Forbidden', function (): void {
    Storage::fake('private');

    $ownerUser = User::factory()->create();
    $otherUser = User::factory()->create();

    $expense = Expense::factory()->create(['user_id' => $ownerUser->id]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/test.pdf',
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put('attachments/test.pdf', 'test content');

    $this->actingAs($otherUser);
    $response = $this->get(route('attachments.download', $attachment));

    $response->assertStatus(403);
});

it('non-existent attachment returns 404', function (): void {
    $this->actingAs(User::factory()->create());
    $response = $this->get(route('attachments.download', 99999));

    $response->assertNotFound();
});

it('returns correct Content-Type header', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/test.pdf',
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put('attachments/test.pdf', 'test content');

    $this->actingAs($user);
    $response = $this->get(route('attachments.download', $attachment));

    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('returns correct Content-Disposition with original filename', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $originalName = 'my-expense-receipt.pdf';
    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/test.pdf',
        'original_name' => $originalName,
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put('attachments/test.pdf', 'test content');

    $this->actingAs($user);
    $response = $this->get(route('attachments.download', $attachment));

    $response->assertHeader(
        'content-disposition',
        sprintf('attachment; filename=%s', $originalName)
    );
});

it('returns 404 when file not on disk (orphaned record)', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/missing.pdf',
        'original_name' => 'missing.pdf',
        'mime_type' => 'application/pdf',
    ]);

    // Don't create the file on disk - simulate orphaned record

    $this->actingAs($user);
    $response = $this->get(route('attachments.download', $attachment));

    $response->assertNotFound();
});

it('unauthenticated user gets redirected to login', function (): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/test.pdf',
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    Storage::disk('private')->put('attachments/test.pdf', 'test content');

    $response = $this->get(route('attachments.download', $attachment));

    $response->assertRedirect(route('login'));
});

it('handles different MIME types correctly', function (string $mimeType): void {
    Storage::fake('private');

    $user = User::factory()->create();
    $expense = Expense::factory()->create(['user_id' => $user->id]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => Expense::class,
        'attachable_id' => $expense->id,
        'disk' => 'private',
        'path' => 'attachments/file',
        'original_name' => 'file',
        'mime_type' => $mimeType,
    ]);

    Storage::disk('private')->put('attachments/file', 'test content');

    $this->actingAs($user);
    $response = $this->get(route('attachments.download', $attachment));

    $response->assertStatus(200);
    expect($response->headers->get('content-type'))->toContain($mimeType);
})->with([
    'application/pdf',
    'image/png',
    'image/jpeg',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain',
]);
