<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-white-900 dark:text-white-500">Clients</h1>
        <a href="{{ route('admin.clients.create') }}"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + Create Client
        </a>
    </div>

    <!-- Search Bar -->
    <div class="flex gap-4">
        <input type="text" wire:model.live="search" placeholder="Search by name or email..."
            class="flex-1 px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-gray-600">
    </div>

    <!-- Clients Table -->
    <div class="overflow-x-auto bg-white dark:bg-white-800 rounded-lg shadow">
        <table class="w-full text-sm">
            <thead class="bg-white-100 dark:bg-white-700 border-b dark:border-white-600">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-white-900 dark:text-black">Name</th>
                    <th class="px-6 py-3 text-left font-semibold text-white-900 dark:text-black">Email</th>
                    <th class="px-6 py-3 text-left font-semibold text-white-900 dark:text-black">Phone</th>
                    <th class="px-6 py-3 text-left font-semibold text-white-900 dark:text-black">Tax Number</th>
                    <th class="px-6 py-3 text-left font-semibold text-white-900 dark:text-black">Invoices</th>
                    <th class="px-6 py-3 text-center font-semibold text-white-900 dark:text-black">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-white-700">
                @forelse($this->clients as $client)
                    <tr class="hover:bg-white-50 dark:hover:bg-white-700 transition">
                        <td class="px-6 py-3 text-white-900 dark:text-black">{{ $client->name }}</td>
                        <td class="px-6 py-3 text-white-600 dark:text-white-300">{{ $client->email }}</td>
                        <td class="px-6 py-3 text-white-600 dark:text-white-300">{{ $client->phone}}</td>
                        <td class="px-6 py-3 text-white-600 dark:text-white-300">{{ $client->tax_number }}</td>
                        <td class="px-6 py-3 text-white-600 dark:text-white-300">{{ $client->invoices_count ?? 0 }}</td>
                        <td class="px-6 py-3 text-center space-x-2">
                            <a href="{{ route('admin.clients.edit', $client) }}"
                                class="text-blue-600 hover:underline">Edit</a>
                            <button wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?"
                                class="text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-white-600 dark:text-white-400">
                            No clients found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div>
        {{ $this->clients->links() }}
    </div>
</div>