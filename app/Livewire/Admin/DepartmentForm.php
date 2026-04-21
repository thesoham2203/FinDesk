<?php

declare(strict_types=1);

/**
 * DepartmentForm Component
 *
 * WHAT: Livewire component for creating or editing a department.
 *       Dual-use for both create (no model) and edit (with model) modes.
 *
 * WHY: Departments are organizational units with budgets. Admins configure them.
 *      This form allows setting: name, description, and monthly_budget (in cents).
 *
 * IMPLEMENT:
 *      1. Properties: $departmentId (?int), $name, $description, $monthlyBudget
 *      2. Add #[Validate] attributes on properties
 *      3. mount(?Department $department) method to populate from model
 *      4. save() method to create or update
 *      5. authorize() check for admin-only access
 */

namespace App\Livewire\Admin;

use App\Models\Department;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class DepartmentForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public string $monthlyBudget = '';

    public ?int $departmentId = null;

    /**
     * Mount component, optionally with existing department for editing.
     */
    public function mount(?Department $department = null): void
    {
        $this->authorize('create', Department::class);

        if ($department) {
            $this->departmentId = $department->id;
            $this->name = $department->name;
            $this->description = $department->description;
            $this->monthlyBudget = (string) ($department->monthly_budget / 100);
        }
    }

    /**
     * Save department (create or update).
     */
    public function save(): void
    {
        $this->authorize('create', Department::class);

        $validated = $this->validate();

        // Convert budget from dollars to cents
        $budgetCents = (int) ((float) ($validated['monthlyBudget']) * 100);

        if ($this->departmentId) {
            $department = Department::findOrFail($this->departmentId);
            $department->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'monthly_budget' => $budgetCents,
            ]);
            session()->flash('success', 'Department updated successfully');
        } else {
            Department::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'monthly_budget' => $budgetCents,
            ]);
            session()->flash('success', 'Department created successfully');
        }

        $this->redirect(route('admin.departments.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.department-form');
    }
}
