<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Categories') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg"><p class="p-4 text-sm text-green-700">{{ session('status') }}</p></div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="POST" action="{{ route('categories.store') }}" class="grid gap-2 sm:grid-cols-3">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Category') }}" required />
                    <select name="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">{{ __('Top-level category') }}</option>
                        @foreach ($roots as $root)
                            <option value="{{ $root->id }}" @selected((string) old('parent_id') === (string) $root->id)>{{ $root->name }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" />
                <x-input-error :messages="$errors->get('parent_id')" />
                <table class="min-w-full text-sm">
                    <tbody>
                        @foreach ($categories as $category)
                            <tr class="border-t border-gray-100">
                                <td class="py-3">{{ $category->label() }}</td>
                                <td class="py-3">{{ $category->is_active ? __('Active') : __('Inactive') }}</td>
                                <td class="py-3 text-right"><a class="underline" href="{{ route('categories.edit', $category) }}">{{ __('Edit') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div>{{ $categories->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
