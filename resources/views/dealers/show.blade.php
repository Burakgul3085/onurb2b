<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $dealer->company_name }}
            </h2>

            @can('update', $dealer)
                <a href="{{ route('dealers.edit', $dealer) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Edit') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <p class="p-4 text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <dl class="p-6 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-gray-500">{{ __('Contact name') }}</dt>
                        <dd>{{ $dealer->contact_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Phone') }}</dt>
                        <dd>{{ $dealer->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Email') }}</dt>
                        <dd>{{ $dealer->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Tax number') }}</dt>
                        <dd>{{ $dealer->tax_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Tax office') }}</dt>
                        <dd>{{ $dealer->tax_office }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('District') }}</dt>
                        <dd>{{ $dealer->province }} / {{ $dealer->district->label() }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">{{ __('Address') }}</dt>
                        <dd>{{ $dealer->address }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">{{ __('Delivery address') }}</dt>
                        <dd>{{ $dealer->delivery_address }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">{{ __('Billing address') }}</dt>
                        <dd>{{ $dealer->billing_address }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Payment term (days)') }}</dt>
                        <dd>{{ $dealer->payment_term_days }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Application status') }}</dt>
                        <dd>{{ $dealer->application_status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Status') }}</dt>
                        <dd>{{ $dealer->is_active ? __('Active') : __('Inactive') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Login user') }}</dt>
                        <dd>
                            @forelse ($dealer->users as $user)
                                <div>{{ $user->name }} — {{ $user->email }}</div>
                            @empty
                                {{ __('No login user yet.') }}
                            @endforelse
                        </dd>
                    </div>
                    @if ($dealer->notes)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">{{ __('Notes') }}</dt>
                            <dd>{{ $dealer->notes }}</dd>
                        </div>
                    @endif
                    @if ($dealer->rejection_reason)
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">{{ __('Rejection reason') }}</dt>
                            <dd>{{ $dealer->rejection_reason }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            @can('approve', $dealer)
                @if ($dealer->application_status === \App\Enums\DealerApplicationStatus::Pending)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form method="POST" action="{{ route('dealers.approve', $dealer) }}" class="p-6 space-y-4">
                            @csrf
                            <h3 class="font-medium text-gray-800">{{ __('Approve dealer') }}</h3>
                            <p class="text-sm text-gray-600">{{ __('The dealer can sign in with the company email and this password.') }}</p>
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

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <form method="POST" action="{{ route('dealers.reject', $dealer) }}" class="p-6 space-y-4">
                            @csrf
                            <h3 class="font-medium text-gray-800">{{ __('Reject dealer') }}</h3>
                            <div>
                                <x-input-label for="rejection_reason" :value="__('Rejection reason')" />
                                <textarea id="rejection_reason" name="rejection_reason" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full" required>{{ old('rejection_reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('rejection_reason')" />
                            <x-input-error class="mt-2" :messages="$errors->get('application_status')" />
                        </div>
                        <x-danger-button>{{ __('Reject dealer') }}</x-danger-button>
                        </form>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</x-app-layout>
