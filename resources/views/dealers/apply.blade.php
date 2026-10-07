<x-public-layout>
    <h1 class="text-xl font-semibold text-gray-800">{{ __('Apply as a dealer') }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ __('Dealer application received hint') }}</p>

    @if (session('status'))
        <p class="mt-4 text-sm text-green-700">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('dealers.apply.store') }}" class="mt-6 space-y-6">
        @csrf
        @include('dealers._form', ['dealer' => null])
        <div class="flex items-center justify-between gap-4">
            <a href="{{ route('login') }}" class="underline text-sm text-gray-600">{{ __('Log in') }}</a>
            <x-primary-button>{{ __('Submit application') }}</x-primary-button>
        </div>
    </form>
</x-public-layout>
