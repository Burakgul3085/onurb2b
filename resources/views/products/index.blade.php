<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Products') }}</h2>
                <div class="mt-3"><x-catalog-nav /></div>
            </div>
            @can('create', App\Models\Product::class)
                <x-primary-link :href="route('products.create')">{{ __('Create product') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search products') }}" />
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Brand') }}</th>
                                <th>{{ __('Category') }}</th>
                                <th>{{ __('Sale price') }}</th>
                                @if ($showsCosts)
                                    <th>{{ __('Purchase price') }}</th>
                                @endif
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td class="font-medium">{{ $product->sku }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->brand->name }}</td>
                                    <td>{{ $product->category->label() }}</td>
                                    <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format(isset($quotes[$product->id]) ? $quotes[$product->id]->discountedPrice : $product->sale_price) }} ₺</td>
                                    @if ($showsCosts)
                                        <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($product->purchase_price) }} ₺</td>
                                    @endif
                                    <td>
                                        <x-badge :tone="$product->is_active ? 'on' : 'off'">{{ $product->is_active ? __('Active') : __('Inactive') }}</x-badge>
                                    </td>
                                    <td class="text-right">
                                        <span class="inline-flex gap-3">
                                            @can('view', $product)
                                                <a href="{{ route('products.show', $product) }}" class="link">{{ __('View') }}</a>
                                            @endcan
                                            @can('update', $product)
                                                <a href="{{ route('products.edit', $product) }}" class="link">{{ __('Edit') }}</a>
                                            @endcan
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-8 text-ink-muted">{{ __('No products found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div>{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
