<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-brass">{{ Auth::user()->name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Dashboard') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pb-10 sm:px-6 lg:px-8">
        <div class="card">
            <div class="flex flex-col gap-2 p-6 sm:p-8">
                <p class="text-lg font-semibold text-ink">{{ __("You're logged in!") }}</p>
                <p class="text-sm text-ink-muted">
                    {{ Auth::user()->roleLabels() !== '' ? Auth::user()->roleLabels() : __('No role assigned') }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @can('viewAny', App\Models\User::class)
                <a href="{{ route('users.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Users') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Staff accounts and roles') }}</p>
                </a>
            @endcan
            @can('viewAny', App\Models\Dealer::class)
                <a href="{{ route('dealers.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Dealers') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Dealer firms and applications') }}</p>
                </a>
            @endcan
            @if (Auth::user()->dealer_id)
                <a href="{{ route('dealers.show', Auth::user()->dealer_id) }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('My company') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Company profile') }}</p>
                </a>
            @endif
            @can('shop')
                <a href="{{ route('catalog.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Catalog') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Browse products and order quickly') }}</p>
                </a>
                <a href="{{ route('cart.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Cart') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Quantities, prices, and delivery address') }}</p>
                </a>
            @elsecan('viewAny', App\Models\Product::class)
                <a href="{{ route('products.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Products') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Catalog, prices, and barcodes') }}</p>
                </a>
            @endcan
            @can('viewAny', App\Models\Delivery::class)
                <a href="{{ route('deliveries.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Deliveries') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Eskişehir delivery route') }}</p>
                </a>
            @endcan
            @can('viewAny', App\Models\Order::class)
                <a href="{{ route('orders.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Orders') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Pending, approved, and cancelled orders') }}</p>
                </a>
            @endcan
            @can('viewStock')
                <a href="{{ route('stock.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Stock') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Warehouses and piece balances') }}</p>
                </a>
            @endcan
            @can('viewPrices')
                <a href="{{ route('price-lists.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Price lists') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Lists, special prices, and discounts') }}</p>
                </a>
            @endcan
        </div>
    </div>
</x-app-layout>
