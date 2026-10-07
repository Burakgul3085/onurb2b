<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit dealer') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('dealers.update', $dealer) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PATCH')
                    <p class="text-sm text-gray-600">{{ __('Application status') }}: {{ $dealer->application_status->label() }}</p>
                    @include('dealers._form')
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
