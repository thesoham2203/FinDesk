<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Clients</h1>
        <a href="{{ route('admin.clients.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + Create Client
        </a>
    </div>

    <!-- Search Bar -->
    <div class="flex gap-4">
        <input type="text" 
            wire:model.live="search" 
            placeholder="Search by name or email..." 
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
    </div>

    <!-- Clients Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Name</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Email</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Phone</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Tax Number</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Invoices</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                @forelse($this->clients as $client)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-3 text-gray-900 dark:text-white">{{ $client->name }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $client->email }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $client->phone ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $client->tax_number ?? '-' }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $client->invoices_count ?? 0 }}</td>
                        <td class="px-6 py-3 text-center space-x-2">
                            <a href="{{ route('admin.clients.edit', $client) }}" class="text-blue-600 hover:underline">Edit</a>
                            <button wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?" class="text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-600 dark:text-gray-400">
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
