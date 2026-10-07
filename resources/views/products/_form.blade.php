@php
    $selectedCategory = old('category_id', $selectedCategoryId);
    $selectedSubcategory = old('subcategory_id', $selectedSubcategoryId);
    $includeVat = old('prices_include_vat', ($product?->prices_include_vat ?? false) ? '1' : '0');
    $pricesIncludeVat = $includeVat === true || $includeVat === 1 || $includeVat === '1';
    $barcodeText = old('barcodes', $product ? $product->barcodes->pluck('barcode')->implode("\n") : '');
@endphp

<div>
    <x-input-label for="sku" value="SKU" />
    <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" :value="old('sku', $product?->sku)" required />
    <x-input-error class="mt-2" :messages="$errors->get('sku')" />
</div>

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $product?->name)" required />
    <x-input-error class="mt-2" :messages="$errors->get('name')" />
</div>

<div>
    <x-input-label for="brand_id" :value="__('Brand')" />
    <select id="brand_id" name="brand_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
        <option value="">{{ __('Select') }}</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('brand_id')" />
</div>

<div>
    <x-input-label for="category_id" :value="__('Category')" />
    <select id="category_id" name="category_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
        <option value="">{{ __('Select') }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
</div>

<div>
    <x-input-label for="subcategory_id" :value="__('Subcategory')" />
    <select id="subcategory_id" name="subcategory_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
        <option value="">{{ __('No subcategory') }}</option>
        @foreach ($subcategories as $subcategory)
            <option value="{{ $subcategory->id }}" @selected((string) $selectedSubcategory === (string) $subcategory->id)>{{ $subcategory->label() }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('subcategory_id')" />
</div>

<div>
    <x-input-label for="unit_id" :value="__('Unit')" />
    <select id="unit_id" name="unit_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
        <option value="">{{ __('Select') }}</option>
        @foreach ($units as $unit)
            <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product?->unit_id) === (string) $unit->id)>{{ $unit->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('unit_id')" />
</div>

<div>
    <x-input-label for="vat_rate" :value="__('VAT rate')" />
    <select id="vat_rate" name="vat_rate" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>
        @foreach ($vatRates as $vatRate)
            <option value="{{ $vatRate->value }}" @selected((string) old('vat_rate', $product?->vat_rate?->value ?? '20') === $vatRate->value)>{{ $vatRate->label() }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('vat_rate')" />
</div>

<div>
    <x-input-label for="purchase_price" :value="__('Purchase price')" />
    <x-text-input id="purchase_price" name="purchase_price" type="text" class="mt-1 block w-full" :value="old('purchase_price', $product?->purchase_price)" required />
    <x-input-error class="mt-2" :messages="$errors->get('purchase_price')" />
</div>

<div>
    <x-input-label for="sale_price" :value="__('Sale price')" />
    <x-text-input id="sale_price" name="sale_price" type="text" class="mt-1 block w-full" :value="old('sale_price', $product?->sale_price)" required />
    <x-input-error class="mt-2" :messages="$errors->get('sale_price')" />
</div>

<div>
    <input type="hidden" name="prices_include_vat" value="0">
    <label class="flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="prices_include_vat" value="1" @checked($pricesIncludeVat)>
        {{ __('Prices include VAT') }}
    </label>
    <x-input-error class="mt-2" :messages="$errors->get('prices_include_vat')" />
</div>

<div>
    <x-input-label for="minimum_stock" :value="__('Minimum stock')" />
    <x-text-input id="minimum_stock" name="minimum_stock" type="number" min="0" class="mt-1 block w-full" :value="old('minimum_stock', $product?->minimum_stock ?? 0)" required />
    <x-input-error class="mt-2" :messages="$errors->get('minimum_stock')" />
</div>

<div>
    <x-input-label for="critical_stock" :value="__('Critical stock')" />
    <x-text-input id="critical_stock" name="critical_stock" type="number" min="0" class="mt-1 block w-full" :value="old('critical_stock', $product?->critical_stock ?? 0)" required />
    <x-input-error class="mt-2" :messages="$errors->get('critical_stock')" />
</div>

<div>
    <x-input-label for="barcodes" :value="__('Barcodes')" />
    <textarea id="barcodes" name="barcodes" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" placeholder="{{ __('One barcode per line') }}">{{ $barcodeText }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('barcodes')" />
</div>

<div>
    <x-input-label for="description" :value="__('Description')" />
    <textarea id="description" name="description" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">{{ old('description', $product?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div>
    <x-input-label for="image" :value="__('Image')" />
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
    <x-input-error class="mt-2" :messages="$errors->get('image')" />
</div>

@if ($product)
    @php
        $activeValue = old('is_active', $product->is_active ? '1' : '0');
        $isActive = $activeValue === true || $activeValue === 1 || $activeValue === '1';
    @endphp
    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            {{ __('Active') }}
        </label>
        <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
    </div>
@endif
