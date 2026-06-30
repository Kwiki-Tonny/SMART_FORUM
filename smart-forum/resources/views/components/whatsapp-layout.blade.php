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

            <!-- Navigation Links (Profile removed) -->
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
            </div>

            <!-- Admin-only links -->
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="bg-white border-b border-gray-200 px-3 py-1.5">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            ⚙️ Admin Dashboard
                        </a>
                    </div>
                    <div class="bg-white border-b border-gray-200 px-3 py-1.5">
                        <a href="{{ route('admin.groups.create') }}" 
                           class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            + Create New Group
                        </a>
                    </div>
                    <div class="bg-white border-b border-gray-200 px-3 py-1">
                        <a href="{{ route('admin.users.index') }}" 
                           class="block text-center text-sm text-[#075E54] font-medium hover:underline">
                            👥 User Management
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

            <!-- ===== BOTTOM: PROFILE + LOGOUT ===== -->
            <div class="border-t border-gray-200 bg-white">
                <!-- Profile toggle -->
                <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 cursor-pointer transition" 
                     id="profileToggle" onclick="toggleProfileMenu()">
                    <div class="flex items-center gap-3">
                        <!-- Avatar - perfectly round with inline styles -->
                        <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; background-color: #075E54; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <span style="color: white; font-weight: 600; font-size: 16px; text-transform: uppercase;">
                                {{ auth()->user()->name[0] ?? 'U' }}
                            </span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-400">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" id="profileArrow" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </div>

                <!-- Dropdown menu (hidden by default) -->
                <div id="profileMenu" class="hidden border-t border-gray-100 bg-gray-50">
                    <a href="{{ route('profile') }}" 
                       class="block px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 transition flex items-center gap-3">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        My Profile
                    </a>
                    <hr class="border-gray-200">
                    <form method="POST" action="{{ route('logout') }}" class="block">
                        @csrf
                        <button type="submit" 
                                class="w-full text-left px-4 py-2.5 text-sm text-red-600 hover:bg-gray-100 transition flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== CHAT AREA (main content) ===== -->
        <div class="whatsapp-chat">
            {{ $slot }}
        </div>
    </div>

    <script>
        // Toggle profile dropdown
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            const arrow = document.getElementById('profileArrow');
            if (menu) {
                menu.classList.toggle('hidden');
                if (arrow) {
                    arrow.style.transform = menu.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const toggle = document.getElementById('profileToggle');
            const menu = document.getElementById('profileMenu');
            if (toggle && menu && !toggle.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
                const arrow = document.getElementById('profileArrow');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        });
    </script>

    @stack('scripts')
</body>
</html>