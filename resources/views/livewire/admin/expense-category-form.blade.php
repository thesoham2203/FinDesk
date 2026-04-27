<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $this->categoryId ? 'Edit Category' : 'Create Category' }}
            </h2>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-md shadow p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Category Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="e.g., Travel, Meals, Office Supplies"
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
                        placeholder="Optional notes about this category..." rows="3"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Amount -->
                <div>
                    <label for="max_amount" class="block text-sm font-medium text-gray-700">
                        Maximum Amount (USD)
                    </label>
                    <div class="mt-1 flex">
                        <span
                            class="inline-flex items-center px-3 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md text-gray-700">
                            $
                        </span>
                        <input type="number" id="max_amount" wire:model="maxAmount"
                            placeholder="Leave empty for no limit" step="0.01" min="0"
                            class="w-full px-4 py-2 border border-gray-300 rounded-r-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Maximum amount per expense in this category (leave empty for no limit)
                    </p>
                    @error('maxAmount')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Requires Receipt -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="requiresReceipt"
                            class="rounded border-gray-300 text-blue-600">
                        <span class="ms-2 text-sm text-gray-700">
                            Require receipts for expenses in this category
                        </span>
                    </label>
                    @error('requiresReceipt')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $this->categoryId ? 'Update Category' : 'Create Category' }}
                        </span>
                        <span wire:loading>
                            Saving...
                        </span>
                    </button>
                    <a href="{{ route('admin.categories.index') }}" wire:navigate
                        class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-900 rounded-md hover:bg-gray-400">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>