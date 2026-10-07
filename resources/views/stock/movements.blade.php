<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Stock movements') }}</h2>
                <div class="mt-3"><x-stock-nav /></div>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('stock.movements') }}" class="grid gap-2 lg:grid-cols-4">
                    <select name="warehouse_id" class="field">
                        <option value="">{{ __('All warehouses') }}</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected($warehouseId === $warehouse->id)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                    <select name="product_id" class="field">
                        <option value="">{{ __('All products') }}</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected($productId === $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <select name="type" class="field">
                        <option value="">{{ __('All movement types') }}</option>
                        @foreach ($types as $movementType)
                            <option value="{{ $movementType->value }}" @selected($type === $movementType->value)>{{ $movementType->label() }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th>{{ __('Movement type') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Physical stock') }}</th>
                                <th>{{ __('Note') }}</th>
                                <th>{{ __('Recorded by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($movements as $movement)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $movement->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                                    <td>
                                        {{ $movement->warehouse->name }}
                                        @if ($movement->counterpartWarehouse)
                                            <span class="block text-xs text-ink-muted">{{ $movement->quantity < 0 ? '→' : '←' }} {{ $movement->counterpartWarehouse->name }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $movement->product->sku }} — {{ $movement->product->name }}</td>
                                    <td>{{ $movement->type->label() }}</td>
                                    <td class="whitespace-nowrap font-medium">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }} {{ __('pieces') }}</td>
                                    <td class="whitespace-nowrap">{{ $movement->physical_before }} → {{ $movement->physical_after }}</td>
                                    <td>{{ $movement->note ?: '—' }}</td>
                                    <td>{{ $movement->user->name }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-8 text-ink-muted">{{ __('No stock movements yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $movements->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
