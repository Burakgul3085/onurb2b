@props(['href', 'icon'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'dash-link']) }}>
    <span class="dash-icon" aria-hidden="true">
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
            @switch($icon)
                @case('users')
                    <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                    <circle cx="9.5" cy="7" r="3" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a3 3 0 0 1 0 5.75" />
                    @break
                @case('building')
                    <path d="M4 21V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v16" />
                    <path d="M15 21V9h5v12" />
                    <path d="M3 21h18" />
                    <path d="M8 8h2M8 12h2M8 16h2" />
                    @break
                @case('box')
                    <path d="M21 8l-9-5-9 5 9 5 9-5z" />
                    <path d="M3 8v8l9 5 9-5V8" />
                    <path d="M12 13v8" />
                    @break
                @case('truck')
                    <path d="M3 7h11v10H3z" />
                    <path d="M14 10h4l3 3v4h-7" />
                    <circle cx="7" cy="18" r="1.5" />
                    <circle cx="18" cy="18" r="1.5" />
                    @break
                @case('message')
                    <path d="M5 6h14v10H8l-3 3z" />
                    @break
                @case('shield')
                    <path d="M12 3l8 3v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6z" />
                    <path d="M9 12l2 2 4-4" />
                    @break
                @case('chart')
                    <path d="M4 19V5" />
                    <path d="M4 19h16" />
                    <path d="M8 15v-4" />
                    <path d="M12 15V8" />
                    <path d="M16 15v-6" />
                    @break
                @case('ledger')
                    <path d="M6 3h12v18H6z" />
                    <path d="M9 8h6M9 12h6M9 16h4" />
                    @break
                @case('orders')
                    <path d="M8 6h12l-1 12H7L6 4H3" />
                    <path d="M9 10h8" />
                    @break
                @case('stock')
                    <path d="M4 8h16v11H4z" />
                    <path d="M8 8V5h8v3" />
                    <path d="M10 13h4" />
                    @break
                @case('company')
                    <circle cx="12" cy="12" r="8" />
                    <path d="M12 8v8M9 11h6" />
                    @break
                @case('price')
                    <path d="M4 12l8-8h8v8l-8 8z" />
                    <circle cx="16" cy="8" r="1" />
                    @break
                @case('cart')
                    <circle cx="9" cy="19" r="1.4" />
                    <circle cx="17" cy="19" r="1.4" />
                    <path d="M4 5h2l2 10h10l2-7H7" />
                    @break
                @default
                    <rect x="4" y="4" width="7" height="7" rx="1.5" />
                    <rect x="13" y="4" width="7" height="7" rx="1.5" />
                    <rect x="4" y="13" width="7" height="7" rx="1.5" />
                    <rect x="13" y="13" width="7" height="7" rx="1.5" />
            @endswitch
        </svg>
    </span>
    <span class="min-w-0 truncate">{{ $slot }}</span>
</a>
