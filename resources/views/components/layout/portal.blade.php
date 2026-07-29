@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-background font-sans text-content antialiased">
    <header class="border-b border-line-divider bg-surface">
        <div class="mx-auto flex max-w-container items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('portal.dashboard') }}" class="text-lg font-bold text-content">
                {{ config('app.name') }}
            </a>

            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('portal.dashboard') }}" class="text-content-muted hover:text-content">Dashboard</a>
                <a href="{{ route('portal.profile.edit') }}" class="text-content-muted hover:text-content">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-content-muted hover:text-content">Log out</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-container px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>
</body>
</html>
