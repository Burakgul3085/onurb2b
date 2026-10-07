<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Edit category') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('categories.update', $category) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <div>
                <x-input-label for="name" :value="__('Category')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="parent_id" :value="__('Top-level category')" />
                <select id="parent_id" name="parent_id" class="field mt-1">
                    <option value="">{{ __('Top-level category') }}</option>
                    @foreach ($roots as $root)
                        <option value="{{ $root->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $root->id)>{{ $root->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('parent_id')" />
            </div>
            <input type="hidden" name="is_active" value="0">
            <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ? '1' : '0') == '1')> {{ __('Active') }}</label>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
