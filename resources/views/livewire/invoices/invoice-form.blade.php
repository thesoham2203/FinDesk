<div class="max-w-6xl mx-auto px-4"> 
   <div class="mb-6">
        <h1 class="text-3xl font-bold text-black-900 dark:text-black">
            {{ $invoiceId ? 'Edit Invoice' : 'Create Invoice' }}
        </h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <!-- Invoice Header Section -->
        <div class="bg-white dark:bg-white-800 border-gray-800 rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-semibold text-black-900 dark:text-black">Invoice Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Client Select -->
                <div>
                    <label for="clientId" class="block text-sm font-medium text-black-900 dark:text-black">Client
                        *</label>
                    <select id="clientId" wire:model="clientId"
                        class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
                        <option value="">-- Select Client --</option>
                        @foreach($this->clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </select>
                    @error('clientId')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Issue Date -->
                <div>
                    <label for="issueDate" class="block text-sm font-medium text-black-900 dark:text-black">Issue Date
                        *</label>
                    <input type="date" id="issueDate" wire:model="issueDate"
                        class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
                    @error('issueDate')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Due Date -->
                <div>
                    <label for="dueDate" class="block text-sm font-medium text-black-900 dark:text-black">Due Date
                        *</label>
                    <input type="date" id="dueDate" wire:model="dueDate"
                        class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
                    @error('dueDate')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Currency -->
                <div>
                    <label for="currency"
                        class="block text-sm font-medium text-black-900 dark:text-black">Currency</label>
                    <select id="currency" wire:model="currency"
                        class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600">
                        <option value="INR">INR (₹)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-black-900 dark:text-black">Notes</label>
                <textarea id="notes" wire:model="notes" rows="3"
                    class="mt-1 w-full px-4 py-2 border border-white-300 rounded-lg dark:bg-white-700 dark:text-black dark:border-white-600"></textarea>
                @error('notes')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Line Items Section (DYNAMIC) -->
        <div class="bg-white dark:bg-white-800 rounded-lg shadow p-6 space-y-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-black-900 dark:text-black">Line Items</h2>
                <button type="button" wire:click="addLineItem"
                    class="px-4 py-2 bg-green-600 text-black rounded-lg hover:bg-green-700 transition">
                    + Add Line Item
                </button>
            </div>

            <!-- Line Items Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white-100 dark:bg-white-700 border-b dark:border-white-600">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold text-black dark:text-black">Description</th>
                            <th class="px-4 py-2 text-center font-semibold text-black dark:text-black">Quantity</th>
                            <th class="px-4 py-2 text-right font-semibold text-black dark:text-black">Unit Price</th>
                            <th class="px-4 py-2 text-left font-semibold text-black dark:text-black">Tax Rate</th>
                            <th class="px-4 py-2 text-right font-semibold text-black dark:text-black">Line Total</th>
                            <th class="px-4 py-2 text-right font-semibold text-black dark:text-black">Tax</th>
                            <th class="px-4 py-2 text-center font-semibold text-black dark:text-black">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-white-700">
                        @forelse($lineItems as $index => $item)
                            <tr class="hover:bg-white-50 dark:hover:bg-white-700 transition">
                                <!-- Description -->
                                <td class="px-4 py-2">
                                    <input type="text" wire:model.live.debounce.500ms="lineItems.{{ $index }}.description"
                                        placeholder="e.g., Web Design Services"
                                        class="w-full px-2 py-1 border border-white-300 rounded dark:bg-white-600 dark:text-black dark:border-white-500">
                                    @error("lineItems.{$index}.description")
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- Quantity -->
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" min="0.01"
                                        wire:model.live.debounce.500ms="lineItems.{{ $index }}.quantity"
                                        class="w-full px-2 py-1 border border-white-300 rounded text-center dark:bg-white-600 dark:text-black dark:border-white-500">
                                    @error("lineItems.{$index}.quantity")
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- Unit Price (in dollars) -->
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-end">
                                        <span class="mr-2 text-black-600 dark:text-black"></span>
                                        <input type="number" step="0.01" min="0.01"
                                            wire:model.live.debounce.500ms="lineItems.{{ $index }}.unit_price"
                                            class="w-full px-2 py-1 border border-white-300 rounded text-right dark:bg-white-600 dark:text-black dark:border-white-500">
                                    </div>
                                    @error("lineItems.{$index}.unit_price")
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                                    @enderror
                                </td>

                                <!-- Tax Rate -->
                                <td class="px-4 py-2">
                                    <select wire:model.live="lineItems.{{ $index }}.tax_rate_id"
                                        class="w-full px-2 py-1 border border-white-300 rounded dark:bg-white-600 dark:text-black dark:border-white-500">
                                        <option value="">No Tax</option>
                                        @foreach($this->taxRates as $rate)
                                            <option value="{{ $rate->id }}">{{ $rate->name }} ({{ $rate->percentage }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <!-- Line Total (display only) -->
                                <td class="px-4 py-2 text-right font-semibold text-black-900 dark:text-black">
                                    {{ $this->currencySymbol }}{{ number_format($item['line_total'] / 100, 2) }}
                                </td>

                                <!-- Tax Amount (display only) -->
                                <td class="px-4 py-2 text-right font-semibold text-black-900 dark:text-black">
                                    {{ $this->currencySymbol }}{{ number_format($item['tax_amount'] / 100, 2) }}
                                </td>

                                <!-- Remove Button -->
                                <td class="px-4 py-2 text-center">
                                    @if(count($lineItems) > 1)
                                        <button type="button" wire:click="removeLineItem({{ $index }})"
                                            class="text-red-600 hover:text-red-800 dark:hover:text-red-400 font-semibold">
                                            Remove
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-black-600 dark:text-black-400">
                                    No line items. Click "Add Line Item" to start.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @error('lineItems')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Totals Section -->
        <div class="bg-white dark:bg-white-800 rounded-lg shadow p-6" wire:poll.3s>
            <div class="space-y-3 max-w-sm ml-auto">
                <div class="flex justify-between">
                    <span class="text-black-700 dark:text-black-300">Subtotal:</span>
                    <span class="font-semibold text-black-900 dark:text-black">
                        {{ $this->currencySymbol }}{{ number_format($subtotal / 100, 2) }}
                    </span>
                </div>
                <div class="flex justify-between dark:text-black">
                    <span>Tax Total:</span>
                    <span>
                        {{ $this->currencySymbol }}{{ number_format($taxTotal / 100, 2) }}
                    </span>
                </div>
                
                <div class="flex justify-between text-lg border-t pt-3 dark:text-black">
                    <span>Grand Total:</span>
                    <span>
                        {{ $this->currencySymbol }}{{ number_format($grandTotal / 100, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-4">
            <button type="submit"
                class="px-6 py-2 bg-blue-600 text-black rounded-lg hover:bg-blue-700 transition font-semibold">
                {{ $invoiceId ? 'Update Invoice' : 'Create Invoice' }}
            </button>
            <a href="{{ route('invoices.index') }}"
                class="px-6 py-2 bg-white-300 text-black-900 rounded-lg hover:bg-white-400 transition dark:bg-white-600 dark:text-black">
                Cancel
            </a>
        </div>
    </form>
</div>