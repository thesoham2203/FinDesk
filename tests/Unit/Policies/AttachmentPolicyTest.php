<?php

declare(strict_types=1);
use App\Models\Attachment;
use App\Models\Expense;
use App\Models\User;
use App\Policies\AttachmentPolicy;

it('allows users to view attachments if they can view the parent model', function (): void {
    $user = User::factory()->unverified()->create();

    $expense = Expense::factory()
        ->for($user)->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => $expense->id,
        'attachable_type' => $expense::class,
    ]);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeTrue();
});

it('denies users from viewing attachments if they cannot view the parent model', function (): void {
    $user = User::factory()->unverified()->create();

    $otherUser = User::factory()->unverified()->create();

    $expense = Expense::factory()
        ->for($otherUser)->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => $expense->id,
        'attachable_type' => $expense::class,
    ]);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeFalse();
});

it('denies users from viewing attachments if the parent model does not exist', function (): void {
    $user = User::factory()->unverified()->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => 9999, // Non-existent ID
        'attachable_type' => Expense::class,
    ]);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeFalse();
});

it('allows users with appropriate permissions to view attachments', function (): void {
    $user = User::factory()->unverified()->create();

    $expense = Expense::factory()
        ->for($user)->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => $expense->id,
        'attachable_type' => $expense::class,
    ]);

    // Simulate that the user has permission to view the expense
    $this->actingAs($user);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeTrue();
});

it('denies users without appropriate permissions from viewing attachments', function (): void {
    $user = User::factory()->unverified()->create();

    $otherUser = User::factory()->unverified()->create();

    $expense = Expense::factory()
        ->for($otherUser)->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => $expense->id,
        'attachable_type' => $expense::class,
    ]);

    // Simulate that the user does not have permission to view the expense
    $this->actingAs($user);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeFalse();
});

it('handles attachments with missing parent models gracefully', function (): void {
    $user = User::factory()->unverified()->create();

    $attachment = Attachment::factory()->create([
        'attachable_id' => 9999, // Non-existent ID
        'attachable_type' => Expense::class,
    ]);

    $policy = new AttachmentPolicy();

    expect($policy->view($user, $attachment))->toBeFalse();
});
