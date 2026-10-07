<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-ink-muted">{{ $order->dealer->company_name }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $order->number }}</h2>
                <div class="mt-3"><x-badge :tone="$order->status->tone()">{{ $order->status->label() }}</x-badge></div>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <x-input-error :messages="$errors->get('order')" />
        <x-input-error :messages="$errors->get('approved')" />
        <x-input-error :messages="$errors->get('reason')" />

        <div class="card p-6 text-sm">
            <p class="text-ink-muted">{{ __('Delivery address') }}</p>
            <p class="mt-1 font-medium">{{ $order->delivery_address }}</p>
            <p class="text-ink-muted">{{ $order->province }} / {{ $order->district->label() }}</p>
            @if ($order->note)
                <p class="mt-3 text-ink-muted">{{ __('Note') }}</p>
                <p class="mt-1">{{ $order->note }}</p>
            @endif
            @if ($order->cancellation_reason)
                <p class="mt-3 text-ink-muted">{{ __('Cancellation reason') }}</p>
                <p class="mt-1">{{ $order->cancellation_reason }}</p>
            @endif
        </div>

        <div class="card space-y-3 p-6 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-semibold text-ink">{{ __('Deliveries') }}</h3>
                @if ($canScheduleDelivery)
                    <a class="link" href="{{ route('deliveries.create', ['order' => $order->id]) }}">{{ __('Plan delivery') }}</a>
                @endif
            </div>
            @forelse ($order->deliveries as $delivery)
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>{{ $delivery->scheduled_on->format('d.m.Y') }} {{ $delivery->scheduled_time ? substr((string) $delivery->scheduled_time, 0, 5) : '' }} · {{ $delivery->driver->name }} · {{ $delivery->sequence }}@if ($delivery->recipient_name) · {{ $delivery->recipient_name }}@endif</span>
                    <x-badge :tone="$delivery->status->tone()">{{ $delivery->status->label() }}</x-badge>
                </div>
            @empty
                <p class="text-ink-muted">{{ __('No deliveries yet.') }}</p>
            @endforelse
        </div>

        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th>{{ __('Requested') }}</th>
                        <th>{{ __('Approved quantity') }}</th>
                        <th>{{ __('Delivered') }}</th>
                        <th>{{ __('Unit price') }}</th>
                        <th>{{ __('Net') }}</th>
                        <th>{{ __('VAT') }}</th>
                        <th>{{ __('Gross') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->lines as $line)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $line->sku }} — {{ $line->product_name }}</div>
                                <div class="text-xs text-ink-muted">{{ $line->quantity }} {{ $line->unit_name }} · {{ $line->pieces_per_unit }} {{ __('pieces') }} · %{{ \App\Support\Money\Money::format($line->discount_percent) }}</div>
                            </td>
                            <td>{{ $line->requested_pieces }} {{ __('pieces') }}</td>
                            <td>{{ $line->approved_pieces }} {{ __('pieces') }}</td>
                            <td>{{ $line->delivered_pieces }} {{ __('pieces') }}</td>
                            <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->unit_price) }} ₺</td>
                            <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->net) }} ₺</td>
                            <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->vat) }} ₺</td>
                            <td class="whitespace-nowrap">{{ \App\Support\Money\Money::format($line->gross) }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1fr_20rem]">
            <div class="space-y-4">
                @if ($canApprove && $order->status->value === 'pending')
                    <form method="POST" action="{{ route('orders.approve', $order) }}" class="card space-y-4 p-6">
                        @csrf
                        <h3 class="font-semibold text-ink">{{ __('Approve order') }}</h3>
                        <div>
                            <x-input-label for="warehouse_id" :value="__('Warehouse')" />
                            <select id="warehouse_id" name="warehouse_id" class="field mt-1" onchange="window.location = '{{ route('orders.show', $order) }}?warehouse_id=' + this.value">
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->id }}" @selected($warehouse?->id === $item->id)>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @foreach ($order->lines as $line)
                            <div>
                                <x-input-label :for="'approved_'.$line->id" :value="$line->sku.' — '.__('Approved quantity')" />
                                <x-text-input :id="'approved_'.$line->id" name="approved[{{ $line->id }}]" type="number" min="0" :max="$line->requested_pieces" class="mt-1 block w-40" :value="old('approved.'.$line->id, $line->requested_pieces)" required />
                                <p class="mt-1 text-xs text-ink-muted">{{ __('Available stock') }}: {{ $availability[$line->product_id] ?? 0 }} {{ __('pieces') }}</p>
                            </div>
                        @endforeach
                        <x-primary-button>{{ __('Approve order') }}</x-primary-button>
                    </form>
                @endif

                @if ($canApprove && $order->status->value === 'approved')
                    <form method="POST" action="{{ route('orders.prepare', $order) }}">
                        @csrf
                        <x-primary-button>{{ __('Mark as preparing') }}</x-primary-button>
                    </form>
                @endif

                @if ($canCancel && $order->status->canCancel())
                    <form method="POST" action="{{ route('orders.cancel', $order) }}" class="card space-y-3 p-6">
                        @csrf
                        <x-input-label for="reason" :value="__('Cancellation reason')" />
                        <textarea id="reason" name="reason" class="field" rows="3" required>{{ old('reason') }}</textarea>
                        <button type="submit" class="btn-danger">{{ __('Cancel order') }}</button>
                    </form>
                @endif
            </div>
            <div class="card space-y-2 p-6 text-sm">
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('Net') }}</span><span>{{ \App\Support\Money\Money::format($order->net) }} ₺</span></div>
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('VAT') }}</span><span>{{ \App\Support\Money\Money::format($order->vat) }} ₺</span></div>
                <div class="flex justify-between"><span class="text-ink-muted">{{ __('Gross') }}</span><span>{{ \App\Support\Money\Money::format($order->gross) }} ₺</span></div>
                <div class="flex justify-between">
                    <span class="text-ink-muted">{{ __('Document discount') }} %{{ \App\Support\Money\Money::format($order->document_discount_percent) }}</span>
                    <span>{{ \App\Support\Money\Money::format($order->discount_amount) }} ₺</span>
                </div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold">
                    <span>{{ __('Payable') }}</span>
                    <span>{{ \App\Support\Money\Money::format($order->payable) }} ₺</span>
                </div>
                <p class="text-xs text-ink-muted">{{ __('Prices and the document discount were locked when the order was sent.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
