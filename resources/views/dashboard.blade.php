<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm text-ink-muted">{{ Auth::user()->name }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Dashboard') }}</h2>
                @if (Auth::user()->roleLabels() !== '')
                    <p class="mt-2"><span class="badge badge-wait">{{ Auth::user()->roleLabels() }}</span></p>
                @endif
            </div>
            <form method="GET" action="{{ route('dashboard') }}" class="card flex flex-col gap-3 p-3 sm:flex-row sm:items-end">
                <div class="sm:w-48">
                    <x-input-label for="period" :value="__('Date')" />
                    <select id="period" name="period" class="field mt-1">
                        @foreach ($periods as $period)
                            <option value="{{ $period->value }}" @selected($dashboard['period'] === $period)>{{ $period->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="from" :value="__('Start date')" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="$dashboard['from']" />
                </div>
                <div>
                    <x-input-label for="to" :value="__('End date')" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="$dashboard['to']" />
                </div>
                <x-primary-button>{{ __('Show report') }}</x-primary-button>
            </form>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pb-10 sm:px-6 lg:px-8">
        <div class="dash-links grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
            @can('viewAny', App\Models\User::class)
                <x-dash-link :href="route('users.index')" icon="users">{{ __('Users') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\Dealer::class)
                <x-dash-link :href="route('dealers.index')" icon="building">{{ __('Dealers') }}</x-dash-link>
            @endcan
            @if (Auth::user()->dealer_id)
                <x-dash-link :href="route('dealers.show', Auth::user()->dealer_id)" icon="building">{{ __('My company') }}</x-dash-link>
            @endif
            @can('shop')
                <x-dash-link :href="route('catalog.index')" icon="box">{{ __('Catalog') }}</x-dash-link>
                <x-dash-link :href="route('cart.index')" icon="cart">{{ __('Cart') }}</x-dash-link>
            @elsecan('viewAny', App\Models\Product::class)
                <x-dash-link :href="route('products.index')" icon="box">{{ __('Products') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\Order::class)
                <x-dash-link :href="route('orders.index')" icon="orders">{{ __('Orders') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\Delivery::class)
                <x-dash-link :href="route('deliveries.index')" icon="truck">{{ __('Deliveries') }}</x-dash-link>
            @endcan
            @can('viewStock')
                <x-dash-link :href="route('stock.index')" icon="stock">{{ __('Stock') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\LedgerEntry::class)
                <x-dash-link :href="route('finance.index')" icon="ledger">{{ __('Ledger') }}</x-dash-link>
            @endcan
            @can('viewPrices')
                <x-dash-link :href="route('price-lists.index')" icon="price">{{ __('Price lists') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\MessageThread::class)
                <x-dash-link :href="route('messages.index')" icon="message">{{ __('Messages') }}</x-dash-link>
            @endcan
            @can('viewReports')
                <x-dash-link :href="route('reports.index')" icon="chart">{{ __('Reports') }}</x-dash-link>
            @endcan
            @can('viewAny', App\Models\AuditLog::class)
                <x-dash-link :href="route('audit-logs.index')" icon="shield">{{ __('Audit log') }}</x-dash-link>
            @endcan
            @can('update', App\Models\CompanySetting::class)
                <x-dash-link :href="route('settings.edit')" icon="company">{{ __('Company details') }}</x-dash-link>
            @endcan
        </div>

        <div class="kpi-grid grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($dashboard['kpis'] as $kpi)
                <article class="kpi">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $kpi['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-ink">{{ $kpi['value'] }}</p>
                    <div class="mt-4 h-1 overflow-hidden rounded-full bg-stone-100">
                        <div class="chart-bar h-full w-full rounded-full bg-brass"></div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($dashboard['lastOrder'])
            <a href="{{ $dashboard['lastOrder']['href'] }}" class="card flex items-center justify-between gap-4 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Last order') }}</p>
                    <p class="mt-1 text-lg font-semibold text-ink">{{ $dashboard['lastOrder']['number'] }} · {{ $dashboard['lastOrder']['status'] }}</p>
                </div>
                <span class="dash-icon" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </span>
            </a>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($dashboard['charts'] as $chart)
                <div class="card p-5">
                    <h3 class="font-semibold text-ink">{{ $chart['title'] }}</h3>
                    @forelse ($chart['rows'] as $row)
                        <div class="mt-4">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span>{{ $row['label'] }}</span>
                                <span class="font-medium">{{ $row['display'] }}</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-stone-100">
                                <div class="chart-bar h-2 rounded-full bg-gradient-to-r from-brass to-brass-light" style="width: {{ $row['width'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-ink-muted">{{ __('No dashboard data for this period.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        @if ($dashboard['dues'] !== [] || $dashboard['messages'] !== [])
            <div class="grid gap-4 lg:grid-cols-2">
                @if ($dashboard['dues'] !== [])
                    <div class="card p-5">
                        <h3 class="font-semibold text-ink">{{ __('Upcoming due dates') }}</h3>
                        <div class="mt-3 divide-y divide-stone-100">
                            @foreach ($dashboard['dues'] as $due)
                                <a class="block py-3 transition hover:bg-paper/60" href="{{ $due['href'] }}">
                                    <span class="link">{{ $due['label'] }}</span>
                                    <span class="mt-1 block text-sm text-ink-muted">{{ $due['meta'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if ($dashboard['messages'] !== [])
                    <div class="card p-5">
                        <h3 class="font-semibold text-ink">{{ __('Recent messages') }}</h3>
                        <div class="mt-3 divide-y divide-stone-100">
                            @foreach ($dashboard['messages'] as $message)
                                <a class="flex items-center justify-between gap-3 py-3" href="{{ $message['href'] }}">
                                    <span>
                                        <span class="link">{{ $message['label'] }}</span>
                                        <span class="mt-1 block text-sm text-ink-muted">{{ $message['meta'] }}</span>
                                    </span>
                                    <span class="h-2 w-2 shrink-0 rounded-full bg-brass"></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
