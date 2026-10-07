<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Deliveries') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Eskişehir only. There is no cargo.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('deliveries.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <x-text-input name="date" type="date" :value="$date" />
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Sequence') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Order number') }}</th>
                                <th>{{ __('Dealer') }}</th>
                                <th>{{ __('Delivery person') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deliveries as $delivery)
                                <tr>
                                    <td>{{ $delivery->sequence }}</td>
                                    <td>{{ $delivery->scheduled_on->format('d.m.Y') }} {{ $delivery->scheduled_time ? substr((string) $delivery->scheduled_time, 0, 5) : '' }}</td>
                                    <td>{{ $delivery->order->number }}</td>
                                    <td>{{ $delivery->dealer->company_name }}</td>
                                    <td>{{ $delivery->driver->name }}</td>
                                    <td><x-badge :tone="$delivery->status->tone()">{{ $delivery->status->label() }}</x-badge></td>
                                    <td class="text-right"><a class="link" href="{{ route('deliveries.show', $delivery) }}">{{ __('View') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-ink-muted">{{ __('No deliveries yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $deliveries->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
