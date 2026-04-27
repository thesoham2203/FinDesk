<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Departments</h2>
            <a href="{{ route('admin.departments.create') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                + Create Department
            </a>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Search -->
        <div class="mb-6 bg-white rounded-md shadow">
            <div class="p-4">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search departments by name or description..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md shadow">
            <table class="w-full">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Description</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Monthly Budget</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Users</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->departments as $department)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $department->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $department->description ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $department->formatted_budget }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $department->users_count }}</td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('admin.departments.edit', $department) }}" wire:navigate
                                    class="text-blue-600 hover:text-blue-800 px-3">
                                    Edit
                                </a>
                                <button wire:click="delete({{ $department->id }})"
                                    wire:confirm="Are you sure? This cannot be undone."
                                    class="text-red-600 hover:text-red-800">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No departments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->departments->links() }}
        </div>
    </div>
</div>