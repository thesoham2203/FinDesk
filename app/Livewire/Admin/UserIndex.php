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
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    /**
     * @return LengthAwarePaginator<int, User>
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        $query = User::query()
            ->with('department', 'manager')
            ->orderBy('name', 'asc');

        if ($this->search !== '' && $this->search !== '0') {
            $query->where('name', 'like', sprintf('%%%s%%', $this->search))
                ->orWhere('email', 'like', sprintf('%%%s%%', $this->search));
        }

        if ($this->roleFilter !== '' && $this->roleFilter !== '0') {
            $query->where('role', $this->roleFilter);
        }

        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = $query->paginate(15);

        return $paginator;
    }

    public function delete(string $id): void
    {
        $user = User::query()->findOrFail($id);
        $this->authorize('delete', $user);

        // Prevent deleting the last admin user
        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin->value)->count() <= 1) {
            $this->dispatch('flash', type: 'error', message: 'Cannot delete the last admin user');

            return;
        }

        $user->delete();
        $this->dispatch('flash', type: 'success', message: 'User deleted successfully');
        $this->resetPage();
    }

    /**
     * Get available role filters.
     *
     * @return Collection<string, string>
     */
    #[Computed]
    public function roleFilters(): Collection
    {
        return collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()]);
    }

    public function render(): View
    {
        return view('livewire.admin.user-index', [
            'roleFilters' => $this->roleFilters(),
        ]);
    }
}
