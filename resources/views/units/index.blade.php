<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Units') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg"><p class="p-4 text-sm text-green-700">{{ session('status') }}</p></div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="POST" action="{{ route('units.store') }}" class="grid gap-2 sm:grid-cols-4">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" placeholder="{{ __('Unit') }}" required />
                    <select name="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="multiplier" type="number" min="1" class="block w-full" placeholder="{{ __('Multiplier') }}" required />
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
                <p class="text-sm text-gray-600">{{ __('One of this unit equals the multiplier of the selected unit.') }}</p>
                <x-input-error :messages="$errors->get('name')" />
                <x-input-error :messages="$errors->get('parent_id')" />
                <x-input-error :messages="$errors->get('multiplier')" />
                <table class="min-w-full text-sm">
                    <tbody>
                        @foreach ($units as $unit)
                            <tr class="border-t border-gray-100">
                                <td class="py-3">{{ $unit->name }}</td>
                                <td class="py-3">{{ $unit->is_base ? __('Base unit') : '1 = '.$unit->pieces().' '.__('pieces') }}</td>
                                <td class="py-3">{{ $unit->is_active ? __('Active') : __('Inactive') }}</td>
                                <td class="py-3 text-right"><a class="underline" href="{{ route('units.edit', $unit) }}">{{ __('Edit') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
