<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit brand') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('brands.update', $brand) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="name" :value="__('Brand')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $brand->name)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $brand->is_active ? '1' : '0') == '1' || old('is_active', $brand->is_active) === true)> {{ __('Active') }}</label>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
