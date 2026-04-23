<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

final class DepartmentPolicy
{
    /**
     * Determine if the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can view a specific department.
     */
    public function view(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can create departments.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can update a department.
     */
    public function update(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can delete a department.
     */
    public function delete(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
