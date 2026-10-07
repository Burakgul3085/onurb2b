<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit price') }}</h2>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('price-lists.items.update', [$priceList, $item]) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            @include('prices._record', ['record' => $item])
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
