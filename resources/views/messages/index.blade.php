<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Messages') }}</h2>
            @can('create', App\Models\MessageThread::class)
                <a href="{{ route('messages.create') }}" class="btn btn-primary">{{ __('Write a message') }}</a>
            @endcan
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Subject') }}</th>
                        @unless (Auth::user()->dealer_id)
                            <th>{{ __('Dealer') }}</th>
                        @endunless
                        <th>{{ __('Order number') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($threads as $thread)
                        <tr>
                            <td><a class="link" href="{{ route('messages.show', $thread) }}">{{ $thread->subject }}</a></td>
                            @unless (Auth::user()->dealer_id)
                                <td>{{ $thread->dealer->company_name }}</td>
                            @endunless
                            <td>{{ $thread->order?->number }}</td>
                            <td>{{ $thread->last_message_at ? \Illuminate\Support\Carbon::parse($thread->last_message_at)->format('d.m.Y H:i') : $thread->created_at->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-ink-muted">{{ __('No messages yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $threads->links() }}</div>
        </div>
    </div>
</x-app-layout>
