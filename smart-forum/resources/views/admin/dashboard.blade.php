<x-whatsapp-layout>
    <x-slot name="sidebar">
        @foreach(auth()->user()->groups as $g)
            <a href="{{ route('groups.topics', $g) }}" class="whatsapp-group-item">
                <div class="whatsapp-avatar whatsapp-avatar-sm" style="background: {{ $g->color ?? '#075E54' }};">
                    {{ $g->name[0] }}
                </div>
                <div class="flex-1 ml-3 min-w-0">
                    <div class="font-medium text-sm">{{ $g->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ $g->topics->count() }} topics</div>
                </div>
            </a>
        @endforeach
    </x-slot>

    <!-- ===== HEADER ===== -->
    <div class="bg-white px-5 py-3 border-b border-gray-200 flex items-center gap-3">
        <a href="{{ route('dashboard') }}" class="text-[#075E54] hover:text-[#128C7E] transition p-1 rounded-full hover:bg-gray-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <span class="text-lg font-semibold">Admin Dashboard</span>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
        <div class="max-w-7xl mx-auto">

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- ===== STATS CARDS ===== -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $totalUsers }}</div>
                    <div class="text-xs text-gray-500">Total Users</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $totalGroups }}</div>
                    <div class="text-xs text-gray-500">Groups</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $totalTopics }}</div>
                    <div class="text-xs text-gray-500">Topics</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                    <div class="text-2xl font-bold text-[#075E54]">{{ $totalPosts }}</div>
                    <div class="text-xs text-gray-500">Posts</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-yellow-200">
                    <div class="text-2xl font-bold text-yellow-600">{{ $pendingApprovals }}</div>
                    <div class="text-xs text-gray-500">Pending Approvals</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm border border-red-200">
                    <div class="text-2xl font-bold text-red-600">{{ $blacklistedUsers }}</div>
                    <div class="text-xs text-gray-500">Blacklisted</div>
                </div>
            </div>

            <!-- ===== QUICK ACTIONS ===== -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="{{ route('admin.groups.create') }}" 
                   class="block text-center p-4 bg-[#075E54] text-white rounded-lg hover:bg-[#128C7E] transition">
                    <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Create New Group
                </a>
                <a href="{{ route('admin.compliance') }}" 
                   class="block text-center p-4 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                    <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Compliance Management
                </a>
                <a href="{{ route('dashboard') }}" 
                   class="block text-center p-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/>
                    </svg>
                    Back to Forum
                </a>
            </div>

            <!-- ===== PENDING APPROVALS ===== -->
            @if($pendingUsers->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-700">Pending User Approvals</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            @foreach($pendingUsers as $user)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <span class="font-medium">{{ $user->name }}</span>
                                        <span class="text-sm text-gray-500 ml-2">{{ $user->email }}</span>
                                        <span class="text-xs text-gray-400 ml-2">Joined {{ $user->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.users.approve', $user->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.reject', $user->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700"
                                                    onclick="return confirm('Reject this user?')">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- ===== TWO-COLUMN LAYOUT ===== -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-700">Recent Activity</h3>
                    </div>
                    <div class="p-4 max-h-64 overflow-y-auto">
                        @if($recentActivity->count() > 0)
                            <div class="space-y-2">
                                @foreach($recentActivity as $activity)
                                    <div class="flex items-center justify-between text-sm p-2 hover:bg-gray-50 rounded">
                                        <div>
                                            <span class="font-medium">{{ $activity->user->name ?? 'Unknown' }}</span>
                                            <span class="text-gray-500">{{ ucfirst($activity->action_type) }}</span>
                                            <span class="text-gray-500">on</span>
                                            <a href="{{ route('topics.show', [$activity->topic->group_id ?? 0, $activity->topic_id]) }}" 
                                               class="text-[#075E54] hover:underline">
                                                {{ $activity->topic->title ?? 'Unknown topic' }}
                                            </a>
                                        </div>
                                        <span class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-400 text-center py-4">No activity yet.</p>
                        @endif
                    </div>
                </div>

                <!-- Top Groups -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-700">Top Groups (by topics)</h3>
                    </div>
                    <div class="p-4">
                        @if($topGroups->count() > 0)
                            <div class="space-y-2">
                                @foreach($topGroups as $group)
                                    <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                        <span class="font-medium">{{ $group->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $group->topics_count }} topics</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-400 text-center py-4">No groups yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-whatsapp-layout>