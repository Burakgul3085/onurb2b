<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Open a thread') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('messages.store') }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            <x-flash />
            @if (Auth::user()->dealer_id === null)
                <div>
                    <x-input-label for="dealer_id" :value="__('Dealer')" />
                    @if ($order)
                        <input type="hidden" name="dealer_id" value="{{ $order->dealer_id }}" />
                        <p class="mt-1 font-medium">{{ $order->dealer->company_name }}</p>
                    @else
                        <select id="dealer_id" name="dealer_id" class="field mt-1" required>
                            <option value="">{{ __('Dealer') }}</option>
                            @foreach ($dealers as $dealer)
                                <option value="{{ $dealer->id }}" @selected((int) old('dealer_id') === $dealer->id)>{{ $dealer->company_name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <x-input-error class="mt-2" :messages="$errors->get('dealer_id')" />
                </div>
            @endif
            <div>
                <x-input-label for="order_id" :value="__('Order number')" />
                @if ($order)
                    <input type="hidden" name="order_id" value="{{ $order->id }}" />
                    <p class="mt-1 font-medium">{{ $order->number }}</p>
                @else
                    <input id="order_id" name="order_id" type="number" class="field mt-1" value="{{ old('order_id') }}" />
                @endif
                <x-input-error class="mt-2" :messages="$errors->get('order_id')" />
            </div>
            <div>
                <x-input-label for="subject" :value="__('Subject')" />
                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject')" required />
                <x-input-error class="mt-2" :messages="$errors->get('subject')" />
            </div>
            <div>
                <x-input-label for="body" :value="__('Message')" />
                <textarea id="body" name="body" class="field mt-1" rows="6" required>{{ old('body') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('body')" />
            </div>
            <x-primary-button>{{ __('Write a message') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
