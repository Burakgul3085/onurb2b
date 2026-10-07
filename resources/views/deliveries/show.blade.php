<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ $delivery->order->number }} · {{ $delivery->dealer->company_name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Delivery') }} {{ $delivery->sequence }}</h2>
            <div class="mt-3"><x-badge :tone="$delivery->status->tone()">{{ $delivery->status->label() }}</x-badge></div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <x-input-error :messages="$errors->get('delivery')" />
        <x-input-error :messages="$errors->get('shipped')" />
        <x-input-error :messages="$errors->get('delivered')" />
        <x-input-error :messages="$errors->get('note')" />

        <div class="card p-6 text-sm">
            <p class="font-medium">{{ $delivery->driver->name }}</p>
            <p class="mt-1 text-ink-muted">{{ $delivery->scheduled_on->format('d.m.Y') }} {{ $delivery->scheduled_time ? substr((string) $delivery->scheduled_time, 0, 5) : '' }}</p>
            <p class="mt-3 font-medium">{{ $delivery->delivery_address }}</p>
            <p class="text-ink-muted">{{ $delivery->province }} / {{ $delivery->district->label() }}</p>
            @if ($delivery->recipient_name)
                <p class="mt-3 text-ink-muted">{{ __('Recipient') }}</p>
                <p>{{ $delivery->recipient_name }}</p>
            @endif
            @if ($delivery->note)
                <p class="mt-3 text-ink-muted">{{ __('Note') }}</p>
                <p>{{ $delivery->note }}</p>
            @endif
            @if ($delivery->proof_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($delivery->proof_path) }}" alt="{{ __('Delivery proof') }}" class="mt-4 max-h-56 rounded-xl object-contain">
            @endif
        </div>

        @if ($canUpdate && $delivery->status->value === 'preparing')
            <form method="POST" action="{{ route('deliveries.dispatch', $delivery) }}" class="card space-y-4 p-6">
                @csrf
                <h3 class="font-semibold text-ink">{{ __('Leave for delivery') }}</h3>
                @foreach ($delivery->order->lines as $line)
                    @php($remaining = $line->approved_pieces - $line->delivered_pieces)
                    @if ($remaining > 0)
                        <div>
                            <x-input-label :for="'shipped_'.$line->id" :value="$line->sku.' — '.__('Shipped quantity')" />
                            <x-text-input :id="'shipped_'.$line->id" name="shipped[{{ $line->id }}]" type="number" min="0" :max="$remaining" class="mt-1 block w-40" :value="old('shipped.'.$line->id, $remaining)" required />
                            <p class="mt-1 text-xs text-ink-muted">{{ __('Approved quantity') }}: {{ $line->approved_pieces }} · {{ __('Delivered') }}: {{ $line->delivered_pieces }}</p>
                        </div>
                    @endif
                @endforeach
                <x-primary-button>{{ __('Leave for delivery') }}</x-primary-button>
            </form>
            <form method="POST" action="{{ route('deliveries.destroy', $delivery) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">{{ __('Remove delivery plan') }}</button>
            </form>
        @endif

        @if ($delivery->lines->isNotEmpty())
            <div class="card overflow-x-auto p-6">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Shipped quantity') }}</th>
                            <th>{{ __('Delivered') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($delivery->lines as $line)
                            <tr>
                                <td>{{ $line->orderLine->sku }} — {{ $line->orderLine->product_name }}</td>
                                <td>{{ $line->shipped_pieces }} {{ __('pieces') }}</td>
                                <td>{{ $line->delivered_pieces }} {{ __('pieces') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($canUpdate && $delivery->status->value === 'out_for_delivery')
            <form method="POST" action="{{ route('deliveries.complete', $delivery) }}" enctype="multipart/form-data" class="card space-y-4 p-6">
                @csrf
                <h3 class="font-semibold text-ink">{{ __('Complete delivery') }}</h3>
                <div>
                    <x-input-label for="recipient_name" :value="__('Recipient')" />
                    <x-text-input id="recipient_name" name="recipient_name" type="text" class="mt-1 block w-full" :value="old('recipient_name')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('recipient_name')" />
                </div>
                @foreach ($delivery->lines as $line)
                    <div>
                        <x-input-label :for="'delivered_'.$line->id" :value="$line->orderLine->sku.' — '.__('Delivered')" />
                        <x-text-input :id="'delivered_'.$line->id" name="delivered[{{ $line->id }}]" type="number" min="0" :max="$line->shipped_pieces" class="mt-1 block w-40" :value="old('delivered.'.$line->id, $line->shipped_pieces)" required />
                    </div>
                @endforeach
                <div>
                    <x-input-label for="complete_note" :value="__('Note')" />
                    <textarea id="complete_note" name="note" class="field mt-1" rows="2">{{ old('note') }}</textarea>
                </div>
                <div>
                    <x-input-label for="proof" :value="__('Delivery proof')" />
                    <input id="proof" name="proof" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                    <x-input-error class="mt-2" :messages="$errors->get('proof')" />
                </div>
                <x-primary-button>{{ __('Complete delivery') }}</x-primary-button>
            </form>
            <form method="POST" action="{{ route('deliveries.fail', $delivery) }}" enctype="multipart/form-data" class="card space-y-3 p-6">
                @csrf
                <h3 class="font-semibold text-ink">{{ __('Fail delivery') }}</h3>
                <textarea name="note" class="field" rows="3" required placeholder="{{ __('Note') }}">{{ old('note') }}</textarea>
                <input name="proof" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm">
                <button type="submit" class="btn-danger">{{ __('Fail delivery') }}</button>
            </form>
        @endif
    </div>
</x-app-layout>
