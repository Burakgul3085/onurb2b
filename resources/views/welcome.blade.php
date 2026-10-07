<x-guest-layout>
    <div class="text-center">
        <h1 class="text-xl font-semibold text-gray-900">{{ config('app.name') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Stationery distribution and dealer management') }}</p>

        <div class="mt-6 flex items-center justify-center gap-4 text-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="underline text-gray-700 hover:text-gray-900">
                    {{ __('Dashboard') }}
                </a>
            @else
                <a href="{{ route('login') }}" class="underline text-gray-700 hover:text-gray-900">
                    {{ __('Log in') }}
                </a>

                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="underline text-gray-700 hover:text-gray-900">
                        {{ __('Register') }}
                    </a>
                @endif
            @endauth
        </div>
    </div>
</x-guest-layout>
