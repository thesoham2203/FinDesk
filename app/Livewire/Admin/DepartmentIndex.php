<?php

declare(strict_types=1);

/**
 * DepartmentIndex Component
 *
 * WHAT: Livewire component that lists all departments with their budgets
 *       and user counts. Admin can create, edit, and delete departments.
 *
 * WHY: Department management is a core admin function. This CRUD interface
 *      lets admins organize the company structure.
 */

namespace App\Livewire\Admin;

use App\Models\Department;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class DepartmentIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Department::class);
    }

    #[Computed]
    public function departments(): LengthAwarePaginator
    {
        $query = Department::query()->withCount('users')->orderBy('name', 'asc');

        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%");
        }

        return $query->paginate(15);
    }

    public function delete(int $id): void
    {
        $department = Department::findOrFail($id);
        $this->authorize('delete', $department);

        if ($department->users()->count() > 0) {
            session()->flash('error', "Cannot delete department with {$department->users()->count()} users. Please reassign users first.");
        } else {
            $department->delete();
            session()->flash('success', 'Department deleted successfully');
            $this->resetPage();
        }
    }

    public function render(): View
    {
        return view('livewire.admin.department-index');
    }
}
