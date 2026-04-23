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
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can update a user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->role === UserRole::Admin;
    }

    /**
     * Determine if the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->role === UserRole::Admin && $user->id !== $model->id;
    }
}
