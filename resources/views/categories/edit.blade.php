<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit category') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('categories.update', $category) }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="name" :value="__('Category')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="parent_id" :value="__('Top-level category')" />
                    <select id="parent_id" name="parent_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full">
                        <option value="">{{ __('Top-level category') }}</option>
                        @foreach ($roots as $root)
                            <option value="{{ $root->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $root->id)>{{ $root->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ? '1' : '0') == '1')> {{ __('Active') }}</label>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
