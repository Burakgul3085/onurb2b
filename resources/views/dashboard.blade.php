<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-brass">{{ Auth::user()->name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Dashboard') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 pb-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('dashboard') }}" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
            <div class="sm:w-64">
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

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($dashboard['kpis'] as $kpi)
                <div class="card p-6">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $kpi['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-ink">{{ $kpi['value'] }}</p>
                </div>
            @endforeach
        </div>

        @if ($dashboard['lastOrder'])
            <a href="{{ $dashboard['lastOrder']['href'] }}" class="card block p-6">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Last order') }}</p>
                <p class="mt-2 text-lg font-semibold text-ink">{{ $dashboard['lastOrder']['number'] }} · {{ $dashboard['lastOrder']['status'] }}</p>
            </a>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($dashboard['charts'] as $chart)
                <div class="card p-6">
                    <h3 class="font-semibold text-ink">{{ $chart['title'] }}</h3>
                    @forelse ($chart['rows'] as $row)
                        <div class="mt-4">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span>{{ $row['label'] }}</span>
                                <span class="font-medium">{{ $row['display'] }}</span>
                            </div>
                            <div class="mt-1 h-2 rounded-full bg-stone-200">
                                <div class="h-2 rounded-full bg-brass" style="width: {{ $row['width'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-ink-muted">{{ __('No dashboard data for this period.') }}</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        @if ($dashboard['dues'] !== [])
            <div class="card space-y-3 p-6">
                <h3 class="font-semibold text-ink">{{ __('Upcoming due dates') }}</h3>
                @foreach ($dashboard['dues'] as $due)
                    <a class="link block" href="{{ $due['href'] }}">{{ $due['label'] }}</a>
                    <p class="-mt-2 text-sm text-ink-muted">{{ $due['meta'] }}</p>
                @endforeach
            </div>
        @endif

        @if ($dashboard['messages'] !== [])
            <div class="card space-y-3 p-6">
                <h3 class="font-semibold text-ink">{{ __('Recent messages') }}</h3>
                @foreach ($dashboard['messages'] as $message)
                    <a class="link block" href="{{ $message['href'] }}">{{ $message['label'] }}</a>
                    <p class="-mt-2 text-sm text-ink-muted">{{ $message['meta'] }}</p>
                @endforeach
            </div>
        @endif

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
            @can('viewAny', App\Models\MessageThread::class)
                <a href="{{ route('messages.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Messages') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Threads with dealers') }}</p>
                </a>
            @endcan
            @can('viewAny', App\Models\AuditLog::class)
                <a href="{{ route('audit-logs.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Audit log') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Sign-ins, prices, stock, and ledger') }}</p>
                </a>
            @endcan
            @can('viewReports')
                <a href="{{ route('reports.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Reports') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Sales, stock, ledger, and deliveries') }}</p>
                </a>
            @endcan
            @can('viewAny', App\Models\LedgerEntry::class)
                <a href="{{ route('finance.index') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Ledger') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Account statement') }}</p>
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
            @can('update', App\Models\CompanySetting::class)
                <a href="{{ route('settings.edit') }}" class="card block p-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ __('Company details') }}</p>
                    <p class="mt-2 text-lg font-semibold text-ink">{{ __('Legal name, logo, and footnote') }}</p>
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
