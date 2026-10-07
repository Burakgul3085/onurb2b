<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Audit log') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="card grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-input-label for="action" :value="__('Action')" />
                <select id="action" name="action" class="field mt-1">
                    <option value="">{{ __('All actions') }}</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action->value }}" @selected($selected === $action->value)>{{ $action->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="btn">{{ __('Apply filter') }}</button>
            </div>
        </form>

        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Entity') }}</th>
                        <th>{{ __('Entity number') }}</th>
                        <th>{{ __('Old value') }}</th>
                        <th>{{ __('New value') }}</th>
                        <th>{{ __('IP address') }}</th>
                        <th>{{ __('User agent') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                            <td>{{ $log->user?->name }}</td>
                            <td>{{ $log->action->label() }}</td>
                            <td>{{ $log->entityName() }}</td>
                            <td>{{ $log->entity_number }}</td>
                            <td class="max-w-xs whitespace-pre-line text-sm">{{ $log->lines($log->old_values) }}</td>
                            <td class="max-w-xs whitespace-pre-line text-sm">{{ $log->lines($log->new_values) }}</td>
                            <td>{{ $log->ip_address }}</td>
                            <td class="max-w-xs truncate">{{ $log->user_agent }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-ink-muted">{{ __('No audit records yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
