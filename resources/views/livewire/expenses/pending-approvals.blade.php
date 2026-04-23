<div class="py-12" wire:poll.30s="$refresh">
    <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-gray-500">Approval Queue</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
                    Pending Approvals
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span
                    class="inline-flex items-center rounded-full bg-yellow-100 px-4 py-2 text-sm font-medium text-yellow-800">
                    {{ $this->pendingCount }} pending
                </span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($this->pendingExpenses->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Employee
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Expense
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Category
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Submitted
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($this->pendingExpenses as $expense)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $expense->user->name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $expense->title }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $expense->category->name }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                        {{ $expense->formattedAmount }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $expense->submitted_at?->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('expenses.show', $expense) }}" wire:navigate
                                                class="rounded px-3 py-1 text-sm font-medium text-blue-600 hover:bg-blue-50">
                                                View
                                            </a>
                                            <button type="button" wire:click="approve({{ $expense->id }})"
                                                wire:confirm="Approve this expense?"
                                                class="rounded bg-green-600 px-3 py-1 text-sm font-medium text-white transition hover:bg-green-700">
                                                Approve
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="border-t border-gray-200 px-6 py-4">
                    {{ $this->pendingExpenses->links() }}
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No pending approvals</h3>
                    <p class="mt-2 text-sm text-gray-500">All expenses in your department are up to date!</p>
                </div>
            @endif
        </div>
    </div>
</div>