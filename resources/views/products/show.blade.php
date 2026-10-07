<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $product->name }}</h2>
            @can('update', $product)
                <a href="{{ route('products.edit', $product) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Edit') }}</a>
            @endcan
        </div>
    </x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg"><p class="p-4 text-sm text-green-700">{{ session('status') }}</p></div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4 text-sm">
                    @if ($product->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="max-h-48 rounded">
                    @endif
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div><dt class="text-gray-500">SKU</dt><dd>{{ $product->sku }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Brand') }}</dt><dd>{{ $product->brand->name }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Category') }}</dt><dd>{{ $product->category->label() }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Unit') }}</dt><dd>{{ $product->unit->name }} ({{ $product->unit->pieces() }} {{ __('pieces') }})</dd></div>
                        <div><dt class="text-gray-500">{{ __('VAT rate') }}</dt><dd>{{ $product->vat_rate->label() }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd>{{ $product->is_active ? __('Active') : __('Inactive') }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-gray-500">{{ __('Barcodes') }}</dt><dd>{{ $product->barcodes->pluck('barcode')->join(', ') ?: '—' }}</dd></div>
                    </dl>
                    @php($sale = $product->saleBreakdown())
                    <div>
                        <h3 class="font-medium text-gray-800">{{ __('Sale price') }}</h3>
                        <p>{{ __('Net') }} {{ \App\Support\Money\Money::format($sale['net']) }} ₺ · {{ __('VAT') }} {{ \App\Support\Money\Money::format($sale['vat']) }} ₺ · {{ __('Gross') }} {{ \App\Support\Money\Money::format($sale['gross']) }} ₺</p>
                        <p class="text-gray-500">{{ $product->prices_include_vat ? __('Prices include VAT') : __('Prices exclude VAT') }}</p>
                    </div>
                    @if ($showsCosts)
                        @php($purchase = $product->purchaseBreakdown())
                        <div>
                            <h3 class="font-medium text-gray-800">{{ __('Purchase price') }}</h3>
                            <p>{{ __('Net') }} {{ \App\Support\Money\Money::format($purchase['net']) }} ₺ · {{ __('VAT') }} {{ \App\Support\Money\Money::format($purchase['vat']) }} ₺ · {{ __('Gross') }} {{ \App\Support\Money\Money::format($purchase['gross']) }} ₺</p>
                            <p>{{ __('Minimum stock') }}: {{ $product->minimum_stock }} · {{ __('Critical stock') }}: {{ $product->critical_stock }}</p>
                        </div>
                    @endif
                    @if ($product->description)
                        <p>{{ $product->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
