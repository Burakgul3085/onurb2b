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
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            <aside class="relative hidden overflow-hidden bg-ink text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
                <div>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brass text-sm font-bold">OB</span>
                        <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <p class="mt-10 max-w-sm text-3xl font-semibold leading-tight">{{ __('Stationery distribution and dealer management') }}</p>
                </div>
                <p class="text-sm text-stone-400">{{ \App\Models\Dealer::PROVINCE }}</p>
            </aside>

            <div class="flex items-center justify-center px-4 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <a href="{{ route('login') }}" class="mb-8 inline-flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-ink text-sm font-bold text-white">OB</span>
                        <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                    </a>
                    <div class="card px-6 py-8 sm:px-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
