<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Users') }}
            </h2>

            @can('create', App\Models\User::class)
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    {{ __('Create user') }}
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
                    <form method="GET" action="{{ route('users.index') }}" class="flex gap-2">
                        <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search by name or email') }}" />
                        <x-primary-button>{{ __('Search') }}</x-primary-button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500">
                                    <th class="py-2 pe-4">{{ __('Name') }}</th>
                                    <th class="py-2 pe-4">{{ __('Email') }}</th>
                                    <th class="py-2 pe-4">{{ __('Roles') }}</th>
                                    <th class="py-2 pe-4">{{ __('Status') }}</th>
                                    <th class="py-2"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr class="border-t border-gray-100">
                                        <td class="py-3 pe-4">{{ $user->name }}</td>
                                        <td class="py-3 pe-4">{{ $user->email }}</td>
                                        <td class="py-3 pe-4">{{ $user->roleLabels() }}</td>
                                        <td class="py-3 pe-4">{{ $user->is_active ? __('Active') : __('Inactive') }}</td>
                                        <td class="py-3 text-right">
                                            @can('update', $user)
                                                <a href="{{ route('users.edit', $user) }}" class="underline text-gray-700">{{ __('Edit') }}</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-6 text-gray-500">{{ __('No users found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>{{ $users->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
