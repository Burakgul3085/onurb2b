<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Price lists') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card space-y-4 p-6">
            @if ($canManage)
                <form method="POST" action="{{ route('price-lists.store') }}" class="grid gap-2 sm:grid-cols-[1fr_8rem_auto]">
                    @csrf
                    <x-text-input name="name" type="text" class="block w-full" :value="old('name')" placeholder="{{ __('Price list') }}" required />
                    <x-text-input name="document_discount_percent" type="text" class="block w-full" :value="old('document_discount_percent', '0')" placeholder="{{ __('Document discount') }}" required />
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
                <p class="text-sm text-ink-muted">{{ __('Document discount is one percent for the whole document.') }}</p>
                <x-input-error :messages="$errors->get('name')" />
                <x-input-error :messages="$errors->get('document_discount_percent')" />
            @endif
            <table class="data-table">
                <tbody>
                    @forelse ($priceLists as $priceList)
                        <tr>
                            <td class="font-medium"><a href="{{ route('price-lists.show', $priceList) }}" class="link">{{ $priceList->name }}</a></td>
                            <td>%{{ \App\Support\Money\Money::format($priceList->document_discount_percent) }}</td>
                            <td>{{ $priceList->dealers_count }}</td>
                            <td><x-badge :tone="$priceList->is_active ? 'on' : 'off'">{{ $priceList->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                        </tr>
                    @empty
                        <tr><td class="py-8 text-ink-muted">{{ __('No price lists yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div>{{ $priceLists->links() }}</div>
        </div>
    </div>
</x-app-layout>
