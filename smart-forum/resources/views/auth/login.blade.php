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
    <div class="min-h-screen flex items-center justify-center">
        <div class="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden">
            <!-- WhatsApp Header -->
            <div class="bg-[#075E54] px-6 py-8 text-white text-center">
                <div class="w-16 h-16 mx-auto bg-white/20 rounded-full flex items-center justify-center text-3xl">
                    💬
                </div>
                <h1 class="text-2xl font-bold mt-3">{{ config('app.name', 'Smart Forum') }}</h1>
                <p class="text-sm text-white/80">Sign in to continue</p>
            </div>

            <!-- Login Form -->
            <div class="p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any() && !$errors->has('email') && !$errors->has('password'))
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <input id="password" type="password" name="password" required
                               class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:border-[#075E54] focus:ring-1 focus:ring-[#075E54] outline-none">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#075E54] focus:ring-[#075E54]">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <!-- Removed Route::has() check – route is always defined by Breeze -->
                        <a href="{{ route('password.request') }}" class="text-sm text-[#075E54] hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition font-medium">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-600 mt-6">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-[#075E54] font-medium hover:underline">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>