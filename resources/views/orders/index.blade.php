<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Orders') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('orders.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <select name="status" class="field sm:max-w-xs">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $item)
                            <option value="{{ $item->value }}" @selected($status === $item->value)>{{ $item->label() }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Order number') }}</th>
                                @unless (Auth::user()->dealer_id)
                                    <th>{{ __('Dealer') }}</th>
                                @endunless
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Payable') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="font-medium">{{ $order->number }}</td>
                                    @unless (Auth::user()->dealer_id)
                                        <td>{{ $order->dealer->company_name }}</td>
                                    @endunless
                                    <td><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></td>
                                    <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($order->payable) }} ₺</td>
                                    <td>{{ $order->created_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                                    <td class="text-right"><a href="{{ route('orders.show', $order) }}" class="link">{{ __('View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-8 text-ink-muted">{{ __('No orders yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $orders->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
