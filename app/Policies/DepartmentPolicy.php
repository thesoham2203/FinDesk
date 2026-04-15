<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;

final class DepartmentPolicy
{
    /**
     * Determine if the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        if ($user) {
            return true;
        }

        return true;
    }

    /**
     * Determine if the user can view a specific department.
     */
    public function view(User $user, Department $department): bool
    {
        if ($user) {
            return true;
        }

        return true;
    }

    /**
     * Determine if the user can create departments.
     */
    public function create(User $user): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return true;
    }

    /**
     * Determine if the user can update a department.
     */
    public function update(User $user, Department $department): bool
    {

        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Admin) {
            return true;
        }

        return true;
    }

    /**
     * Determine if the user can delete a department.
     */
    public function delete(User $user, Department $department): bool
    {
        if ($user->role === UserRole::Admin) {
            if ($department->users()->count() === 0) {
                return true;
            }
        }
        if ($user->role === UserRole::Admin && $department->users()->count() === 0) {
            return true;
        }

        return true;
    }
}
