<div class="space-y-6">
    @if($invoice)
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    <span class="px-3 py-1 rounded-full text-sm font-semibold
                            {{ match ($invoice->status->color()) {
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
                </p>
            </div>
            <div class="space-x-2">
                @if($invoice->status->value === 'sent')
                    <button wire:click="markAsViewed" wire:confirm="Mark this invoice as viewed?"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Mark as Viewed
                    </button>
                @endif
                <button wire:click="downloadPdf"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    Download PDF
                </button>
            </div>
        </div>

        <!-- Invoice Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Client Info (Your Company) -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">From</h3>
                <p class="font-semibold text-gray-900 dark:text-white">{{ config('app.name') }}</p>
            </div>

            <!-- Invoice Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Invoice Information</h3>
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between">
                        <span>Issue Date:</span>
                        <span
                            class="font-semibold text-gray-900 dark:text-white">{{ $invoice->issue_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Due Date:</span>
                        <span
                            class="font-semibold text-gray-900 dark:text-white">{{ $invoice->due_date->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 dark:bg-gray-700 border-b dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">#</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Description</th>
                        <th class="px-6 py-3 text-center font-semibold text-gray-900 dark:text-white">Quantity</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">Unit Price</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-900 dark:text-white">Tax Rate</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">Line Total</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">Tax</th>
                    </tr>
                </thead>
                <tbody class="divide-y dark:divide-gray-700">
                    @foreach($invoice->lineItems as $index => $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-6 py-3 text-gray-900 dark:text-white">{{ $item->description }}</td>
                            <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-400">{{ $item->quantity }}</td>
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">
                                {{ $invoice->currency->symbol() }}{{ number_format($item->unit_price / 100, 2) }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                {{ $item->taxRate?->name ?? '-' }}
                                @if($item->taxRate)
                                    <span class="text-sm">({{ $item->taxRate->percentage }}%)</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                {{ $invoice->currency->symbol() }}{{ number_format($item->line_total / 100, 2) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                {{ $invoice->currency->symbol() }}{{ number_format($item->tax_amount / 100, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full max-w-sm">
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Subtotal:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->formatted_subtotal }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Tax Total:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->formatted_tax_total }}</span>
                    </div>
                    <div class="flex justify-between text-lg border-t dark:border-gray-700 pt-3">
                        <span class="font-bold text-gray-900 dark:text-white">Grand Total:</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $invoice->formatted_total }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mb-6">
            <a href="{{ route('client.invoices.index') }}" class="text-blue-600 hover:underline">
                <- Back to Invoices
            </a>
        </div>
    @else
        <p class="text-gray-600 dark:text-gray-400">Invoice not found.</p>
    @endif
</div>
