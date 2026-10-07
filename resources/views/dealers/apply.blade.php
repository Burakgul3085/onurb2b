<x-public-layout>
    <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Apply as a dealer') }}</h1>
    <p class="mt-2 text-sm text-ink-muted">{{ __('Dealer application received hint') }}</p>

    <x-flash />

    <form method="POST" action="{{ route('dealers.apply.store') }}" class="mt-6 space-y-6">
        @csrf
        @include('dealers._form', ['dealer' => null])
        <div class="flex items-center justify-end">
            <x-primary-button>{{ __('Submit application') }}</x-primary-button>
        </div>
    </form>
</x-public-layout>
