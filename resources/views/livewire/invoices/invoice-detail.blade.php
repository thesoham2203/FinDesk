<div class="space-y-6">
    @if($invoice)
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $invoice->invoice_number }}</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
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
                </p>
            </div>
            <div class="space-x-2">
                @if($invoice->status->value === 'draft')
                    <a href="{{ route('invoices.edit', $invoice) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        Edit
                    </a>
                    <button wire:click="send" wire:confirm="Send this invoice?" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Send Invoice
                    </button>
                @endif
                @if(in_array($invoice->status->value, ['draft', 'sent', 'viewed']))
                    <button wire:click="openCancelModal" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                        Cancel
                    </button>
                @endif
            </div>
        </div>

        <!-- Invoice Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Client Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Bill To</h3>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->client->name }}</p>
                <p class="text-gray-600 dark:text-gray-400">{{ $invoice->client->email }}</p>
                @if($invoice->client->phone)
                    <p class="text-gray-600 dark:text-gray-400">{{ $invoice->client->phone }}</p>
                @endif
                @if($invoice->client->address)
                    <p class="text-gray-600 dark:text-gray-400">{{ $invoice->client->address }}</p>
                @endif
                @if($invoice->client->tax_number)
                    <p class="text-gray-600 dark:text-gray-400">Tax #: {{ $invoice->client->tax_number }}</p>
                @endif
            </div>

            <!-- Invoice Info -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Invoice Information</h3>
                <div class="space-y-2 text-gray-600 dark:text-gray-400">
                    <div class="flex justify-between">
                        <span>Issue Date:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->issue_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Due Date:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->due_date->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Created By:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $invoice->creator->name }}</span>
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
                            <td class="px-6 py-3 text-right text-gray-900 dark:text-white">{{ $invoice->currency->symbol() }}{{ number_format($item->unit_price / 100, 2) }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                {{ $item->taxRate?->name ?? '-' }}
                                @if($item->taxRate)
                                    <span class="text-sm">({{ $item->taxRate->percentage }}%)</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $invoice->currency->symbol() }}{{ number_format($item->line_total / 100, 2) }}</td>
                            <td class="px-6 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ $invoice->currency->symbol() }}{{ number_format($item->tax_amount / 100, 2) }}</td>
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

        <!-- Payment History Placeholder -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment History</h3>
            <p class="text-gray-600 dark:text-gray-400">
                // TODO: Show payments list and amount due (Day 7)
            </p>
        </div>

        <!-- Activity Log -->
        @if($invoice->activities->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activity Log</h3>
                <div class="space-y-3">
                    @foreach($invoice->activities->sortByDesc('created_at') as $activity)
                        <div class="flex gap-3 pb-3 border-b dark:border-gray-700 last:border-b-0">
                            <div class="flex-shrink-0">
                                <div class="w-2 h-2 bg-blue-600 rounded-full mt-2"></div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $activity->user?->name ?? 'System' }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $activity->description }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    {{ $activity->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Cancel Modal -->
        @if($showCancelModal)
            <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Cancel Invoice</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Please provide a reason for cancelling this invoice.
                    </p>

                    <textarea wire:model="cancelReason" rows="4" 
                        placeholder="Enter cancellation reason..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 mb-4"></textarea>
                    @error('cancelReason')
                        <p class="text-sm text-red-600 dark:text-red-400 mb-4">{{ $message }}</p>
                    @enderror

                    <div class="flex gap-3 justify-end">
                        <button type="button" wire:click="$set('showCancelModal', false)" 
                            class="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition dark:bg-gray-600 dark:text-white">
                            Keep Invoice
                        </button>
                        <button type="button" wire:click="cancel" 
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            Cancel Invoice
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <div class="mb-6">
            <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:underline">← Back to Invoices</a>
        </div>
    @else
        <p class="text-gray-600 dark:text-gray-400">Invoice not found.</p>
    @endif
</div>
