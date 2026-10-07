<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dealers') }}
            </h2>

            @can('create', App\Models\Dealer::class)
                <a href="{{ route('dealers.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Create dealer') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <p class="p-4 text-sm text-green-700">{{ session('status') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <form method="GET" action="{{ route('dealers.index') }}" class="flex flex-col gap-2 sm:flex-row">
                        <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search dealers') }}" />
                        <select name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('All statuses') }}</option>
                            @foreach ($statuses as $applicationStatus)
                                <option value="{{ $applicationStatus->value }}" @selected($status === $applicationStatus->value)>{{ $applicationStatus->label() }}</option>
                            @endforeach
                        </select>
                        <x-primary-button>{{ __('Search') }}</x-primary-button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pe-4">{{ __('Company name') }}</th>
                                    <th class="py-2 pe-4">{{ __('Contact name') }}</th>
                                    <th class="py-2 pe-4">{{ __('District') }}</th>
                                    <th class="py-2 pe-4">{{ __('Application status') }}</th>
                                    <th class="py-2 pe-4">{{ __('Status') }}</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($dealers as $dealer)
                                    <tr class="border-t border-gray-100">
                                        <td class="py-3 pe-4">{{ $dealer->company_name }}</td>
                                        <td class="py-3 pe-4">{{ $dealer->contact_name }}</td>
                                        <td class="py-3 pe-4">{{ $dealer->district->label() }}</td>
                                        <td class="py-3 pe-4">{{ $dealer->application_status->label() }}</td>
                                        <td class="py-3 pe-4">{{ $dealer->is_active ? __('Active') : __('Inactive') }}</td>
                                        <td class="py-3 text-right space-x-3">
                                            @can('view', $dealer)
                                                <a href="{{ route('dealers.show', $dealer) }}" class="underline text-gray-700">{{ __('View') }}</a>
                                            @endcan
                                            @can('update', $dealer)
                                                <a href="{{ route('dealers.edit', $dealer) }}" class="underline text-gray-700">{{ __('Edit') }}</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-6 text-gray-500">{{ __('No dealers found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $dealers->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
