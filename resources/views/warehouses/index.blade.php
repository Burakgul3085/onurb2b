<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Warehouses') }}</h2>
                <div class="mt-3"><x-stock-nav /></div>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            @if ($canManage)
                <form method="POST" action="{{ route('warehouses.store') }}" class="flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Warehouse') }}" required />
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" />
            @endif
            <table class="data-table">
                <tbody>
                    @foreach ($warehouses as $warehouse)
                        <tr>
                            <td class="font-medium">{{ $warehouse->name }}</td>
                            <td><x-badge :tone="$warehouse->is_active ? 'on' : 'off'">{{ $warehouse->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                            <td class="text-right">
                                @can('update', $warehouse)
                                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="link">{{ __('Edit') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>{{ $warehouses->links() }}</div>
        </div>
    </div>
</x-app-layout>
