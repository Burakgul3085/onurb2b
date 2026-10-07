<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-ink-muted">{{ $product->sku }} · {{ $product->brand->name }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $product->name }}</h2>
                <div class="mt-3"><x-catalog-nav /></div>
            </div>
            @can('update', $product)
                <x-primary-link :href="route('products.edit', $product)">{{ __('Edit') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="grid gap-4 lg:grid-cols-[16rem_1fr]">
            <div class="card flex items-center justify-center bg-stone-50 p-6">
                @if ($product->image_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="max-h-56 rounded-xl object-contain">
                @else
                    <div class="flex h-40 w-full items-center justify-center rounded-xl border border-dashed border-stone-300 text-sm text-ink-muted">{{ __('Image') }}</div>
                @endif
            </div>

            <div class="card p-6">
                <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">SKU</dt><dd class="mt-1 font-medium">{{ $product->sku }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Brand') }}</dt><dd class="mt-1 font-medium">{{ $product->brand->name }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Category') }}</dt><dd class="mt-1 font-medium">{{ $product->category->label() }}</dd></div>
                    <div><dt class="text-ink-muted">{{ __('Unit') }}</dt><dd class="mt-1 font-medium">{{ $product->unit->name }} ({{ $product->unit->pieces() }} {{ __('pieces') }})</dd></div>
                    <div><dt class="text-ink-muted">{{ __('VAT rate') }}</dt><dd class="mt-1 font-medium">{{ $product->vat_rate->label() }}</dd></div>
                    <div>
                        <dt class="text-ink-muted">{{ __('Status') }}</dt>
                        <dd class="mt-1"><x-badge :tone="$product->is_active ? 'on' : 'off'">{{ $product->is_active ? __('Active') : __('Inactive') }}</x-badge></dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-ink-muted">{{ __('Barcodes') }}</dt>
                        <dd class="mt-1 font-medium">{{ $product->barcodes->pluck('barcode')->join(', ') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        @php($sale = isset($quote) ? ['net' => $quote->net, 'vat' => $quote->vat, 'gross' => $quote->gross] : $product->saleBreakdown())
        @php($saleIncludesVat = isset($quote) ? $quote->includesVat : $product->prices_include_vat)
        <div class="grid gap-4 {{ $showsCosts ? 'lg:grid-cols-2' : '' }}">
            <div class="card p-6">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-ink">{{ isset($quote) ? $quote->source->label() : __('Sale price') }}</h3>
                    <span class="text-xs text-ink-muted">{{ $saleIncludesVat ? __('Prices include VAT') : __('Prices exclude VAT') }}</span>
                </div>
                @if (isset($quote) && bccomp($quote->discountPercent, '0', 2) === 1)
                    <p class="mt-2 text-sm text-ink-muted">{{ __('Line discount') }} %{{ \App\Support\Money\Money::format($quote->discountPercent) }}</p>
                @endif
                @if (isset($quote) && bccomp($quote->documentDiscountPercent, '0', 2) === 1)
                    <p class="mt-1 text-sm text-ink-muted">{{ __('Document discount is applied on the order, not in this unit price.') }} %{{ \App\Support\Money\Money::format($quote->documentDiscountPercent) }}</p>
                @endif
                @can('shop')
                    <form method="POST" action="{{ route('cart.store') }}" class="mt-4 flex flex-wrap items-end gap-2">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <div>
                            <x-input-label for="quantity" :value="__('Quantity')" />
                            <x-text-input id="quantity" name="quantity" type="number" min="1" value="1" class="mt-1 w-24" required />
                        </div>
                        <x-primary-button>{{ __('Add to cart') }}</x-primary-button>
                    </form>
                @endcan
                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="stat"><p class="text-xs text-ink-muted">{{ __('Net') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($sale['net']) }} ₺</p></div>
                    <div class="stat"><p class="text-xs text-ink-muted">{{ __('VAT') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($sale['vat']) }} ₺</p></div>
                    <div class="stat"><p class="text-xs text-ink-muted">{{ __('Gross') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($sale['gross']) }} ₺</p></div>
                </div>
            </div>
            @if ($showsCosts)
                @php($purchase = $product->purchaseBreakdown())
                <div class="card p-6">
                    <h3 class="font-semibold text-ink">{{ __('Purchase price') }}</h3>
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div class="stat"><p class="text-xs text-ink-muted">{{ __('Net') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($purchase['net']) }} ₺</p></div>
                        <div class="stat"><p class="text-xs text-ink-muted">{{ __('VAT') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($purchase['vat']) }} ₺</p></div>
                        <div class="stat"><p class="text-xs text-ink-muted">{{ __('Gross') }}</p><p class="mt-1 font-semibold">{{ \App\Support\Money\Money::format($purchase['gross']) }} ₺</p></div>
                    </div>
                    <p class="mt-4 text-sm text-ink-muted">{{ __('Minimum stock') }}: {{ $product->minimum_stock }} · {{ __('Critical stock') }}: {{ $product->critical_stock }}</p>
                </div>
            @endif
        </div>

        @if ($product->description)
            <div class="card p-6 text-sm leading-6 text-ink">{{ $product->description }}</div>
        @endif
    </div>
</x-app-layout>
