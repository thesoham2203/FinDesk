<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Tax Rates</h2>
            <a href="{{ route('admin.tax-rates.create') }}" wire:navigate
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                + Create Tax Rate
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

        <!-- Table -->
        <div class="bg-white rounded-md shadow">
            <table class="w-full">
                <thead class="bg-gray-100 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Percentage</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Default</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Line Items</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->taxRates as $rate)
                                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $rate->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($rate->percentage, 2) }}%</td>
                                        <td class="px-6 py-4 text-sm">
                                            @if ($rate->is_default)
                                                <span
                                                    class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold">
                                                    Default
                                                </span>
                                            @else
                                                <span
                                                    class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-semibold">
                                                    —
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            <button wire:click="toggleActive({{ $rate->id }})" class="px-3 py-1 rounded-full text-xs font-semibold
                                                        {{ $rate->is_active
                        ? 'bg-green-100 text-green-800'
                        : 'bg-gray-100 text-gray-800' }}">
                                                {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $rate->line_items_count }}</td>
                                        <td class="px-6 py-4 text-sm space-x-2">
                                            <a href="{{ route('admin.tax-rates.edit', $rate) }}" wire:navigate
                                                class="text-blue-600 hover:text-blue-800">
                                                Edit
                                            </a>
                                            <button wire:click="delete({{ $rate->id }})"
                                                wire:confirm="Are you sure? This cannot be undone."
                                                class="text-red-600 hover:text-red-800">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No tax rates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $this->taxRates->links() }}
        </div>
    </div>
</div>