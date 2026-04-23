<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class UserForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    #[Validate('required|string')]
    public string $role = '';

    #[Validate('nullable|integer|exists:departments,id')]
    public ?int $departmentId = null;

    #[Validate('nullable|string|exists:users,id')]
    public ?string $managerId = null;

    public ?string $userId = null;

    /**
     * Mount component, optionally with existing user for editing.
     */
    public function mount(?User $user = null): void
    {
        $this->authorize('create', User::class);

        if ($user) {
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->role = $user->role->value;
            $this->departmentId = $user->department_id;
            $this->managerId = $user->manager_id;
        } else {
            $this->role = UserRole::Employee->value;
        }
    }

    /**
     * Save user (create or update).
     */
    public function save(): void
    {
        $this->authorize('create', User::class);

        // Validate password only on create or if provided on edit
        if (! $this->userId && ! $this->password) {
            $this->addError('password', 'Password is required for new users');

            return;
        }

        $validated = $this->validate();

        // For editing, password is optional (only set if provided)
        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['departmentId'],
            'manager_id' => $validated['managerId'],
        ];

        if ($validated['password']) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update($data);
            session()->flash('success', 'User updated successfully');
        } else {
            User::create($data);
            session()->flash('success', 'User created successfully');
        }

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    /**
     * Get all departments for dropdown.
     */
    public function getDepartmentsProperty(): Collection
    {
        return Department::query()->orderBy('name')->pluck('name', 'id');
    }

    /**
     * Get available managers based on role.
     * Only show users who are in roles that can manage others (Manager, Admin).
     */
    public function getManagersProperty(): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::Manager->value, UserRole::Admin->value])
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Check if department field should be shown based on role.
     */
    public function shouldShowDepartmentField(): bool
    {
        return $this->role !== UserRole::Admin->value;
    }

    /**
     * Check if manager field should be shown based on role.
     */
    public function shouldShowManagerField(): bool
    {
        return in_array($this->role, [UserRole::Employee->value, UserRole::Manager->value]);
    }

    public function render(): View
    {
        return view('livewire.admin.user-form', [
            'departments' => $this->departments,
            'managers' => $this->managers,
            'roles' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()]),
        ]);
    }
}
