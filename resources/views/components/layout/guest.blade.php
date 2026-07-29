<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-background font-sans text-content antialiased">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="mb-8 text-xl font-bold text-content">
            {{ config('app.name') }}
        </a>

        <div class="w-full max-w-md rounded-lg border border-line-divider bg-surface p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
