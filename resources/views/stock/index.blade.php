<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Stock') }}</h2>
                <div class="mt-3"><x-stock-nav /></div>
            </div>
            @if ($canAdjust && $warehouse)
                <x-primary-link :href="route('stock.create', ['warehouse_id' => $warehouse->id])">{{ __('Record stock movement') }}</x-primary-link>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                @if ($warehouses->isEmpty())
                    <p class="text-sm text-ink-muted">{{ __('No active warehouse.') }}</p>
                @else
                    <form method="GET" action="{{ route('stock.index') }}" class="flex flex-col gap-2 lg:flex-row">
                        <select name="warehouse_id" class="field lg:max-w-xs">
                            @foreach ($warehouses as $option)
                                <option value="{{ $option->id }}" @selected($warehouse?->id === $option->id)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search by name or SKU') }}" />
                        <select name="alert" class="field lg:max-w-xs">
                            <option value="">{{ __('All stock') }}</option>
                            <option value="critical" @selected($alert === 'critical')>{{ __('Critical stock') }}</option>
                            <option value="low" @selected($alert === 'low')>{{ __('Below minimum') }}</option>
                        </select>
                        <x-primary-button>{{ __('Search') }}</x-primary-button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Physical stock') }}</th>
                                    <th>{{ __('Reserved stock') }}</th>
                                    <th>{{ __('Available stock') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    @if ($canAdjust)
                                        <th></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    @php
                                        $physical = (int) $product->physical_stock;
                                        $reserved = (int) $product->reserved_stock;
                                        $available = $physical - $reserved;
                                        $critical = $product->critical_stock > 0 && $available <= $product->critical_stock;
                                        $low = $product->minimum_stock > 0 && $available <= $product->minimum_stock;
                                    @endphp
                                    <tr>
                                        <td class="font-medium">{{ $product->sku }}</td>
                                        <td>{{ $product->name }}</td>
                                        <td>{{ $physical }} {{ __('pieces') }}</td>
                                        <td>{{ $reserved }} {{ __('pieces') }}</td>
                                        <td class="font-medium">{{ $available }} {{ __('pieces') }}</td>
                                        <td>
                                            @if ($critical)
                                                <x-badge tone="off">{{ __('Critical stock') }}</x-badge>
                                            @elseif ($low)
                                                <x-badge tone="wait">{{ __('Below minimum') }}</x-badge>
                                            @else
                                                <x-badge tone="on">{{ __('Sufficient') }}</x-badge>
                                            @endif
                                        </td>
                                        @if ($canAdjust)
                                            <td class="text-right">
                                                <a href="{{ route('stock.create', ['warehouse_id' => $warehouse->id, 'product_id' => $product->id]) }}" class="link">{{ __('Record stock movement') }}</a>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="py-8 text-ink-muted">{{ __('No products found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div>{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
