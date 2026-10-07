<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Categories') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            <form method="POST" action="{{ route('categories.store') }}" class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                @csrf
                <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Category') }}" required />
                <select name="parent_id" class="field">
                    <option value="">{{ __('Top-level category') }}</option>
                    @foreach ($roots as $root)
                        <option value="{{ $root->id }}" @selected((string) old('parent_id') === (string) $root->id)>{{ $root->name }}</option>
                    @endforeach
                </select>
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('name')" />
            <x-input-error :messages="$errors->get('parent_id')" />
            <table class="data-table">
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td class="font-medium">{{ $category->label() }}</td>
                            <td><x-badge :tone="$category->is_active ? 'on' : 'off'">{{ $category->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                            <td class="text-right"><a class="link" href="{{ route('categories.edit', $category) }}">{{ __('Edit') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>{{ $categories->links() }}</div>
        </div>
    </div>
</x-app-layout>
