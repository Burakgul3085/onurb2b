<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Dealers') }}</h2>
            @can('create', App\Models\Dealer::class)
                <x-primary-link :href="route('dealers.create')">{{ __('Create dealer') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('dealers.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search dealers') }}" />
                    <select name="status" class="field sm:max-w-xs">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $applicationStatus)
                            <option value="{{ $applicationStatus->value }}" @selected($status === $applicationStatus->value)>{{ $applicationStatus->label() }}</option>
                        @endforeach
                    </select>
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Company name') }}</th>
                                <th>{{ __('Contact name') }}</th>
                                <th>{{ __('District') }}</th>
                                <th>{{ __('Application status') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dealers as $dealer)
                                <tr>
                                    <td class="font-medium">{{ $dealer->company_name }}</td>
                                    <td>{{ $dealer->contact_name }}</td>
                                    <td>{{ $dealer->district->label() }}</td>
                                    <td>
                                        <x-badge :tone="$dealer->application_status->value === 'approved' ? 'on' : ($dealer->application_status->value === 'rejected' ? 'off' : 'wait')">
                                            {{ $dealer->application_status->label() }}
                                        </x-badge>
                                    </td>
                                    <td><x-badge :tone="$dealer->is_active ? 'on' : 'off'">{{ $dealer->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                                    <td class="text-right">
                                        <span class="inline-flex gap-3">
                                            @can('view', $dealer)
                                                <a href="{{ route('dealers.show', $dealer) }}" class="link">{{ __('View') }}</a>
                                            @endcan
                                            @can('update', $dealer)
                                                <a href="{{ route('dealers.edit', $dealer) }}" class="link">{{ __('Edit') }}</a>
                                            @endcan
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-ink-muted">{{ __('No dealers found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $dealers->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
