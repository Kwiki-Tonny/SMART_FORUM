<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Smart Forum') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="whatsapp-container">
        <!-- ===== SIDEBAR ===== -->
        <div class="whatsapp-sidebar">
            <!-- Header -->
            <div class="bg-[#EDEDED] px-5 py-4 border-b border-gray-300 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="whatsapp-avatar">
                        {{ auth()->user()->name[0] ?? 'U' }}
                    </div>
                    <div>
                        <div class="font-semibold text-base">
                            {{ auth()->user()->name }}
                        </div>
                        <div class="text-xs text-gray-400 flex items-center gap-1.5">
                            <span class="whatsapp-status-dot online"></span>
                            Online
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 text-gray-400">
                    <a href="{{ route('dashboard') }}" class="hover:text-gray-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                        </svg>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-gray-600 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Search -->
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300">
                <input type="text" id="groupSearch" placeholder="Search or start new chat..."
                       class="w-full px-4 py-2 border-none rounded-full text-sm outline-none shadow-sm">
            </div>

            <!-- Group List -->
            <div class="flex-1 overflow-y-auto bg-[#F9F9F9]" id="groupList">
                {{ $sidebar }}
            </div>
        </div>

        <!-- ===== CHAT AREA ===== -->
        <div class="whatsapp-chat">
            {{ $slot }}
        </div>
    </div>

    @stack('scripts')
</body>
</html>