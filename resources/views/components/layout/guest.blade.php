<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    {{-- Auth and document-verification pages are never indexed — see
         docs/07-SEO.md §2's indexing policy. --}}
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-background font-sans text-content antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        {{-- Centered, single-column page with nothing competing for width —
             the full horizontal lockup reads best here. --}}
        <a href="{{ url('/') }}" class="mb-8" aria-label="{{ config('app.name') }}">
            <img src="{{ asset('images/branding/logo-horizontal.png') }}" alt="{{ config('app.name') }}" class="h-8 w-auto">
        </a>

        <div class="w-full max-w-md rounded-lg border border-line-divider bg-surface p-8 shadow-sm">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
