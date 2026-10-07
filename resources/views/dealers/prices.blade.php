<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ $dealer->company_name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Special prices') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            @if ($canManage)
                <form method="POST" action="{{ route('dealers.prices.store', $dealer) }}" class="space-y-4">
                    @csrf
                    @include('prices._record', ['record' => null])
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            @endif
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Line discount') }}</th>
                            <th>{{ __('Minimum quantity') }}</th>
                            <th>{{ __('Valid from') }}</th>
                            <th>{{ __('Valid until') }}</th>
                            <th>{{ __('Status') }}</th>
                            @if ($canManage)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($prices as $price)
                            <tr>
                                <td>{{ $price->product->sku }} — {{ $price->product->name }}</td>
                                <td>{{ \App\Support\Money\Money::format($price->price) }} ₺</td>
                                <td>%{{ \App\Support\Money\Money::format($price->discount_percent) }}</td>
                                <td>{{ $price->minimum_quantity }} {{ __('pieces') }}</td>
                                <td>{{ $price->starts_at?->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $price->ends_at?->format('d.m.Y') ?: '—' }}</td>
                                <td><x-badge :tone="$price->is_active ? 'on' : 'off'">{{ $price->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                                @if ($canManage)
                                    <td class="text-right"><a class="link" href="{{ route('dealers.prices.edit', [$dealer, $price]) }}">{{ __('Edit') }}</a></td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-8 text-ink-muted">{{ __('No prices yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
