<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    /**
     * Determine if the user can view organization settings.
     */
    public function view(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can update organization settings.
     */
    public function update(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
