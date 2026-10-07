<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ __('Ledger') }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $dealer->company_name }}</h2>
            <p class="mt-3 text-lg font-semibold text-ink">{{ __('Balance') }} {{ \App\Support\Money\Money::format($balance) }} ₺</p>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Balance is the sum of the entries.') }} · {{ __('Payment term (days)') }}: {{ $dealer->payment_term_days }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <x-input-error :messages="$errors->get('ledger')" />

        @if ($canCollect)
            <form method="POST" action="{{ route('finance.collections.store', $dealer) }}" class="card space-y-4 p-6">
                @csrf
                <h3 class="font-semibold text-ink">{{ __('Record collection') }}</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" :value="__('Amount')" />
                        <x-text-input id="amount" name="amount" type="text" inputmode="decimal" class="mt-1 block w-full" :value="old('amount')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>
                    <div>
                        <x-input-label for="method" :value="__('Collection')" />
                        <select id="method" name="method" class="field mt-1" required>
                            @foreach ($methods as $method)
                                <option value="{{ $method->value }}" @selected(old('method') === $method->value)>{{ $method->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('method')" />
                    </div>
                    <div>
                        <x-input-label for="document_date" :value="__('Document date')" />
                        <x-text-input id="document_date" name="document_date" type="date" class="mt-1 block w-full" :value="old('document_date', now()->toDateString())" required />
                        <x-input-error class="mt-2" :messages="$errors->get('document_date')" />
                    </div>
                    <div>
                        <x-input-label for="note" :value="__('Note')" />
                        <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" />
                    </div>
                </div>
                <x-primary-button>{{ __('Record collection') }}</x-primary-button>
            </form>
        @endif

        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Document date') }}</th>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Order number') }}</th>
                        <th>{{ __('Debit') }}</th>
                        <th>{{ __('Credit') }}</th>
                        <th>{{ __('Due date') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td>{{ $entry->document_date->format('d.m.Y') }}</td>
                            <td>{{ $entry->number }}</td>
                            <td>
                                {{ $entry->type->label() }}
                                @if ($entry->method)
                                    <span class="text-ink-muted">· {{ $entry->method->label() }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($entry->order)
                                    <a class="link" href="{{ route('orders.show', $entry->order) }}">{{ $entry->order->number }}</a>
                                @endif
                            </td>
                            <td>{{ \App\Support\Money\Money::format($entry->debit) }} ₺</td>
                            <td>{{ \App\Support\Money\Money::format($entry->credit) }} ₺</td>
                            <td>{{ $entry->due_on?->format('d.m.Y') }}</td>
                            <td class="text-right">
                                @if ($canCollect && $entry->canBeReversed())
                                    <form method="POST" action="{{ route('finance.entries.reverse', $entry) }}">
                                        @csrf
                                        <button type="submit" class="btn-secondary">{{ __('Reverse entry') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-ink-muted">{{ __('No ledger entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $entries->links() }}</div>
        </div>
    </div>
</x-app-layout>
