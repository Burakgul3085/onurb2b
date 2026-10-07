<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Create product') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <div class="card">
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="grid gap-5 p-6 sm:grid-cols-2 sm:p-8">
                    @csrf
                    @include('products._form', ['product' => null])
                    <div class="sm:col-span-2"><x-primary-button>{{ __('Save') }}</x-primary-button></div>
            </form>
        </div>
    </div>
</x-app-layout>
