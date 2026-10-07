<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Units') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            <form method="POST" action="{{ route('units.store') }}" class="grid gap-2 sm:grid-cols-4">
                @csrf
                <x-text-input name="name" type="text" class="block w-full" placeholder="{{ __('Unit') }}" required />
                <select name="parent_id" class="field" required>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                <x-text-input name="multiplier" type="number" min="1" class="block w-full" placeholder="{{ __('Multiplier') }}" required />
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
            <p class="text-sm text-ink-muted">{{ __('One of this unit equals the multiplier of the selected unit.') }}</p>
            <x-input-error :messages="$errors->get('name')" />
            <x-input-error :messages="$errors->get('parent_id')" />
            <x-input-error :messages="$errors->get('multiplier')" />
            <table class="data-table">
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td class="font-medium">{{ $unit->name }}</td>
                            <td>{{ $unit->is_base ? __('Base unit') : '1 = '.$unit->pieces().' '.__('pieces') }}</td>
                            <td><x-badge :tone="$unit->is_active ? 'on' : 'off'">{{ $unit->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                            <td class="text-right"><a class="link" href="{{ route('units.edit', $unit) }}">{{ __('Edit') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
