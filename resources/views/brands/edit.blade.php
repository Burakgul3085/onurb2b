<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit brand') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('brands.update', $brand) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" :value="__('Brand')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $brand->name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ? '1' : '0') == '1' || old('is_active', $brand->is_active) === true)> {{ __('Active') }}</label>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
