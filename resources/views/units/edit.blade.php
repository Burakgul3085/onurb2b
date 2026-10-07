<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit unit') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('units.update', $unit) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" :value="__('Unit')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $unit->name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            @unless ($unit->is_base)
                <div>
                    <x-input-label for="parent_id" :value="__('Parent unit')" />
                    <select id="parent_id" name="parent_id" class="field mt-1" required>
                        @foreach ($units as $candidate)
                            <option value="{{ $candidate->id }}" @selected((string) old('parent_id', $unit->parent_id) === (string) $candidate->id)>{{ $candidate->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
                </div>
                <div>
                    <x-input-label for="multiplier" :value="__('Multiplier')" />
                    <x-text-input id="multiplier" name="multiplier" type="number" min="1" class="mt-1 block w-full" :value="old('multiplier', $unit->multiplier)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('multiplier')" />
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $unit->is_active ? '1' : '0') == '1')> {{ __('Active') }}</label>
                <x-input-error :messages="$errors->get('is_active')" />
            @else
                <p class="text-sm text-ink-muted">{{ __('Base unit') }}: 1 = 1 {{ __('pieces') }}</p>
            @endunless
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
