<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Brands') }}</h2>
            <div class="mt-3"><x-catalog-nav /></div>
        </div>
    </x-slot>
    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            <form method="POST" action="{{ route('brands.store') }}" class="flex flex-col gap-2 sm:flex-row">
                @csrf
                <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Brand') }}" required />
                <x-primary-button>{{ __('Save') }}</x-primary-button>
            </form>
            <x-input-error :messages="$errors->get('name')" />
            <table class="data-table">
                <tbody>
                    @foreach ($brands as $brand)
                        <tr>
                            <td class="font-medium">{{ $brand->name }}</td>
                            <td><x-badge :tone="$brand->is_active ? 'on' : 'off'">{{ $brand->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                            <td class="text-right"><a href="{{ route('brands.edit', $brand) }}" class="link">{{ __('Edit') }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>{{ $brands->links() }}</div>
        </div>
    </div>
</x-app-layout>
