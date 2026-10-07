<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-ink">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brass text-xs font-bold text-white">OB</span>
                    <span class="hidden font-semibold tracking-tight text-white sm:block">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-1 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    @can('viewAny', App\Models\User::class)
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Users') }}
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Dealer::class)
                        <x-nav-link :href="route('dealers.index')" :active="request()->routeIs('dealers.*') && ! Auth::user()->dealer_id">
                            {{ __('Dealers') }}
                        </x-nav-link>
                    @endcan
                    @if (Auth::user()->dealer_id)
                        <x-nav-link :href="route('dealers.show', Auth::user()->dealer_id)" :active="request()->routeIs('dealers.show')">
                            {{ __('My company') }}
                        </x-nav-link>
                    @endif
                    @can('shop')
                        <x-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">
                            {{ __('Catalog') }}
                        </x-nav-link>
                        <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*')">
                            {{ __('Cart') }}@if (($cartCount ?? 0) > 0) ({{ $cartCount }})@endif
                        </x-nav-link>
                    @elsecan('viewAny', App\Models\Product::class)
                        <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*', 'brands.*', 'categories.*', 'units.*')">
                            {{ __('Products') }}
                        </x-nav-link>
                    @endcan
                    @can('viewStock')
                        <x-nav-link :href="route('stock.index')" :active="request()->routeIs('stock.*', 'warehouses.*')">
                            {{ __('Stock') }}
                        </x-nav-link>
                    @endcan
                    @can('viewAny', App\Models\Order::class)
                        <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                            {{ __('Orders') }}
                        </x-nav-link>
                    @endcan
                    @can('viewPrices')
                        <x-nav-link :href="route('price-lists.index')" :active="request()->routeIs('price-lists.*')">
                            {{ __('Price lists') }}
                        </x-nav-link>
                    @endcan
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-stone-200 hover:bg-white/10 focus:outline-none">
                            <span class="max-w-[12rem] truncate">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg p-2 text-stone-300 hover:bg-white/10 hover:text-white focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 px-3 py-3 sm:hidden">
        <div class="space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            @can('viewAny', App\Models\User::class)
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                    {{ __('Users') }}
                </x-responsive-nav-link>
            @endcan
            @can('viewAny', App\Models\Dealer::class)
                <x-responsive-nav-link :href="route('dealers.index')" :active="request()->routeIs('dealers.*') && ! Auth::user()->dealer_id">
                    {{ __('Dealers') }}
                </x-responsive-nav-link>
            @endcan
            @if (Auth::user()->dealer_id)
                <x-responsive-nav-link :href="route('dealers.show', Auth::user()->dealer_id)" :active="request()->routeIs('dealers.show')">
                    {{ __('My company') }}
                </x-responsive-nav-link>
            @endif
            @can('shop')
                <x-responsive-nav-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*')">
                    {{ __('Catalog') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*')">
                    {{ __('Cart') }}@if (($cartCount ?? 0) > 0) ({{ $cartCount }})@endif
                </x-responsive-nav-link>
            @elsecan('viewAny', App\Models\Product::class)
                <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.*', 'brands.*', 'categories.*', 'units.*')">
                    {{ __('Products') }}
                </x-responsive-nav-link>
            @endcan
            @can('viewAny', App\Models\Order::class)
                <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                    {{ __('Orders') }}
                </x-responsive-nav-link>
            @endcan
            @can('viewStock')
                <x-responsive-nav-link :href="route('stock.index')" :active="request()->routeIs('stock.*', 'warehouses.*')">
                    {{ __('Stock') }}
                </x-responsive-nav-link>
            @endcan
            @can('viewPrices')
                <x-responsive-nav-link :href="route('price-lists.index')" :active="request()->routeIs('price-lists.*')">
                    {{ __('Price lists') }}
                </x-responsive-nav-link>
            @endcan
        </div>

        <div class="mt-3 space-y-1 border-t border-white/10 pt-3">
            <div class="px-3 pb-2">
                <div class="text-sm font-semibold text-white">{{ Auth::user()->name }}</div>
                <div class="text-xs text-stone-400">{{ Auth::user()->email }}</div>
            </div>
            <x-responsive-nav-link :href="route('profile.edit')">
                {{ __('Profile') }}
            </x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                    {{ __('Log Out') }}
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
