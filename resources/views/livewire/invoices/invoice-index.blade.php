<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Invoices</h1>
        <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + Create Invoice
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <input type="text" 
                wire:model.live="search" 
                placeholder="Search invoice #..." 
                class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">

            <!-- Status Filter -->
            <select wire:model.live="statusFilter" 
                class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                <option value="">All Statuses</option>
                @foreach(\App\Enums\InvoiceStatus::cases() as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>

            <!-- Client Filter -->
            <select wire:model.live="clientFilter" 
                class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                <option value="">All Clients</option>
                @foreach($this->clients as $client)
                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                @endforeach
            </select>

            <!-- Date From -->
            <input type="date" 
                wire:model.live="dateFrom" 
                class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">

            <!-- Date To -->
            <input type="date" 
                wire:model.live="dateTo" 
                class="px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Invoice #</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Client</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Status</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Issue Date</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Due Date</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">Total</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-900 dark:text-white">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-gray-700">
                @forelse($this->invoices as $invoice)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-3 font-mono text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                        <td class="px-6 py-3 text-gray-900 dark:text-white">{{ $invoice->client->name }}</td>
                        <td class="px-6 py-3">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold
                                {{ match($invoice->status->color()) {
                                    'gray' => 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
                                    'blue' => 'bg-blue-200 text-blue-800 dark:bg-blue-600 dark:text-blue-200',
                                    'purple' => 'bg-purple-200 text-purple-800 dark:bg-purple-600 dark:text-purple-200',
                                    'yellow' => 'bg-yellow-200 text-yellow-800 dark:bg-yellow-600 dark:text-yellow-200',
                                    'green' => 'bg-green-200 text-green-800 dark:bg-green-600 dark:text-green-200',
                                    'red' => 'bg-red-200 text-red-800 dark:bg-red-600 dark:text-red-200',
                                    'black' => 'bg-black text-white dark:bg-gray-700',
                                    default => 'bg-gray-200 text-gray-800',
                                } }}">
                                {{ $invoice->status->label() }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $invoice->issue_date->format('M d, Y') }}</td>
                        <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $invoice->due_date->format('M d, Y') }}</td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $invoice->formatted_total }}</td>
                        <td class="px-6 py-3 text-center space-x-2">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-blue-600 hover:underline">View</a>
                            @if($invoice->status->value === 'draft')
                                <a href="{{ route('invoices.edit', $invoice) }}" class="text-green-600 hover:underline">Edit</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-600 dark:text-gray-400">
                            No invoices found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div>
        {{ $this->invoices->links() }}
    </div>
</div>
