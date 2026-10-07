<x-guest-layout>
    <div class="text-center">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ config('app.name') }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('Stationery distribution and dealer management') }}</p>

        <div class="mt-6 flex items-center justify-center gap-4 text-sm">
            @auth
                <x-primary-link :href="route('dashboard')">{{ __('Dashboard') }}</x-primary-link>
            @else
                <x-primary-link :href="route('login')">{{ __('Log in') }}</x-primary-link>
                <a href="{{ route('dealers.apply') }}" class="link">{{ __('Apply as a dealer') }}</a>
            @endauth
        </div>
    </div>
</x-guest-layout>
