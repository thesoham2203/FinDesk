<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                {{ $this->taxRateId ? 'Edit Tax Rate' : 'Create Tax Rate' }}
            </h2>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-md shadow p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Tax Rate Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="e.g., GST 18%, VAT 20%"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Percentage -->
                <div>
                    <label for="percentage" class="block text-sm font-medium text-gray-700">
                        Percentage <span class="text-red-600">*</span>
                    </label>
                    <div class="mt-1 flex">
                        <input type="number" id="percentage" wire:model="percentage" placeholder="0" step="0.01" min="0"
                            max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                        <span
                            class="inline-flex items-center px-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-700">
                            %
                        </span>
                    </div>
                    @error('percentage')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Default -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="isDefault" class="rounded border-gray-300 text-blue-600">
                        <span class="ms-2 text-sm text-gray-700">
                            Set as default
                        </span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Pre-selected when adding invoice line items
                    </p>
                    @error('isDefault')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Active -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="isActive" class="rounded border-gray-300 text-blue-600">
                        <span class="ms-2 text-sm text-gray-700">
                            Active
                        </span>
                    </label>
                    <p class="mt-1 text-sm text-gray-500">
                        Inactive rates won't appear in dropdowns but existing invoices will retain them
                    </p>
                    @error('isActive')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $this->taxRateId ? 'Update Tax Rate' : 'Create Tax Rate' }}
                        </span>
                        <span wire:loading>
                            Saving...
                        </span>
                    </button>
                    <a href="{{ route('admin.tax-rates.index') }}" wire:navigate
                        class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-900 rounded-md hover:bg-gray-400">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>