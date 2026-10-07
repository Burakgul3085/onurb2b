@php
    $movementType = old('type', 'purchase');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Record stock movement') }}</h2>
            <div class="mt-3"><x-stock-nav /></div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('stock.store') }}" class="card space-y-5 p-6 sm:p-8" x-data="{ type: @js($movementType) }">
            @csrf
            <div>
                <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                <select id="warehouse_id" name="warehouse_id" class="field mt-1" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $selectedWarehouse) === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('warehouse_id')" />
            </div>
            <div>
                <x-input-label for="product_id" :value="__('Product')" />
                <select id="product_id" name="product_id" class="field mt-1" required>
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) old('product_id', $selectedProduct) === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('product_id')" />
            </div>
            <div>
                <x-input-label for="type" :value="__('Movement type')" />
                <select id="type" name="type" class="field mt-1" x-model="type" required>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('type')" />
            </div>
            <div x-show="type === 'adjustment'" x-cloak>
                <x-input-label for="direction" :value="__('Direction')" />
                <select id="direction" name="direction" class="field mt-1">
                    <option value="in" @selected(old('direction', 'in') === 'in')>{{ __('Increase') }}</option>
                    <option value="out" @selected(old('direction') === 'out')>{{ __('Decrease') }}</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('direction')" />
            </div>
            <div x-show="type === 'transfer'" x-cloak>
                <x-input-label for="destination_warehouse_id" :value="__('Destination warehouse')" />
                <select id="destination_warehouse_id" name="destination_warehouse_id" class="field mt-1">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) old('destination_warehouse_id') === (string) $warehouse->id)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('destination_warehouse_id')" />
            </div>
            <div>
                <x-input-label for="quantity" :value="__('Quantity in pieces')" />
                <x-text-input id="quantity" name="quantity" type="number" min="0" class="mt-1 block w-full" :value="old('quantity')" required />
                <p class="mt-1 text-sm text-ink-muted" x-show="type === 'count'">{{ __('Enter the counted physical quantity.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('quantity')" />
            </div>
            <div>
                <x-input-label for="note" :value="__('Note')" />
                <textarea id="note" name="note" rows="3" class="field mt-1">{{ old('note') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('note')" />
            </div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
