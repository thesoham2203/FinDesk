<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

final class UserPolicy
{
    /**
     * Determine if the user can view any users (admin list).
     */
    public function viewAny(User $user): bool
    {
        // TODO: Only Admin role
        // TODO: Check user->role === UserRole::Admin
        return true;
    }

    /**
     * Determine if the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        // TODO: Admin can view any user
        // TODO: Any user can view their own profile
        // TODO: Check: is admin OR $user->id === $model->id
        return true;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        // TODO: Only Admin role
        // TODO: Check user->role === UserRole::Admin
        return true;
    }

    /**
     * Determine if the user can update a user.
     */
    public function update(User $user, User $model): bool
    {
        // TODO: Admin can update any user
        // TODO: Any user can update their own profile
        // TODO: Check: is admin OR $user->id === $model->id
        return true;
    }

    /**
     * Determine if the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        // TODO: Only Admin role
        // TODO: Prevent deleting yourself — check $user->id !== $model->id
        // TODO: Return false if trying to delete self
        return true;
    }
}
