<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ $thread->dealer->company_name }}@if ($thread->order) · {{ $thread->order->number }}@endif</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ $thread->subject }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="space-y-3">
            @foreach ($thread->messages as $message)
                <article class="card p-5">
                    <p class="text-sm font-semibold text-ink">{{ $message->user->name }}</p>
                    <p class="text-xs text-ink-muted">{{ $message->created_at->format('d.m.Y H:i') }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm">{{ $message->body }}</p>
                </article>
            @endforeach
        </div>
        @if ($canReply)
            <form method="POST" action="{{ route('messages.reply', $thread) }}" class="card space-y-4 p-6">
                @csrf
                <div>
                    <x-input-label for="body" :value="__('Reply')" />
                    <textarea id="body" name="body" class="field mt-1" rows="4" required>{{ old('body') }}</textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('body')" />
                </div>
                <x-primary-button>{{ __('Reply') }}</x-primary-button>
            </form>
        @endif
    </div>
</x-app-layout>
