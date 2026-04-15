{{-- ExpenseDetail View
    WHAT: Scaffold for a single expense detail page.
    WHY: This page will host the receipt preview, status timeline, activity log, and conditional
    actions for later workflow steps such as submit, approve, reject, and delete.
    IMPLEMENT: Connect the component's loaded expense to the detailed sections and action buttons.
    KEY CONCEPTS: Route model binding, conditional rendering, livewire actions, status badges.
--}}
<div class="py-12">
    <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-gray-500">Expense Detail</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                    {{ $expense?->title ?? 'Expense detail scaffold' }}
                </h1>
            </div>

            <a href="{{ route('expenses.index') }}" wire:navigate
                class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Back to Expenses
            </a>
        </div>

        <div class="grid gap-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-semibold text-gray-900">Summary</h2>
                            @if ($expense)
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
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                    {{ $expense->status->label() }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Created {{ $expense?->created_at?->format('M d, Y h:i A') ?? 'for the detail scaffold' }}
                        </p>
                    </div>

                    @if ($expense)
                        <div class="flex flex-wrap gap-3">
                            @if ($expense->status === \App\Enums\ExpenseStatus::Draft)
                                <button type="button" wire:click="submit" wire:confirm="Submit this expense?"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                    Submit
                                </button>
                                <a href="{{ route('expenses.edit', $expense) }}" wire:navigate
                                    class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                    Edit
                                </a>
                                <button type="button" wire:click="delete" wire:confirm="Delete this expense?"
                                    class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                                    Delete
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Details</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $expense?->formatted_amount ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $expense?->category?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $expense?->department?->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Currency</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $expense?->currency?->value ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-gray-900">
                            {{ $expense?->description ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Receipt</h2>
                <div class="mt-4 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                    @if ($expense?->receipt_path)
                        <p class="font-medium text-gray-800">Receipt attached</p>
                        <p class="mt-1">Receipt preview and download handling will be added in the implementation step.</p>
                    @else
                        <p>No receipt attached.</p>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Timeline</h2>
                <div class="mt-4 space-y-3 text-sm text-gray-700">
                    <p>Submitted at: {{ $expense?->submitted_at?->format('M d, Y h:i A') ?? '-' }}</p>
                    <p>Reviewed by: {{ $expense?->reviewer?->name ?? '-' }}</p>
                    <p>Reviewed at: {{ $expense?->reviewed_at?->format('M d, Y h:i A') ?? '-' }}</p>
                    <p>Rejection reason: {{ $expense?->rejection_reason ?? '-' }}</p>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Activity Log</h2>
                <div class="mt-4 rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                    {{-- TODO: Show $expense->activities timeline here in Day 5. --}}
                    <p>Activity timeline scaffold placeholder.</p>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Review Actions</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Approve and reject actions will be added in the next day of the workflow.
                </p>
            </section>
        </div>
    </div>
</div>
