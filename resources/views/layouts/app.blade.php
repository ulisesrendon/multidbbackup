<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} – @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-900 text-gray-100 antialiased dark:bg-gray-900 dark:text-gray-100">

    @if(auth()->check())
    <nav class="bg-gray-800 shadow-sm border-b border-gray-700 dark:bg-gray-800 dark:border-gray-700">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <span class="text-lg font-semibold tracking-tight text-blue-300 dark:text-blue-300">
                    🗄 MultiDB Backup
                </span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="text-sm text-gray-300 hover:text-red-400 transition-colors dark:text-gray-300 dark:hover:text-red-400">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </nav>
    @endif

    <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8">

        @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-900 border border-green-700 px-4 py-3 text-sm text-green-200 dark:bg-green-900 dark:border-green-700 dark:text-green-200">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-900 border border-red-700 px-4 py-3 text-sm text-red-200 dark:bg-red-900 dark:border-red-700 dark:text-red-200">
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
