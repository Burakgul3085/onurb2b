<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-ink">{{ __('Mail templates') }}</h2>
            <p class="mt-1 text-sm text-ink-muted">{{ __('Placeholders stay as written.') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 pb-10 sm:px-6 lg:px-8">
        <x-flash />
        <div class="card overflow-x-auto p-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('Template') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($templates as $template)
                        <tr>
                            <td><a class="link" href="{{ route('mail-templates.edit', $template) }}">{{ $template->name }}</a></td>
                            <td>{{ $template->subject }}</td>
                            <td><x-badge :tone="$template->is_active ? 'on' : 'off'">{{ $template->is_active ? __('Active') : __('Inactive') }}</x-badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
