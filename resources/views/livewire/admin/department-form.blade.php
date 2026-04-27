<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $this->departmentId ? 'Edit Department' : 'Create Department' }}
            </h2>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-md shadow p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Department Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="e.g., Engineering, Sales, HR"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea id="description" wire:model="description"
                        placeholder="Optional notes about this department..." rows="3"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Monthly Budget -->
                <div>
                    <label for="monthly_budget" class="block text-sm font-medium text-gray-700">
                        Monthly Budget (₹) <span class="text-red-600">*</span>
                    </label>
                    <div class="mt-1 flex">
                        <span
                            class="inline-flex items-center px-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md text-gray-700">
                            ₹
                        </span>
                        <input type="number" id="monthly_budget" wire:model="monthlyBudget" placeholder="0.00"
                            step="0.01" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-r-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Monthly budget limit for this department's expenses
                    </p>
                    @error('monthlyBudget')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $this->departmentId ? 'Update Department' : 'Create Department' }}
                        </span>
                        <span wire:loading>
                            Saving...
                        </span>
                    </button>
                    <a href="{{ route('admin.departments.index') }}" wire:navigate
                        class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-900 rounded-md hover:bg-gray-400">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>