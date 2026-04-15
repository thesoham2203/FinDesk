{{-- ExpenseForm View
    WHAT: Scaffold for the create/edit expense form.
    WHY: This screen introduces file uploads, category-aware hints, and the create-versus-submit
    split that powers the expense lifecycle.
    IMPLEMENT: Connect validation, upload previews, category rules, and action calls in the next step.
    KEY CONCEPTS: Livewire file uploads, form binding, money input in dollars, conditional UI.
--}}
<div class="py-12">
    <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-3xl font-semibold tracking-tight text-gray-900">
                {{ $expenseId ? 'Edit Expense' : 'Create Expense' }}
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                Draft expenses can be saved now and submitted once they are ready.
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <label for="title" class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                    <input id="title" type="text" wire:model="title" placeholder="e.g. Client lunch"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea id="description" wire:model="description" rows="4" placeholder="Optional notes about the expense..."
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="categoryId" class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <select id="categoryId" wire:model.live="categoryId"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <option value="">Choose a category</option>
                        @foreach ($this->categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('categoryId')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="mt-3 rounded-md bg-gray-50 p-4 text-sm text-gray-600">
                        @if ($this->selectedCategory)
                            <p class="font-medium text-gray-800">{{ $this->selectedCategory->name }}</p>
                            <p class="mt-1">Category-specific rules will appear here once implemented.</p>
                        @else
                            <p>Select a category to see max amount and receipt requirements.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="amount" class="mb-1 block text-sm font-medium text-gray-700">Amount</label>
                        <input id="amount" type="number" step="0.01" wire:model="amount" placeholder="0.00"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                        <p class="mt-1 text-xs text-gray-500">Enter the amount in dollars. The model will store cents.</p>
                        @error('amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="currency" class="mb-1 block text-sm font-medium text-gray-700">Currency</label>
                        <select id="currency" wire:model="currency"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="INR">INR</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                        @error('currency')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="receipt" class="mb-1 block text-sm font-medium text-gray-700">Receipt</label>
                    <input id="receipt" type="file" wire:model="receipt" accept=".jpg,.jpeg,.png,.pdf"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, or PDF files only.</p>

                    <div class="mt-2 text-sm text-gray-600" wire:loading wire:target="receipt">
                        Uploading receipt...
                    </div>

                    @if ($existingReceiptPath)
                        <p class="mt-2 text-sm text-gray-600">Current receipt: {{ basename($existingReceiptPath) }}</p>
                    @endif

                    @if ($receipt)
                        <div class="mt-3 rounded-md border border-dashed border-gray-300 p-3 text-sm text-gray-600">
                            <p>Temporary upload selected: {{ $receipt->getClientOriginalName() }}</p>
                            <p class="mt-1">Image preview and receipt replacement will be implemented later.</p>
                        </div>
                    @endif

                    @error('receipt')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="date" class="mb-1 block text-sm font-medium text-gray-700">Date</label>
                    <input id="date" type="date" wire:model="date"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                
                    @error('date')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button type="button" wire:click="save(false)"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        <span wire:loading.remove wire:target="save" >Save as Draft</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>

                    <button type="button" wire:click="save(true)"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        <span wire:loading.remove wire:target="save">Save &amp; Submit</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>

                    <a href="{{ route('expenses.index') }}" wire:navigate
                        class="inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-gray-900">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
