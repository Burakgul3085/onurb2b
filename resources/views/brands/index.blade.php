<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Brands') }}</h2></x-slot>
    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white shadow-sm sm:rounded-lg"><p class="p-4 text-sm text-green-700">{{ session('status') }}</p></div>
            @endif
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <form method="POST" action="{{ route('brands.store') }}" class="flex gap-2">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Brand') }}" required />
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
                <x-input-error :messages="$errors->get('name')" />
                <table class="min-w-full text-sm">
                    <tbody>
                        @foreach ($brands as $brand)
                            <tr class="border-t border-gray-100">
                                <td class="py-3">{{ $brand->name }}</td>
                                <td class="py-3">{{ $brand->is_active ? __('Active') : __('Inactive') }}</td>
                                <td class="py-3 text-right"><a href="{{ route('brands.edit', $brand) }}" class="underline">{{ __('Edit') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div>{{ $brands->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
