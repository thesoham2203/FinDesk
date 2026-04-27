<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Users</h2>
            <a href="{{ route('admin.users.create') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                + Create User
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

        <!-- Filters -->
        <div class="mb-6 bg-white rounded-md shadow p-4 space-y-4">
            <!-- Search -->
            <div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search users by name or email..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Role Filter -->
            <div>
                <select wire:model.live="roleFilter"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Roles</option>
                    @foreach ($roleFilters as $roleValue => $roleLabel)
                        <option value="{{ $roleValue }}">{{ $roleLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-md shadow overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Role</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Department</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Manager</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->users as $user)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    {{ $user->role->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $user->department?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $user->manager?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" wire:navigate
                                    class="text-blue-600 hover:text-blue-800 px-3">
                                    Edit
                                </a>
                                @if (!($user->role->value === 'admin' && \App\Models\User::where('role', 'admin')->count() === 1))
                                    <button wire:click="delete('{{ $user->id }}')"
                                        wire:confirm="Are you sure? This cannot be undone."
                                        class="text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->users->links() }}
        </div>
    </div>
</div>