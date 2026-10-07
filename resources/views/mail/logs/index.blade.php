<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Mail log') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Mail recipient') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Template') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Sent at') }}</th>
                        <th>{{ __('Error') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->recipient }}</td>
                            <td>{{ $log->subject }}</td>
                            <td>{{ $log->template?->name }}</td>
                            <td><x-badge :tone="$log->status->tone()">{{ $log->status->label() }}</x-badge></td>
                            <td>{{ $log->sent_at?->format('d.m.Y H:i') }}</td>
                            <td class="max-w-xs truncate">{{ $log->error }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-ink-muted">{{ __('No mail has been sent yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </div>
</x-app-layout>
