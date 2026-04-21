<?php

declare(strict_types=1);

/**
 * UserIndex Component
 *
 * WHAT: Livewire component that lists all users with their roles,
 *       departments, and managers. Admin can create, edit, and delete users.
 *
 * WHY: User management is critical for system operations. This interface
 *      lets admins see all users, their assignments, and manage them.
 */

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class UserIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->with('department', 'manager')
            ->orderBy('name', 'asc');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%");
        }

        if ($this->roleFilter) {
            $query->where('role', $this->roleFilter);
        }

        return $query->paginate(15);
    }

    public function delete(string $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        // Prevent deleting the last admin user
        if ($user->role === UserRole::Admin && User::where('role', UserRole::Admin->value)->count() <= 1) {
            session()->flash('error', 'Cannot delete the last admin user');

            return;
        }

        $user->delete();
        session()->flash('success', 'User deleted successfully');
        $this->resetPage();
    }

    /**
     * Get available role filters.
     */
    public function getRoleFiltersProperty()
    {
        return collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role) => [$role->value => $role->label()]);
    }

    public function render(): View
    {
        return view('livewire.admin.user-index', [
            'roleFilters' => $this->roleFilters,
        ]);
    }
}
