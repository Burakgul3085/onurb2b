<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit price list') }}</h2>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('price-lists.update', $priceList) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" :value="__('Price list')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $priceList->name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="document_discount_percent" :value="__('Document discount')" />
                <x-text-input id="document_discount_percent" name="document_discount_percent" type="text" class="mt-1 block w-full" :value="old('document_discount_percent', $priceList->document_discount_percent)" required />
                <x-input-error class="mt-2" :messages="$errors->get('document_discount_percent')" />
            </div>
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm text-ink">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $priceList->is_active ? '1' : '0') == '1' || old('is_active', $priceList->is_active) === true)>
                {{ __('Active') }}
            </label>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
