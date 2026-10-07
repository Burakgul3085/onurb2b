<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm text-ink-muted">{{ $dealer->province }} / {{ $dealer->district->label() }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $dealer->company_name }}</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-badge :tone="$dealer->application_status->value === 'approved' ? 'on' : ($dealer->application_status->value === 'rejected' ? 'off' : 'wait')">
                        {{ $dealer->application_status->label() }}
                    </x-badge>
                    <x-badge :tone="$dealer->is_active ? 'on' : 'off'">{{ $dealer->is_active ? __('Active') : __('Inactive') }}</x-badge>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('viewPrices')
                    <a href="{{ route('dealers.prices.index', $dealer) }}" class="btn-secondary">{{ __('Special prices') }}</a>
                @endcan
                @can('update', $dealer)
                    <x-primary-link :href="route('dealers.edit', $dealer)">{{ __('Edit') }}</x-primary-link>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />

        <div class="card">
            <dl class="grid grid-cols-1 gap-5 p-6 text-sm sm:grid-cols-2 sm:p-8">
                <div>
                    <dt class="text-ink-muted">{{ __('Contact name') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->contact_name }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Phone') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->phone }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Email') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->email }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Tax number') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->tax_number }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Tax office') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->tax_office }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Payment term (days)') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->payment_term_days }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">{{ __('Price list') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->priceList?->name ?: __('No price list') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-ink-muted">{{ __('Address') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->address }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-ink-muted">{{ __('Delivery address') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->delivery_address }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-ink-muted">{{ __('Billing address') }}</dt>
                    <dd class="mt-1 font-medium">{{ $dealer->billing_address }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-ink-muted">{{ __('Login user') }}</dt>
                    <dd class="mt-1 font-medium">
                        @forelse ($dealer->users as $user)
                            <div>{{ $user->name }} — {{ $user->email }}</div>
                        @empty
                            {{ __('No login user yet.') }}
                        @endforelse
                    </dd>
                </div>
                @if ($dealer->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-ink-muted">{{ __('Notes') }}</dt>
                        <dd class="mt-1 font-medium">{{ $dealer->notes }}</dd>
                    </div>
                @endif
                @if ($dealer->rejection_reason)
                    <div class="sm:col-span-2">
                        <dt class="text-ink-muted">{{ __('Rejection reason') }}</dt>
                        <dd class="mt-1 font-medium">{{ $dealer->rejection_reason }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        @can('approve', $dealer)
            @if ($dealer->application_status === \App\Enums\DealerApplicationStatus::Pending)
                <div class="card">
                    <form method="POST" action="{{ route('dealers.approve', $dealer) }}" class="space-y-4 p-6 sm:p-8">
                        @csrf
                        <h3 class="text-lg font-semibold text-ink">{{ __('Approve dealer') }}</h3>
                        <p class="text-sm text-ink-muted">{{ __('The dealer can sign in with the company email and this password.') }}</p>
                        <div>
                            <x-input-label for="password" :value="__('Password for the dealer login')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>
                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        <x-input-error class="mt-2" :messages="$errors->get('application_status')" />
                        <x-primary-button>{{ __('Approve dealer') }}</x-primary-button>
                    </form>
                </div>

                <div class="card">
                    <form method="POST" action="{{ route('dealers.reject', $dealer) }}" class="space-y-4 p-6 sm:p-8">
                        @csrf
                        <h3 class="text-lg font-semibold text-ink">{{ __('Reject dealer') }}</h3>
                        <div>
                            <x-input-label for="rejection_reason" :value="__('Rejection reason')" />
                            <textarea id="rejection_reason" name="rejection_reason" rows="3" class="field mt-1" required>{{ old('rejection_reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('rejection_reason')" />
                            <x-input-error class="mt-2" :messages="$errors->get('application_status')" />
                        </div>
                        <x-danger-button>{{ __('Reject dealer') }}</x-danger-button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</x-app-layout>
