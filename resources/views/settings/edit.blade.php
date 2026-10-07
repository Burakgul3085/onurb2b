<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Company details') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Shown on the operational delivery note.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <x-flash />
            <div>
                <x-input-label for="legal_name" :value="__('Legal name')" />
                <x-text-input id="legal_name" name="legal_name" type="text" class="mt-1 block w-full" :value="old('legal_name', $setting->legal_name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('legal_name')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="tax_office" :value="__('Tax office')" />
                    <x-text-input id="tax_office" name="tax_office" type="text" class="mt-1 block w-full" :value="old('tax_office', $setting->tax_office)" />
                </div>
                <div>
                    <x-input-label for="tax_number" :value="__('Tax number')" />
                    <x-text-input id="tax_number" name="tax_number" type="text" class="mt-1 block w-full" :value="old('tax_number', $setting->tax_number)" />
                </div>
            </div>
            <div>
                <x-input-label for="address" :value="__('Address')" />
                <textarea id="address" name="address" class="field mt-1" rows="3" required>{{ old('address', $setting->address) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('address')" />
            </div>
            <div>
                <x-input-label for="footnote" :value="__('Document footnote')" />
                <textarea id="footnote" name="footnote" class="field mt-1" rows="3" required>{{ old('footnote', $setting->footnote) }}</textarea>
                <p class="mt-1 text-xs text-ink-muted">{{ \App\Models\DeliveryDocument::DISCLAIMER }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('footnote')" />
            </div>
            <div>
                <x-input-label for="logo" :value="__('Logo')" />
                <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                <x-input-error class="mt-2" :messages="$errors->get('logo')" />
            </div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
