@php
    $includeVat = old('prices_include_vat', ($record?->prices_include_vat ?? false) ? '1' : '0');
    $pricesIncludeVat = $includeVat === true || $includeVat === 1 || $includeVat === '1';
@endphp

<div>
    <x-input-label for="product_id" :value="__('Product')" />
    <select id="product_id" name="product_id" class="field mt-1" required>
        <option value="">{{ __('Select') }}</option>
        @foreach ($products as $product)
            <option value="{{ $product->id }}" @selected((string) old('product_id', $record?->product_id) === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('product_id')" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="price" :value="__('Price')" />
        <x-text-input id="price" name="price" type="text" class="mt-1 block w-full" :value="old('price', $record?->price)" required />
        <x-input-error class="mt-2" :messages="$errors->get('price')" />
    </div>
    <div>
        <x-input-label for="discount_percent" :value="__('Line discount')" />
        <x-text-input id="discount_percent" name="discount_percent" type="text" class="mt-1 block w-full" :value="old('discount_percent', $record?->discount_percent ?? '0')" required />
        <x-input-error class="mt-2" :messages="$errors->get('discount_percent')" />
    </div>
    <div>
        <x-input-label for="minimum_quantity" :value="__('Minimum quantity')" />
        <x-text-input id="minimum_quantity" name="minimum_quantity" type="number" min="1" class="mt-1 block w-full" :value="old('minimum_quantity', $record?->minimum_quantity ?? 1)" required />
        <x-input-error class="mt-2" :messages="$errors->get('minimum_quantity')" />
    </div>
    <div class="flex items-end">
        <div>
            <input type="hidden" name="prices_include_vat" value="0">
            <label class="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="prices_include_vat" value="1" @checked($pricesIncludeVat)>
                {{ __('Prices include VAT') }}
            </label>
        </div>
    </div>
    <div>
        <x-input-label for="starts_at" :value="__('Valid from')" />
        <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full" :value="old('starts_at', $record?->starts_at?->toDateString())" />
        <x-input-error class="mt-2" :messages="$errors->get('starts_at')" />
    </div>
    <div>
        <x-input-label for="ends_at" :value="__('Valid until')" />
        <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full" :value="old('ends_at', $record?->ends_at?->toDateString())" />
        <x-input-error class="mt-2" :messages="$errors->get('ends_at')" />
    </div>
</div>

@if ($record)
    @php
        $activeValue = old('is_active', $record->is_active ? '1' : '0');
        $isActive = $activeValue === true || $activeValue === 1 || $activeValue === '1';
    @endphp
    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm text-ink">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            {{ __('Active') }}
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
@endif
