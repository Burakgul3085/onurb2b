<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Ledger') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Balance is the sum of the entries.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card p-6">
            <h3 class="font-semibold text-ink">{{ __('Upcoming due dates') }}</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Dealer') }}</th>
                            <th>{{ __('Document date') }}</th>
                            <th>{{ __('Due date') }}</th>
                            <th>{{ __('Debit') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dues as $entry)
                            <tr>
                                <td>{{ $entry->dealer->company_name }}</td>
                                <td>{{ $entry->document_date->format('d.m.Y') }}</td>
                                <td>{{ $entry->due_on->format('d.m.Y') }}</td>
                                <td>{{ \App\Support\Money\Money::format($entry->debit) }} ₺</td>
                                <td class="text-right"><a class="link" href="{{ route('finance.show', $entry->dealer_id) }}">{{ __('View') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-ink-muted">{{ __('No upcoming due dates.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('finance.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <x-text-input name="q" type="search" :value="$search" placeholder="{{ __('Dealer') }}" />
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Dealer') }}</th>
                                <th>{{ __('Balance') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dealers as $dealer)
                                <tr>
                                    <td class="font-medium">{{ $dealer->company_name }}</td>
                                    <td>{{ \App\Support\Money\Money::format($balances[$dealer->id] ?? '0.00') }} ₺</td>
                                    <td class="text-right"><a class="link" href="{{ route('finance.show', $dealer) }}">{{ __('View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-ink-muted">{{ __('No ledger entries yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $dealers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
