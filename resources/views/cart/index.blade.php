<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Cart') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('Stock is not reserved until an order is approved.') }}</p>
            </div>
            <x-primary-link :href="route('catalog.index')">{{ __('Catalog') }}</x-primary-link>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <x-input-error :messages="$errors->get('quantity')" />

        <div class="card p-6">
            <p class="text-sm text-ink-muted">{{ __('Delivery address') }}</p>
            <p class="mt-1 font-medium text-ink">{{ $dealer->delivery_address }}</p>
            <p class="text-sm text-ink-muted">{{ $dealer->province }} / {{ $dealer->district->label() }}</p>
        </div>

        <div class="card">
            <div class="overflow-x-auto p-6">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Unit price') }}</th>
                            <th>{{ __('Net') }}</th>
                            <th>{{ __('VAT') }}</th>
                            <th>{{ __('Gross') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summary->lines as $line)
                            <tr>
                                <td>
                                    <div class="font-medium">{{ $line->item->product->sku }} — {{ $line->item->product->name }}</div>
                                    @if ($line->sellable)
                                        <div class="text-xs text-ink-muted">{{ $line->pieces }} {{ __('pieces') }} · {{ $line->quote->source->label() }}</div>
                                    @else
                                        <div class="text-xs text-rose-700">{{ __('This product is not for sale.') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($line->sellable)
                                        <form method="POST" action="{{ route('cart.items.update', $line->item) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <x-text-input name="quantity" type="number" min="1" :value="$line->item->quantity" class="w-20" required />
                                            <button type="submit" class="btn-secondary">{{ __('Update') }}</button>
                                        </form>
                                    @else
                                        {{ $line->item->quantity }}
                                    @endif
                                </td>
                                <td class="whitespace-nowrap">{{ $line->sellable ? \App\Support\Money\Money::format($line->quote->discountedPrice).' ₺' : '—' }}</td>
                                <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->net) }} ₺</td>
                                <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->vat) }} ₺</td>
                                <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->gross) }} ₺</td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('cart.items.destroy', $line->item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">{{ __('Remove') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-8 text-ink-muted">{{ __('Your cart is empty.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
            <div>
                @if ($summary->lines !== [])
                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger">{{ __('Clear cart') }}</button>
                    </form>
                @endif
            </div>
            <div class="card space-y-2 p-6 text-sm">
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('Net') }}</span><span>{{ \App\Support\Money\Money::format($summary->net) }} ₺</span></div>
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('VAT') }}</span><span>{{ \App\Support\Money\Money::format($summary->vat) }} ₺</span></div>
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('Gross') }}</span><span>{{ \App\Support\Money\Money::format($summary->gross) }} ₺</span></div>
                <div class="flex justify-between">
                    <span class="text-ink-muted">{{ __('Document discount') }} %{{ \App\Support\Money\Money::format($summary->documentDiscountPercent) }}</span>
                    <span>{{ \App\Support\Money\Money::format($summary->discountAmount) }} ₺</span>
                </div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold">
                    <span>{{ __('Payable') }}</span>
                    <span>{{ \App\Support\Money\Money::format($summary->payable) }} ₺</span>
                </div>
                <p class="text-xs text-ink-muted">{{ __('Document discount is applied once to the cart total, not inside the unit price.') }}</p>
                <p class="text-xs text-ink-muted">{{ __('Sending the order comes later.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
