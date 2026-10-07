<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Users') }}</h2>
            @can('create', App\Models\User::class)
                <x-primary-link :href="route('users.create')">{{ __('Create user') }}</x-primary-link>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card">
            <div class="space-y-4 p-6">
                <form method="GET" action="{{ route('users.index') }}" class="flex flex-col gap-2 sm:flex-row">
                    <x-text-input name="search" type="search" class="block w-full" :value="$search" placeholder="{{ __('Search by name or email') }}" />
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Roles') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="font-medium">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->roleLabels() }}</td>
                                    <td><x-badge :tone="$user->is_active ? 'on' : 'off'">{{ $user->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                                    <td class="text-right">
                                        @can('update', $user)
                                            <a href="{{ route('users.edit', $user) }}" class="link">{{ __('Edit') }}</a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-ink-muted">{{ __('No users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>{{ $users->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
