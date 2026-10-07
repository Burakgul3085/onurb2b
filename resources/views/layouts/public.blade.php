<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-paper font-sans text-ink antialiased">
        <div class="min-h-screen">
            <header class="border-b border-stone-200 bg-white">
                <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ink text-sm font-bold text-white">OB</span>
                        <span class="font-semibold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <a href="{{ route('login') }}" class="link">{{ __('Log in') }}</a>
                </div>
            </header>

            <div class="mx-auto max-w-3xl px-4 py-8">
                <div class="card p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
