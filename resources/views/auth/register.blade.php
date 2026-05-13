@extends('layouts.app')
@section('title', 'First-Time Setup')

@section('content')
<div class="flex min-h-[70vh] items-center justify-center">
    <div class="w-full max-w-sm">

        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-900">First-Time Setup</h1>
            <p class="mt-1 text-sm text-gray-500">Create your admin account to get started.</p>
        </div>

        <div class="rounded-2xl bg-white shadow-md border border-gray-100 px-6 py-8">
            <form action="{{ route('register') }}" method="POST" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               @error('email') border-red-400 @enderror"
                        placeholder="admin@example.com"
                    >
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               @error('password') border-red-400 @enderror"
                        placeholder="Min. 8 characters"
                    >
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                        Confirm password
                    </label>
                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Repeat your password"
                    >
                </div>

                <button type="submit"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white
                           hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500
                           transition-colors">
                    Create Admin Account
                </button>
            </form>
        </div>

    </div>
</div>
@endsection
