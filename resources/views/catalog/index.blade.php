<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Catalog') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Prices follow your quantity and are calculated on the server.') }}</p>
            </div>
            <x-primary-link :href="route('cart.index')">{{ __('Cart') }}</x-primary-link>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            <h3 class="font-semibold text-ink">{{ __('Quick order') }}</h3>
            <form method="POST" action="{{ route('catalog.quick-order') }}" class="grid gap-2 sm:grid-cols-[1fr_8rem_auto]">
                @csrf
                <x-text-input name="code" type="text" class="block w-full" :value="old('code')" placeholder="{{ __('SKU or barcode') }}" required />
                <x-text-input name="quantity" type="number" min="1" class="block w-full" :value="old('quantity', 1)" required />
                <x-primary-button>{{ __('Add to cart') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('code')" />
            <x-input-error :messages="$errors->get('quantity')" />
        </div>

        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('catalog.index') }}" class="grid gap-2 sm:grid-cols-[1fr_12rem_12rem_auto]">
                    <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search products') }}" />
                    <select name="brand_id" class="field">
                        <option value="">{{ __('All brands') }}</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected($brandId === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <select name="category_id" class="field">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Unit') }}</th>
                                <th>{{ __('Net') }}</th>
                                <th>{{ __('Gross') }}</th>
                                <th>{{ __('Stock') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                @php($quote = $quotes[$product->id])
                                <tr>
                                    <td class="font-medium">{{ $product->sku }}</td>
                                    <td>
                                        <a href="{{ route('products.show', $product) }}" class="link">{{ $product->name }}</a>
                                        <div class="text-xs text-ink-muted">{{ $product->brand->name }} · {{ $product->category->label() }}</div>
                                    </td>
                                    <td>{{ $product->unit->name }}</td>
                                    <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($quote->net) }} ₺</td>
                                    <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($quote->gross) }} ₺</td>
                                    <td>
                                        <x-badge :tone="($availability[$product->id] ?? false) ? 'on' : 'off'">
                                            {{ ($availability[$product->id] ?? false) ? __('In stock') : __('Out of stock') }}
                                        </x-badge>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('cart.store') }}" class="flex items-center justify-end gap-2">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <x-text-input name="quantity" type="number" min="1" value="1" class="w-20" required />
                                            <x-primary-button>{{ __('Add to cart') }}</x-primary-button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-ink-muted">{{ __('No products found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
