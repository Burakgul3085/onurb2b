@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold text-white bg-white/15 focus:outline-none'
            : 'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-stone-300 hover:bg-white/10 hover:text-white focus:outline-none transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
