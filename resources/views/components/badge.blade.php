@props(['tone' => 'on'])

@php
    $tones = [
        'on' => 'badge badge-on',
        'off' => 'badge badge-off',
        'wait' => 'badge badge-wait',
    ];
@endphp

<span {{ $attributes->merge(['class' => $tones[$tone] ?? $tones['on']]) }}>{{ $slot }}</span>
