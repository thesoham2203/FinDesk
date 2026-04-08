<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Create Expense</h1>
        <p class="text-gray-600 text-sm mb-6">Submit a new expense request for reimbursement</p>

        @if (session()->has('success'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="submit" class="space-y-4">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    Title <span class="text-red-500">*</span>
                </label>
                <input
                    id="title"
                    type="text"
                    wire:model="title"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="e.g., Flight to Mumbai"
                />
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label for="categoryId" class="block text-sm font-medium text-gray-700 mb-1">
                    Category <span class="text-red-500">*</span>
                </label>
                <select
                    id="categoryId"
                    wire:model.live="categoryId"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="0">-- Select Category --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">
                            {{ $cat->name }}
                            @if ($cat->max_amount !== null)
                                (Max: {{ $cat->max_amount }})
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('categoryId')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label for="amountInput" class="block text-sm font-medium text-gray-700 mb-1">
                    Amount (₹) <span class="text-red-500">*</span>
                </label>
                <input
                    id="amountInput"
                    type="number"
                    step="0.01"
                    wire:model="amountInput"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0.00"
                />
                @error('amountInput')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description
                </label>
                <textarea
                    id="description"
                    wire:model="description"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Additional details..."
                    rows="3"
                ></textarea>
                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Receipt Upload -->
            @if ($requiresReceipt)
                <div>
                    <label for="receipt" class="block text-sm font-medium text-gray-700 mb-1">
                        Receipt <span class="text-red-500">* (Required)</span>
                    </label>
                    <input
                        id="receipt"
                        type="file"
                        wire:model="receipt"
                        accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    @error('receipt')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Submit Button -->
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full mt-6 px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
                <span wire:loading.remove>Save Expense</span>
                <span wire:loading>
                    <span class="inline-block mr-2">Processing...</span>
                </span>
            </button>
        </form>
    </div>
</div>
