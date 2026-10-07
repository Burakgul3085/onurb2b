@php
    $user = Auth::user();

    $link = function (string $label, string $href, bool $active): array {
        return ['label' => $label, 'href' => $href, 'active' => $active];
    };

    $sales = [];

    if ($user->can('viewAny', App\Models\Dealer::class) && ! $user->dealer_id) {
        $sales[] = $link(__('Dealers'), route('dealers.index'), request()->routeIs('dealers.*'));
    }

    if ($user->can('viewAny', App\Models\Order::class)) {
        $sales[] = $link(__('Orders'), route('orders.index'), request()->routeIs('orders.*'));
    }

    if ($user->can('viewAny', App\Models\Delivery::class)) {
        $sales[] = $link(__('Deliveries'), route('deliveries.index'), request()->routeIs('deliveries.*'));
    }

    if ($user->can('viewAny', App\Models\LedgerEntry::class)) {
        $sales[] = $link(__('Ledger'), route('finance.index'), request()->routeIs('finance.*'));
    }

    $catalog = [];

    if (! $user->can('shop') && $user->can('viewAny', App\Models\Product::class)) {
        $catalog[] = $link(__('Products'), route('products.index'), request()->routeIs('products.*', 'brands.*', 'categories.*', 'units.*'));
    }

    if ($user->can('viewStock')) {
        $catalog[] = $link(__('Stock'), route('stock.index'), request()->routeIs('stock.*', 'warehouses.*'));
    }

    if ($user->can('viewPrices')) {
        $catalog[] = $link(__('Price lists'), route('price-lists.index'), request()->routeIs('price-lists.*'));
    }

    $communication = [];

    if ($user->can('viewAny', App\Models\MessageThread::class)) {
        $communication[] = $link(__('Messages'), route('messages.index'), request()->routeIs('messages.*'));
    }

    if ($user->can('viewAny', App\Models\MailLog::class)) {
        $communication[] = $link(__('Mail log'), route('mail-logs.index'), request()->routeIs('mail-logs.*'));
    }

    if ($user->can('viewAny', App\Models\MailTemplate::class)) {
        $communication[] = $link(__('Mail templates'), route('mail-templates.index'), request()->routeIs('mail-templates.*'));
    }

    $administration = [];

    if ($user->can('viewAny', App\Models\User::class)) {
        $administration[] = $link(__('Users'), route('users.index'), request()->routeIs('users.*'));
    }

    if ($user->can('viewReports')) {
        $administration[] = $link(__('Reports'), route('reports.index'), request()->routeIs('reports.*'));
    }

    if ($user->can('viewAny', App\Models\AuditLog::class)) {
        $administration[] = $link(__('Audit log'), route('audit-logs.index'), request()->routeIs('audit-logs.*'));
    }

    if ($user->can('update', App\Models\CompanySetting::class)) {
        $administration[] = $link(__('Company details'), route('settings.edit'), request()->routeIs('settings.*'));
    }

    $entries = [
        $link(__('Dashboard'), route('dashboard'), request()->routeIs('dashboard')),
    ];

    if ($user->can('shop')) {
        $entries[] = $link(__('Catalog'), route('catalog.index'), request()->routeIs('catalog.*'));
        $cartLabel = __('Cart');

        if (($cartCount ?? 0) > 0) {
            $cartLabel .= ' ('.$cartCount.')';
        }

        $entries[] = $link($cartLabel, route('cart.index'), request()->routeIs('cart.*'));
    }

    if ($user->dealer_id) {
        $entries[] = $link(__('My company'), route('dealers.show', $user->dealer_id), request()->routeIs('dealers.show'));
    }

    foreach ([
        __('Sales') => $sales,
        __('Catalog') => $catalog,
        __('Communication') => $communication,
        __('Administration') => $administration,
    ] as $label => $items) {
        if (count($items) === 1) {
            $entries[] = $items[0];
        } elseif (count($items) > 1) {
            $entries[] = [
                'label' => $label,
                'items' => $items,
                'active' => collect($items)->contains(fn (array $item) => $item['active']),
            ];
        }
    }
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-ink">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">
            <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brass text-xs font-bold text-white">OB</span>
                <span class="hidden font-semibold tracking-tight text-white sm:block">{{ config('app.name') }}</span>
            </a>

            <div class="hidden min-w-0 flex-1 items-center gap-1 lg:flex">
                @foreach ($entries as $entry)
                    @if (isset($entry['items']))
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button" @class([
                                    'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm focus:outline-none',
                                    'bg-white/15 font-semibold text-white' => $entry['active'],
                                    'font-medium text-stone-300 hover:bg-white/10 hover:text-white' => ! $entry['active'],
                                ])>
                                    {{ $entry['label'] }}
                                    <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @foreach ($entry['items'] as $item)
                                    <x-dropdown-link :href="$item['href']" @class(['bg-paper font-semibold' => $item['active']])>
                                        {{ $item['label'] }}
                                    </x-dropdown-link>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @else
                        <x-nav-link :href="$entry['href']" :active="$entry['active']">
                            {{ $entry['label'] }}
                        </x-nav-link>
                    @endif
                @endforeach
            </div>

            <div class="hidden shrink-0 lg:flex lg:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-stone-200 hover:bg-white/10 focus:outline-none">
                            <span class="max-w-[10rem] truncate">{{ $user->name }}</span>
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

            <div class="flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg p-2 text-stone-300 hover:bg-white/10 hover:text-white focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-white/10 px-3 py-3 lg:hidden">
        <div class="space-y-1">
            @foreach ($entries as $entry)
                @if (isset($entry['items']))
                    <p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-stone-400">{{ $entry['label'] }}</p>
                    @foreach ($entry['items'] as $item)
                        <x-responsive-nav-link :href="$item['href']" :active="$item['active']">
                            {{ $item['label'] }}
                        </x-responsive-nav-link>
                    @endforeach
                @else
                    <x-responsive-nav-link :href="$entry['href']" :active="$entry['active']">
                        {{ $entry['label'] }}
                    </x-responsive-nav-link>
                @endif
            @endforeach
        </div>

        <div class="mt-3 space-y-1 border-t border-white/10 pt-3">
            <div class="px-3 pb-2">
                <div class="text-sm font-semibold text-white">{{ $user->name }}</div>
                <div class="text-xs text-stone-400">{{ $user->email }}</div>
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
