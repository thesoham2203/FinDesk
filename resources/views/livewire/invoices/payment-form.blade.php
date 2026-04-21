<?php

use App\Enums\PaymentMethod;
use Livewire\Volt\Component;

?>

<div class="bg-white shadow rounded-lg p-6 mt-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-6">Record Payment</h3>

    <!-- Balance Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6 bg-gray-50 p-4 rounded-lg">
        <div>
            <p class="text-sm text-gray-600">Invoice Total</p>
            <p class="text-xl font-bold text-gray-900">₹{{ number_format($invoiceTotal / 100, 2) }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Total Paid</p>
            <p class="text-xl font-bold text-green-600">₹{{ number_format($totalPaid / 100, 2) }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Remaining Balance</p>
            <p class="text-xl font-bold {{ $remaining > 0 ? 'text-red-600' : 'text-green-600' }}">
                ₹{{ number_format($remaining / 100, 2) }}
            </p>
        </div>
    </div>

    <!-- Payment Form -->
    @if ($remaining > 0)
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Amount (₹)</label>
                    <input type="number" id="amount" wire:model="amount" step="0.01" min="0.01" max="{{ $remaining / 100 }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        placeholder="0.00" />
                    <p class="mt-1 text-xs text-gray-500">Maximum: ₹{{ number_format($remaining / 100, 2) }}</p>
                    @error('amount')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Date -->
                <div>
                    <label for="paymentDate" class="block text-sm font-medium text-gray-700">Payment Date</label>
                    <input type="date" id="paymentDate" wire:model="paymentDate"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
                    @error('paymentDate')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Payment Method -->
                <div>
                    <label for="paymentMethod" class="block text-sm font-medium text-gray-700">Payment Method</label>
                    <select id="paymentMethod" wire:model="paymentMethod"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Select Method</option>
                        @foreach ($this->paymentMethods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                    @error('paymentMethod')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reference Number -->
                <div>
                    <label for="referenceNumber" class="block text-sm font-medium text-gray-700">Reference Number
                        (Optional)</label>
                    <input type="text" id="referenceNumber" wire:model="referenceNumber"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                        placeholder="e.g., Cheque #12345" />
                    @error('referenceNumber')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                <textarea id="notes" wire:model="notes" rows="2"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="Additional payment details..."></textarea>
                @error('notes')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Quick Pay Buttons -->
            <div class="flex gap-2">
                <button type="button" wire:click="$set('amount', {{ ($remaining / 100) }})"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Pay Full Remaining
                </button>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4">
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50">
                    <span wire:loading.remove>Record Payment</span>
                    <span wire:loading>Processing...</span>
                </button>
            </div>
        </form>
    @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-green-800 text-sm font-medium">✓ Invoice is fully paid. No further payments needed.</p>
        </div>
    @endif
</div>