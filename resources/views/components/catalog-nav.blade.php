@can('manageCatalog')
    <nav class="flex flex-wrap gap-2">
        <a href="{{ route('products.index') }}" @class(['chip', 'chip-on' => request()->routeIs('products.*')])>{{ __('Products') }}</a>
        <a href="{{ route('brands.index') }}" @class(['chip', 'chip-on' => request()->routeIs('brands.*')])>{{ __('Brands') }}</a>
        <a href="{{ route('categories.index') }}" @class(['chip', 'chip-on' => request()->routeIs('categories.*')])>{{ __('Categories') }}</a>
        <a href="{{ route('units.index') }}" @class(['chip', 'chip-on' => request()->routeIs('units.*')])>{{ __('Units') }}</a>
    </nav>
@endcan
