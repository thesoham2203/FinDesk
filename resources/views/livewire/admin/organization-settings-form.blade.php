<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Organization Settings</h2>
            <p class="text-gray-600 text-sm mt-1">Configure your organization's basic information and preferences</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-md shadow p-6">
            <form wire:submit="save" class="space-y-6">
                <!-- Organization Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Organization Name <span class="text-red-600">*</span>
                    </label>
                    <input type="text" id="name" wire:model="name" placeholder="Your Company Name"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">
                        Address
                    </label>
                    <textarea id="address" wire:model="address" placeholder="Street address, city, state, country..."
                        rows="3"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Logo -->
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">
                        Logo
                    </label>
                    @if ($logoPath)
                        <div class="mt-2 mb-4">
                            <img src="{{ Storage::url($logoPath) }}" alt="Organization Logo"
                                class="h-20 w-auto rounded-md border border-gray-300">
                        </div>
                    @endif
                    <input type="file" id="logo" wire:model="logo" accept="image/*"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Maximum 2MB. JPG, PNG, or GIF format.</p>
                    @error('logo')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Default Currency -->
                <div>
                    <label for="default_currency" class="block text-sm font-medium text-gray-700">
                        Default Currency <span class="text-red-600">*</span>
                    </label>
                    <select id="default_currency" wire:model="defaultCurrency"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select a currency</option>
                        @foreach ($currencies as $currencyValue => $currencyLabel)
                            <option value="{{ $currencyValue }}">{{ $currencyLabel }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Default currency for invoices and expenses</p>
                    @error('defaultCurrency')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fiscal Year Start -->
                <div>
                    <label for="fiscal_year_start" class="block text-sm font-medium text-gray-700">
                        Fiscal Year Start Month <span class="text-red-600">*</span>
                    </label>
                    <select id="fiscal_year_start" wire:model="fiscalYearStart"
                        class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        @foreach ($months as $monthNum => $monthName)
                            <option value="{{ $monthNum }}">{{ $monthName }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-500">Month when your fiscal year begins</p>
                    @error('fiscalYearStart')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit"
                        class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            Save Settings
                        </span>
                        <span wire:loading>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>