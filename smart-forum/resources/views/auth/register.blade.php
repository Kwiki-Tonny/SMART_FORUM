<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Smart Forum') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#DADBDC] font-sans antialiased">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden">
            <!-- WhatsApp Header -->
            <div class="bg-[#075E54] px-6 py-6 text-white text-center">
                <div class="w-16 h-16 mx-auto bg-white/20 rounded-full flex items-center justify-center text-3xl">
                    ✏️
                </div>
                <h1 class="text-2xl font-bold mt-2">Create Account</h1>
                <p class="text-sm text-white/80">Join the Smart Forum community</p>
            </div>

            <!-- Register Form -->
            <div class="p-6">
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" type="password" name="password" required
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition font-medium">
                        Create Account
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Already have an account?
                    <a href="{{ route('login') }}" class="text-[#075E54] font-medium hover:underline">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>