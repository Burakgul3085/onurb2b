<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Products') }}</h2>
            <div class="flex flex-wrap gap-2">
                @can('manageCatalog')
                    <a href="{{ route('brands.index') }}" class="underline text-sm text-gray-700">{{ __('Brands') }}</a>
                    <a href="{{ route('categories.index') }}" class="underline text-sm text-gray-700">{{ __('Categories') }}</a>
                    <a href="{{ route('units.index') }}" class="underline text-sm text-gray-700">{{ __('Units') }}</a>
                @endcan
                @can('create', App\Models\Product::class)
                    <a href="{{ route('products.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">{{ __('Create product') }}</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg"><p class="p-4 text-sm text-green-700">{{ session('status') }}</p></div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <form method="GET" action="{{ route('products.index') }}" class="flex gap-2">
                        <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search products') }}" />
                        <x-primary-button>{{ __('Search') }}</x-primary-button>
                    </form>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pe-4">SKU</th>
                                    <th class="py-2 pe-4">{{ __('Name') }}</th>
                                    <th class="py-2 pe-4">{{ __('Brand') }}</th>
                                    <th class="py-2 pe-4">{{ __('Category') }}</th>
                                    <th class="py-2 pe-4">{{ __('Sale price') }}</th>
                                    @if ($showsCosts)
                                        <th class="py-2 pe-4">{{ __('Purchase price') }}</th>
                                    @endif
                                    <th class="py-2 pe-4">{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr class="border-t border-gray-100">
                                        <td class="py-3 pe-4">{{ $product->sku }}</td>
                                        <td class="py-3 pe-4">{{ $product->name }}</td>
                                        <td class="py-3 pe-4">{{ $product->brand->name }}</td>
                                        <td class="py-3 pe-4">{{ $product->category->label() }}</td>
                                        <td class="py-3 pe-4">{{ \App\Support\Money\Money::format($product->sale_price) }} ₺</td>
                                        @if ($showsCosts)
                                            <td class="py-3 pe-4">{{ \App\Support\Money\Money::format($product->purchase_price) }} ₺</td>
                                        @endif
                                        <td class="py-3 pe-4">{{ $product->is_active ? __('Active') : __('Inactive') }}</td>
                                        <td class="py-3 text-right space-x-3">
                                            @can('view', $product)
                                                <a href="{{ route('products.show', $product) }}" class="underline text-gray-700">{{ __('View') }}</a>
                                            @endcan
                                            @can('update', $product)
                                                <a href="{{ route('products.edit', $product) }}" class="underline text-gray-700">{{ __('Edit') }}</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="py-6 text-gray-500">{{ __('No products found.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div>{{ $products->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
