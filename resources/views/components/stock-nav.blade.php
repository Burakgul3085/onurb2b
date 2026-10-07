@can('viewStock')
    <nav class="flex flex-wrap gap-2">
        <a href="{{ route('stock.index') }}" @class(['chip', 'chip-on' => request()->routeIs('stock.index', 'stock.create')])>{{ __('Stock') }}</a>
        <a href="{{ route('stock.movements') }}" @class(['chip', 'chip-on' => request()->routeIs('stock.movements')])>{{ __('Stock movements') }}</a>
        @can('viewAny', App\Models\Warehouse::class)
            <a href="{{ route('warehouses.index') }}" @class(['chip', 'chip-on' => request()->routeIs('warehouses.*')])>{{ __('Warehouses') }}</a>
        @endcan
    </nav>
@endcan
