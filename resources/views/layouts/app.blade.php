<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} – @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    @if(auth()->check())
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-14 items-center justify-between">
                <span class="text-lg font-semibold tracking-tight text-blue-700">
                    🗄 MultiDB Backup
                </span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="text-sm text-gray-500 hover:text-red-600 transition-colors">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </nav>
    @endif

    <main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-8">

        @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>

</body>
</html>
