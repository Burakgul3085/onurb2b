<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit dealer') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="card">
            <form method="POST" action="{{ route('dealers.update', $dealer) }}" class="space-y-6 p-6 sm:p-8">
                    @csrf
                    @method('PATCH')
                    <p class="text-sm text-ink-muted">{{ __('Application status') }}: {{ $dealer->application_status->label() }}</p>
                    @include('dealers._form')
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
