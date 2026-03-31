<?php

declare(strict_types=1);

use App\Models\User;

test('to array', function (): void {
    $user = User::factory()->create();

    $keys = array_keys($user->toArray());

    expect($keys)->toContain('id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at');
});
