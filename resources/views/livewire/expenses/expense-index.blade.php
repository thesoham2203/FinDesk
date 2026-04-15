{{-- ExpenseIndex View
    WHAT: Scaffold for the authenticated "My Expenses" list.
    WHY: This view introduces the filter bar, table shell, pagination slot, and empty state
    for the expense listing workflow.
    IMPLEMENT: Connect the table rows, filter actions, and status formatting to the component data.
    KEY CONCEPTS: Livewire pagination, URL-persisted filters, role-aware lists, Tailwind cards/tables.
--}}
<div class="py-12">
    <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">My Expenses</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Browse your expenses, filter by status or category, and open any submission for details.
                </p>
            </div>

            <a href="{{ route('expenses.create') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                Create Expense
            </a>
        </div>

        @if (session()->has('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="lg:col-span-3">
                    <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Search</label>
                    <input id="search" type="text" wire:model.live.debounce.300ms="search"
                        placeholder="Search title or description..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label for="statusFilter" class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                    <select id="statusFilter" wire:model.live="statusFilter"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All Statuses</option>
                        @foreach ($this->statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="categoryFilter" class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <select id="categoryFilter" wire:model.live="categoryFilter"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">All Categories</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="dateFrom" class="mb-1 block text-sm font-medium text-gray-700">Date From</label>
                    <input id="dateFrom" type="date" wire:model.live="dateFrom"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label for="dateTo" class="mb-1 block text-sm font-medium text-gray-700">Date To</label>
                    <input id="dateTo" type="date" wire:model.live="dateTo"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label for="amountMin" class="mb-1 block text-sm font-medium text-gray-700">Amount Min</label>
                    <input id="amountMin" type="number" step="0.01" wire:model.live="amountMin"
                        placeholder="In dollars"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div>
                    <label for="amountMax" class="mb-1 block text-sm font-medium text-gray-700">Amount Max</label>
                    <input id="amountMax" type="number" step="0.01" wire:model.live="amountMax"
                        placeholder="In dollars"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>

                <div class="flex items-end gap-3">
                    <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600">
                <span wire:loading wire:target="search,statusFilter,categoryFilter,dateFrom,dateTo,amountMin,amountMax">
                    Updating results...
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($this->expenses as $expense)
                            <tr>
                                @php
                                    $badgeClasses = match ($expense->status->color()) {
                                        'gray' => 'bg-gray-100 text-gray-800',
                                        'yellow' => 'bg-yellow-100 text-yellow-800',
                                        'green' => 'bg-green-100 text-green-800',
                                        'red' => 'bg-red-100 text-red-800',
                                        'blue' => 'bg-blue-100 text-blue-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $expense->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $expense->formatted_amount }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $expense->category?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                        {{ $expense->status->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $expense->created_at?->format('M d, Y') ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-wrap gap-3">
                                        <a href="{{ route('expenses.show', $expense) }}" wire:navigate class="text-blue-600 hover:text-blue-800">View</a>
                                        @if ($expense->status === \App\Enums\ExpenseStatus::Draft)
                                            <a href="{{ route('expenses.edit', $expense) }}" wire:navigate class="text-amber-600 hover:text-amber-800">Edit</a>
                                            <button type="button" class="text-red-600 hover:text-red-800">Delete</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No expenses found. The scaffold is ready for the Day 4 query and filter logic.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">
            {{ $this->expenses->links() }}
        </div>
    </div>
</div>
