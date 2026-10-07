<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ $order->number }} · {{ $order->dealer->company_name ?? '' }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Plan delivery') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('deliveries.store') }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <p class="text-sm text-ink">{{ $order->delivery_address }}</p>
            <p class="text-sm text-ink-muted">{{ $order->province }} / {{ $order->district->label() }}</p>
            <div>
                <x-input-label for="user_id" :value="__('Delivery person')" />
                <select id="user_id" name="user_id" class="field mt-1" required>
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($drivers as $driver)
                        <option value="{{ $driver->id }}" @selected((string) old('user_id') === (string) $driver->id)>{{ $driver->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('user_id')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="scheduled_on" :value="__('Date')" />
                    <x-text-input id="scheduled_on" name="scheduled_on" type="date" class="mt-1 block w-full" :value="old('scheduled_on', now()->toDateString())" required />
                    <x-input-error class="mt-2" :messages="$errors->get('scheduled_on')" />
                </div>
                <div>
                    <x-input-label for="scheduled_time" :value="__('Time')" />
                    <x-text-input id="scheduled_time" name="scheduled_time" type="time" class="mt-1 block w-full" :value="old('scheduled_time')" />
                </div>
                <div>
                    <x-input-label for="sequence" :value="__('Sequence')" />
                    <x-text-input id="sequence" name="sequence" type="number" min="1" class="mt-1 block w-full" :value="old('sequence', 1)" required />
                    <x-input-error class="mt-2" :messages="$errors->get('sequence')" />
                </div>
            </div>
            <x-input-error :messages="$errors->get('delivery')" />
            <x-primary-button>{{ __('Plan delivery') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
