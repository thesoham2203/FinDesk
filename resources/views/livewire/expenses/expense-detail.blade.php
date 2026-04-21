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
                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClasses }}">
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
                            @elseif ($expense->status === \App\Enums\ExpenseStatus::Submitted)
                                @can('approve', $expense)
                                    <button type="button" wire:click="approve" wire:confirm="Approve this expense?"
                                        class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-700 dark:text-black">
                                        Approve
                                    </button>
                                    <button type="button" wire:click="openRejectModal"
                                        class="inline-flex items-center rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                                        Reject
                                    </button>
                                @endcan
                            @elseif ($expense->status === \App\Enums\ExpenseStatus::Approved)
                                    @can('reimburse', $expense)
                                        <button type="button" wire:click="reimburse" wire:confirm="Mark this expense as reimbursed?"
                                            class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                            Mark as Reimbursed
                                        </button>
                                    @endcan
                                @can('partiallyPaid', $expense)
                                    <button type="button" wire:click="markPartiallyPaid" wire:confirm="Mark this expense as partially paid?"
                                        class="inline-flex items-center rounded-md bg-grey-600 px-4 py-2 text-sm font-medium text-black transition hover:bg-grey-700">
                                        Mark as Partially Paid
                                    </button>
                                @endcan
                            @elseif ($expense->status === \App\Enums\ExpenseStatus::PartiallyPaid)
                                @can('reimburse', $expense)
                                    <button type="button" wire:click="reimburse" wire:confirm="Mark this expense as fully reimbursed?"
                                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                                        Mark as Reimbursed
                                    </button>
                                @endcan
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
                <h2 class="text-lg font-semibold text-gray-900">Receipt Attachment</h2>
                
                @if ($expense?->attachments->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        @foreach ($expense->attachments as $attachment)
                            <div class="flex items-center justify-between rounded-md border border-gray-200 p-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $attachment->original_name }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($attachment->size / 1024, 2) }} KB</p>
                                </div>
                                <button type="button" wire:click="downloadAttachment({{ $attachment->id }})" wire:loading.attr="disabled"
                                    class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                                    <span wire:loading.remove>Download</span>
                                    <span wire:loading>Downloading...</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 text-sm text-gray-600">No attachments</p>
                @endif
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
                <div class="mt-4">
                    @if ($expense?->activities->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($expense->activities as $activity)
                                <div class="flex gap-4 border-l-2 border-gray-200 py-3 pl-4">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                            <span
                                                class="text-xs font-semibold text-gray-600">{{ substr($activity->user->name, 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="font-medium text-gray-900">{{ $activity->user->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $activity->description }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ $activity->created_at?->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-md border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                            <p>No activities yet.</p>
                        </div>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">Review Actions</h2>
                <p class="mt-2 text-sm text-gray-600">
                    @if ($showRejectModal)
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                            <div class="rounded-lg bg-white p-6 shadow-lg">
                                <h3 class="text-lg font-semibold text-gray-900">Reject Expense</h3>
                                <p class="mt-2 text-sm text-gray-600">Please provide a reason for rejection:</p>
                                <textarea wire:model="rejectionReason"
                                    class="mt-4 w-full rounded-md border border-gray-300 p-3 text-sm" rows="4"
                                    placeholder="Enter rejection reason..."></textarea>
                                <div class="mt-4 flex gap-3">
                                    <button type="button" wire:click="reject"
                                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700">
                                        Reject
                                    </button>
                                    <button type="button" wire:click="$set('showRejectModal', false)"
                                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </p>
            </section>
        </div>
    </div>
</div>