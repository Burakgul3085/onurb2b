<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm text-ink-muted">{{ $template->name }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-ink">{{ __('Mail templates') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 pb-10 sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('mail-templates.update', $template) }}" class="card space-y-4 p-6 sm:p-8">
            @csrf
            @method('PATCH')
            <x-flash />
            <div>
                <x-input-label for="subject" :value="__('Subject')" />
                <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" :value="old('subject', $template->subject)" required />
                <x-input-error class="mt-2" :messages="$errors->get('subject')" />
            </div>
            <div>
                <x-input-label for="body" :value="__('Message')" />
                <textarea id="body" name="body" class="field mt-1" rows="10" required>{{ old('body', $template->body) }}</textarea>
                <p class="mt-1 text-xs text-ink-muted">{{ __('Placeholders stay as written.') }} {contact} {company} {order_number} {reason} {amount} {sender} {subject} {body} {due_on}</p>
                <x-input-error class="mt-2" :messages="$errors->get('body')" />
            </div>
            <div>
                <input type="hidden" name="is_active" value="0" />
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) />
                    {{ __('Active') }}
                </label>
            </div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </form>
    </div>
</x-app-layout>
