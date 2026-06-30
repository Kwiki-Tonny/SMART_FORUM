<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Smart Discussion Forum') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="whatsapp-container">
        <!-- ===== SIDEBAR ===== -->
        <div class="whatsapp-sidebar">
            <!-- Header: Forum Name -->
            <div class="bg-[#075E54] px-5 py-4 flex items-center justify-between text-white">
                <div class="flex items-center gap-3">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/>
                    </svg>
                    <span class="text-lg font-semibold">Smart Discussion Forum</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="bg-white border-b border-gray-200 px-3 py-2 flex items-center gap-2">
                <a href="{{ route('dashboard') }}" 
                   class="flex-1 text-center py-1.5 px-2 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#075E54] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Groups
                </a>
                <a href="{{ route('quizzes.index') }}" 
                   class="flex-1 text-center py-1.5 px-2 rounded-lg text-sm font-medium {{ request()->routeIs('quizzes.*') ? 'bg-[#075E54] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Quizzes
                </a>
                <a href="{{ route('analytics') }}" 
                   class="flex-1 text-center py-1.5 px-2 rounded-lg text-sm font-medium {{ request()->routeIs('analytics') ? 'bg-[#075E54] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Analytics
                </a>
                <a href="{{ route('recommendations') }}" 
                   class="flex-1 text-center py-1.5 px-2 rounded-lg text-sm font-medium {{ request()->routeIs('recommendations') ? 'bg-[#075E54] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Recommendations
                </a>
                <a href="{{ route('profile') }}" 
                   class="flex-1 text-center py-1.5 px-2 rounded-lg text-sm font-medium {{ request()->routeIs('profile') ? 'bg-[#075E54] text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                    Profile
                </a>
            </div>

            <!-- Admin-only: Create Group link -->
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="bg-white border-b border-gray-200 px-3 py-1.5">
                        <a href="{{ route('admin.groups.create') }}" 
                           class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            + Create New Group
                        </a>
                    </div>
                @endif
            @endauth

            <!-- Admin-only: User Management link -->
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="bg-white border-b border-gray-200 px-3 py-1">
                        <a href="{{ route('admin.users.index') }}" 
                        class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            👥 User Management
                        </a>
                    </div>
                @endif
            @endauth

            <!-- Admin-only: Admin Dashboard link -->
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="bg-white border-b border-gray-200 px-3 py-1">
                        <a href="{{ route('admin.dashboard') }}" 
                        class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            ⚙️ Admin Dashboard
                        </a>
                    </div>
                @endif
            @endauth

            <!-- Search -->
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-300">
                <input type="text" id="groupSearch" placeholder="Search groups..."
                       class="w-full px-4 py-2 border-none rounded-full text-sm outline-none shadow-sm">
            </div>

            <!-- Group List (injected) -->
            <div class="flex-1 overflow-y-auto bg-[#F9F9F9]" id="groupList">
                {{ $sidebar }}
            </div>

            <!-- Logout (bottom) -->
            <div class="border-t border-gray-200 p-3 bg-white">
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center justify-center gap-2 text-sm text-gray-600 hover:text-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- ===== CHAT AREA (main content) ===== -->
        <div class="whatsapp-chat">
            {{ $slot }}
        </div>
    </div>

    @stack('scripts')
</body>
</html>