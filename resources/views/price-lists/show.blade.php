<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-ink-muted">{{ __('Document discount') }} %{{ \App\Support\Money\Money::format($priceList->document_discount_percent) }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $priceList->name }}</h2>
            </div>
            @can('update', $priceList)
                <x-primary-link :href="route('price-lists.edit', $priceList)">{{ __('Edit') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            @if ($canManage)
                <form method="POST" action="{{ route('price-lists.items.store', $priceList) }}" class="space-y-4">
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
                        @forelse ($priceList->items as $item)
                            <tr>
                                <td>{{ $item->product->sku }} — {{ $item->product->name }}</td>
                                <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($item->price) }} ₺</td>
                                <td>%{{ \App\Support\Money\Money::format($item->discount_percent) }}</td>
                                <td>{{ $item->minimum_quantity }} {{ __('pieces') }}</td>
                                <td>{{ $item->starts_at?->format('d.m.Y') ?: '—' }}</td>
                                <td>{{ $item->ends_at?->format('d.m.Y') ?: '—' }}</td>
                                <td><x-badge :tone="$item->is_active ? 'on' : 'off'">{{ $item->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                                @if ($canManage)
                                    <td class="text-right"><a href="{{ route('price-lists.items.edit', [$priceList, $item]) }}" class="link">{{ __('Edit') }}</a></td>
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
