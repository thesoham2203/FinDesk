<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Department;
use Illuminate\Contracts\View\View;
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

        if ($department instanceof Department) {
            $this->departmentId = $department->id;
            $this->name = $department->name;
            $this->description = $department->description ?? '';
            $this->monthlyBudget = (string) ($department->monthly_budget / 100);
        }
    }

    /**
     * Save department (create or update).
     */
    public function save(): void
    {
        $this->authorize('create', Department::class);

        /** @var array{name: string, description: string|null, monthlyBudget: string} $validated */
        $validated = $this->validate();

        // Convert budget from dollars to cents
        $budgetCents = (int) (((float) $validated['monthlyBudget']) * 100);

        if ($this->departmentId) {
            $department = Department::query()->findOrFail($this->departmentId);
            $department->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'monthly_budget' => $budgetCents,
            ]);
            $this->dispatch('flash', type: 'success', message: 'Department updated successfully');
        } else {
            Department::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'monthly_budget' => $budgetCents,
            ]);
            $this->dispatch('flash', type: 'success', message: 'Department created successfully');
        }

        $this->redirect(route('admin.departments.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.department-form');
    }
}
