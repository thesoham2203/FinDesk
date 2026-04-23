<div class="min-h-screen flex items-center justify-center">
    <div class="max-w-2xl w-full">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-white-900 dark:text-black">
            {{ $clientId ? 'Edit Client' : 'Create Client' }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-6 bg-white dark:bg-white-800 border-white-800 rounded-lg shadow p-6">
        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-white-900 dark:text-black">Name *</label>
            <input type="text" id="name" wire:model="name"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-white-900 dark:text-black">Email *</label>
            <input type="email" id="email" wire:model="email"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
            @error('email')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-white-900 dark:text-black">Phone</label>
            <input type="tel" id="phone" wire:model="phone"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
            @error('phone')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Address -->
        <div>
            <label for="address" class="block text-sm font-medium text-white-900 dark:text-black">Address</label>
            <textarea id="address" wire:model="address" rows="3"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600"></textarea>
            @error('address')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Tax Number -->
        <div>
            <label for="taxNumber" class="block text-sm font-medium text-white-900 dark:text-black">Tax Number
                (GST/VAT)</label>
            <input type="text" id="taxNumber" wire:model="taxNumber"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
            @error('taxNumber')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-white-900 dark:text-black">Notes</label>
            <textarea id="notes" wire:model="notes" rows="3"
                class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600"></textarea>
            @error('notes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
            <button type="submit" class="px-6 py-2 bg-blue-600 text-black rounded-lg hover:bg-blue-700 transition">
                {{ $clientId ? 'Update Client' : 'Create Client' }}
            </button>
            <a href="{{ route('admin.clients.index') }}"
                class="px-6 py-2 bg-white-300 text-white-900 rounded-lg hover:bg-white-400 transition dark:bg-white-600 dark:text-black">
                Cancel
            </a>
        </div>
    </form>
</div>